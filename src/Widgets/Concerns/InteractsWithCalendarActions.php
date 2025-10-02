<?php

declare(strict_types=1);

namespace Peniti\FilamentCalendar\Widgets\Concerns;

use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Peniti\FilamentCalendar\Widgets\Calendar;

trait InteractsWithCalendarActions
{
    public function bootedInteractsWithCalendarActions(): void
    {
        $this->cacheCalendarActions();
    }

    public function hasCreateAction(): bool
    {
        return $this->hasAction('create');
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
        /** @var array<string, Action> $actions */
        $actions = Action::configureUsing(
            $this->configureCalendarAction(...),
            fn (): array => $this->calendarActions(),
        );

        foreach ([...$actions, ...$this->customCalendarActions()] as $action) {
            if (! $action instanceof Action) {
                throw new InvalidArgumentException(
                    sprintf('Calendar actions must be an instance of %s.', Action::class),
                );
            }

            $this->cacheAction($action);
        }
    }

    /**  @return array<Action> */
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
            default => throw new InvalidArgumentException(
                sprintf(
                    'Calendar actions must be an instance of %s, %s, %s, or %s. '.
                    'If you want to add custom actions, you can override the %s method.',
                    CreateAction::class,
                    EditAction::class,
                    DeleteAction::class,
                    ViewAction::class,
                    self::class.'::customCalendarActions',
                ),
            ),
        };
    }

    protected function configureCreateAction(CreateAction $action): void
    {
        /** @var class-string<resource> $resource */
        $resource = static::getResource();

        $action->modal()
            ->authorize($resource::canCreate())
            ->model($resource::getModel())
            ->schema(fn (Schema $form, Calendar $livewire) => $livewire->schema($form))
            ->mountUsing(function (Schema $schema, array $arguments) {
                /** @var array<string, mixed> $arguments */
                return $schema->fill($arguments);
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
            ->schema(fn (Schema $schema, Calendar $livewire) => $livewire->schema($schema))
            ->extraModalFooterActions(function () {
                /** @var Action|null $deleteAction */
                $deleteAction = Arr::get($this->cachedActions, 'delete');

                return [$deleteAction?->extraAttributes(['class' => 'ml-auto order-last'])];
            })
            ->after(fn (Calendar $livewire) => $livewire->refreshEvents());
    }

    protected function configureViewAction(ViewAction $action): void
    {
        /** @var class-string<resource> $resource */
        $resource = static::getResource();

        /** @var array<Action> $editAction */
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
            ->schema(fn (Schema $schema, Calendar $livewire) => $livewire->schema($schema))
            ->extraModalFooterActions($editAction)
            ->after(fn (Calendar $livewire) => $livewire->refreshEvents());
    }

    /**  @return array<Action> */
    protected function customCalendarActions(): array
    {
        return [];
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
