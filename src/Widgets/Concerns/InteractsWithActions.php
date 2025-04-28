<?php

declare(strict_types=1);

namespace Peniti\FilamentCalendar\Widgets\Concerns;

use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\StaticAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Peniti\FilamentCalendar\Widgets\Calendar;

trait InteractsWithActions
{
    public function bootedInteractsWithActions(): void
    {
        $this->cacheCalendarActions();
    }

    public function hasCreateAction(): bool
    {
        return $this->hasAction('create');
    }

    public function hasDeleteAction(): bool
    {
        return $this->hasAction('delete');
    }

    public function hasEditAction(): bool
    {
        return $this->hasAction('edit');
    }

    public function hasViewAction(): bool
    {
        return $this->hasAction('view');
    }

    protected function cacheCalendarActions(): void
    {
        /** @var array<string, Action|ActionGroup> $actions */
        $actions = Action::configureUsing(
            $this->configureCalendarAction(...),
            fn (): array => $this->calendarActions(),
        );

        foreach ($actions as $action) {
            if ($action instanceof ActionGroup) {
                /** @var array<string, Action> $grouped */
                $grouped = $action->livewire($this)->getFlatActions();

                $this->mergeCachedActions($grouped);

                continue;
            }

            if (! $action instanceof Action) {
                throw new InvalidArgumentException(
                    sprintf('Calendar actions must be an instance of %s, or %s.', Action::class, ActionGroup::class)
                );
            }

            $this->cacheAction($action);
        }
    }

    /**  @return array<Action|ActionGroup> */
    protected function calendarActions(): array
    {
        return [];
    }

    protected function configureCalendarAction(Action $action): void
    {
        match (true) {
            $action instanceof CreateAction => $this->configureCreateAction($action),
            $action instanceof EditAction => $this->configureEditAction($action),
            $action instanceof DeleteAction => $this->configureDeleteAction($action),
            $action instanceof ViewAction => $this->configureViewAction($action),
            default => null,
        };

    }

    protected function configureCreateAction(CreateAction $action): void
    {
        /** @var class-string<resource> $resource */
        $resource = static::getResource();

        $action->modal()
            ->authorize($resource::canCreate())
            ->model($resource::getModel())
            ->form(fn (Form $form, Calendar $livewire) => $livewire->form($form))
            ->mountUsing(function (Form $form, array $arguments) {
                /** @var array<string, mixed> $arguments */
                return $form->fill($arguments);
            })
            ->using($this->save(...))
            ->createAnother(false)
            ->after(fn (Calendar $livewire) => $livewire->refreshEvents());
    }

    protected function configureDeleteAction(DeleteAction $action): void
    {
        /** @var class-string<resource> $resource */
        $resource = static::getResource();

        $action->modal()
            ->cancelParentActions()
            ->model($resource::getModel())
            ->record(fn (Calendar $livewire) => $livewire->getRecord())
            ->recordTitle(fn (Calendar $livewire) => $livewire->getRecordTitle())
            ->authorize(function (Calendar $livewire) use ($resource) {
                $record = $livewire->getRecord();
                assert(! is_null($record));

                return $resource::canDelete($record);
            })
            ->after(fn (Calendar $livewire) => $livewire->resetRecord()->refreshEvents());
    }

    protected function configureEditAction(EditAction $action): void
    {
        /** @var class-string<resource> $resource */
        $resource = static::getResource();

        $action->modal()
            ->cancelParentActions()
            ->model($resource::getModel())
            ->record(fn (Calendar $livewire) => $livewire->getRecord())
            ->recordTitle(fn (Calendar $livewire) => $livewire->getRecordTitle())
            ->authorize(function (Calendar $livewire) use ($resource) {
                $record = $livewire->getRecord();
                assert(! is_null($record));

                return $resource::canEdit($record);
            })
            ->form(fn (Form $form, Calendar $livewire) => $livewire->form($form))
            ->extraModalFooterActions(function () {
                /** @var StaticAction|null $deleteAction */
                $deleteAction = Arr::get($this->cachedActions, 'delete');

                return [$deleteAction?->extraAttributes(['class' => 'ml-auto order-last'])];
            })
            ->after(fn (Calendar $livewire) => $livewire->refreshEvents());
    }

    protected function configureViewAction(ViewAction $action): void
    {
        /** @var class-string<resource> $resource */
        $resource = static::getResource();

        /** @var array<StaticAction> $editAction */
        $editAction = Arr::only($this->cachedActions, ['edit']);

        $action->modal()
            ->model($resource::getModel())
            ->record(fn (Calendar $livewire) => $livewire->getRecord())
            ->recordTitle(fn (Calendar $livewire) => $livewire->getRecordTitle())
            ->authorize(function (Calendar $livewire) use ($resource) {
                $record = $livewire->getRecord();
                assert(! is_null($record));

                return $resource::canView($record);
            })
            ->form(fn (Form $form, Calendar $livewire) => $livewire->form($form))
            ->extraModalFooterActions($editAction)
            ->after(fn (Calendar $livewire) => $livewire->refreshEvents());
    }

    protected function hasAction(string $name): bool
    {
        return Arr::has($this->cachedActions, $name);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  class-string<Model>  $model
     *
     * @throws Exception
     */
    protected function save(array $data, string $model): Model|false
    {
        /** @var class-string<resource> $resource */
        $resource = static::getResource();

        /** @var Model $record */
        $record = new ($model)($data);

        if ($resource::isScopedToTenant() && ($tenant = Filament::getTenant())) {
            $relationship = $resource::getTenantRelationship($tenant);

            if ($relationship instanceof HasManyThrough) {
                return tap($record)->save();
            }

            /** @var HasMany<Model, Model> $relationship */
            return $relationship->save($record);
        }

        return tap($record)->save();
    }
}
