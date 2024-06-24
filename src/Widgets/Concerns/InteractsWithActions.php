<?php

namespace Peniti\FilamentCalendar\Widgets\Concerns;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Peniti\FilamentCalendar\Widgets\CalendarOverview;

trait InteractsWithActions
{
    public function bootedInteractsWithActions(): void
    {
        $this->cacheCalendarActions();
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
                $this->mergeCachedActions(
                    $action->livewire($this)->getFlatActions()
                );

                continue;
            }

            if (! $action instanceof Action) {
                throw new InvalidArgumentException(
                    'Calendar actions must be an instance of '.Action::class.', or '.ActionGroup::class.'.'
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
        $resource = static::getResource();

        $action->modal()
            ->authorize($resource::canCreate())
            ->model($resource::getModel())
            ->form(fn (Form $form, CalendarOverview $livewire) => $livewire->form($form->columns(2)))
            ->mountUsing(fn (Form $form, array $arguments) => $form->fill($arguments))
            ->using(function (array $data, string $model) use ($resource): Model {
                $record = new ($model)($data);

                if ($resource::isScopedToTenant() && ($tenant = Filament::getTenant())) {
                    $relationship = $resource::getTenantRelationship($tenant);

                    if ($relationship instanceof HasManyThrough) {
                        $record->save();

                        return $record;
                    }

                    return $relationship->save($record);
                }

                $record->save();

                return $record;
            })
            ->createAnother(false)
            ->after(fn (CalendarOverview $livewire) => $livewire->refreshEvents());
    }

    protected function configureEditAction(EditAction $action): void
    {
        $resource = static::getResource();

        $action->modal()
            ->model($resource::getModel())
            ->record(fn (CalendarOverview $livewire) => $livewire->getRecord())
            ->recordTitle(fn (CalendarOverview $livewire) => $livewire->getRecordTitle())
            ->authorize(fn (CalendarOverview $livewire) => $resource::canEdit($livewire->getRecord()))
            ->form(fn (Form $form, CalendarOverview $livewire) => $livewire->form($form->columns(2)))
            ->extraModalFooterActions(fn () => [Arr::get($this->cachedActions, 'delete')->extraAttributes(['class' => 'ml-auto order-last'])])
            ->after(fn (CalendarOverview $livewire) => $livewire->refreshEvents());
    }

    protected function configureDeleteAction(DeleteAction $action): void
    {
        $resource = static::getResource();

        $action->modal()
            ->cancelParentActions()
            ->model($resource::getModel())
            ->record(fn (CalendarOverview $livewire) => $livewire->getRecord())
            ->recordTitle(fn (CalendarOverview $livewire) => $livewire->getRecordTitle())
            ->authorize(fn (CalendarOverview $livewire) => $resource::canDelete($livewire->getRecord()))
            ->after(fn (CalendarOverview $livewire) => $livewire->resetRecord()->refreshEvents());
    }

    protected function configureViewAction(ViewAction $action): void
    {
        $resource = static::getResource();

        $action->modal()
            ->model($resource::getModel())
            ->record(fn (CalendarOverview $livewire) => $livewire->getRecord())
            ->recordTitle(fn (CalendarOverview $livewire) => $livewire->getRecordTitle())
            ->authorize(fn (CalendarOverview $livewire) => $resource::canView($livewire->getRecord()))
            ->form(fn (Form $form, CalendarOverview $livewire) => $livewire->form($form->columns(2)))
            ->extraModalFooterActions(Arr::only($this->cachedActions, ['edit']))
            ->after(fn (CalendarOverview $livewire) => $livewire->refreshEvents());
    }

    protected function hasViewAction(): bool
    {
        return Arr::has($this->cachedActions, 'view');
    }
}
