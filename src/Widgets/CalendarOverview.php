<?php

namespace Peniti\FilamentCalendar\Widgets;

use Carbon\Carbon;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Widgets\Widget;
use Illuminate\Support\Arr;
use Peniti\FilamentCalendar\Widgets\Concerns\InteractsWithActions as InteractsWithCalendarActions;
use Peniti\FilamentCalendar\Widgets\Concerns\InteractsWithResource;
use Spatie\OpeningHours\Day;
use Spatie\OpeningHours\OpeningHours;
use Spatie\OpeningHours\OpeningHoursForDay;
use Spatie\OpeningHours\Time;
use Spatie\OpeningHours\TimeRange;

class CalendarOverview extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithCalendarActions;
    use InteractsWithForms;
    use InteractsWithResource;

    protected static string $view = 'filament-calendar::widgets.calendar-overview';

    protected int|string|array $columnSpan = 'full';

    public array $options = [];

    public function mount(): void
    {
        $this->options = array_filter([
            ...$this->options,

            'allDaySlot' => $this->getAllDaySlot(),
            'businessHours' => $this->parseBusinessHours(),

            'slotDuration' => $this->getSlotDuration(),
            ...$this->slotOptions(),

            'googleCalendarApiKey' => config('filament-calendar.sources.google.calendar_key'),
            'googleCalendarId' => config('filament-calendar.sources.google.calendar_id'),
        ], static fn ($element) => ! is_null($element));
    }

    public function getAllDaySlot(): bool
    {
        return true;
    }

    public function getBusinessHours(): ?OpeningHours
    {
        return null;
    }

    public function getSlotExtraTime(): int
    {
        return 1;
    }

    public function getSlotDuration(): string
    {
        return '00:15:00';
    }

    public function select(int|string $key): void
    {
        $this->resolveRecord($key);

        $this->mountAction($this->hasViewAction() ? 'view' : 'edit');
    }

    public function update(int|string $id, string $start, ?string $end, bool $allDay): void
    {
        $start = Carbon::parse($start)
            ->setTimezone(config('app.timezone'))
            ->format('Y-m-d H:i');

        $end = Carbon::parse($end)
            ->setTimezone(config('app.timezone'))
            ->format('Y-m-d H:i');

        $this->resolveRecord($id)?->update(compact('start', 'end', 'allDay'));
        $this->refreshEvents();
    }

    public function fetchEvents(string $start, string $end): array
    {
        return [];
    }

    public function refreshEvents(): void
    {
        $this->dispatch('filament-calendar--refresh');
    }

    public function create(string $start, ?string $end, bool $allDay): void
    {
        $this->mountAction('create', compact('start', 'end'));
    }

    public function form(Form $form): Form
    {
        return static::getResource()::form($form);
    }

    protected function parseBusinessHours(): array
    {
        $hours = $this->getBusinessHours()?->map(
            fn (OpeningHoursForDay $hoursForDay, string $day) => $hoursForDay->map(fn (TimeRange $range) => [
                'daysOfWeek' => [Day::toISO($day)],
                'startTime' => (string) $range->start(),
                'endTime' => (string) $range->end(),
            ])
        );

        return Arr::flatten(array_values($hours ?? []), 1);
    }

    protected function slotOptions(): array
    {
        if (! $businessHours = $this->getBusinessHours()) {
            return [];
        }

        /** @var Time $slotMinTime */
        $slotMinTime = array_reduce(
            $openings = Arr::flatten($businessHours->map(
                fn (OpeningHoursForDay $hoursForDay, string $day) => $hoursForDay->map(fn (TimeRange $range) => $range->start())
            ), 1),
            static fn (Time $result, Time $opening) => $opening->isBefore($result) ? $opening : $result,
            $openings[0]
        );

        /** @var Time $slotMaxTime */
        $slotMaxTime = array_reduce(
            $closings = Arr::flatten($businessHours->map(
                fn (OpeningHoursForDay $hoursForDay, string $day) => $hoursForDay->map(fn (TimeRange $range) => $range->end())
            ), 1),
            static fn (Time $result, Time $closing) => $closing->isAfter($result) ? $closing : $result,
            $closings[0]
        );

        return [
            'slotMinTime' => (new Carbon($slotMinTime->toDateTime()))
                ->subHours($this->getSlotExtraTime())
                ->format('H:i:s'),
            'slotMaxTime' => (new Carbon($slotMaxTime->toDateTime()))
                ->addHours($this->getSlotExtraTime())
                ->format('H:i:s'),
        ];
    }
}
