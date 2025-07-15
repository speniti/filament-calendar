<?php

declare(strict_types=1);

namespace Peniti\FilamentCalendar\Widgets;

use Carbon\Carbon;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;
use InvalidArgumentException;
use Peniti\FilamentCalendar\Calendar\Event;
use Peniti\FilamentCalendar\Widgets\Concerns\InteractsWithActions as InteractsWithCalendarActions;
use Peniti\FilamentCalendar\Widgets\Concerns\InteractsWithResource;
use Spatie\OpeningHours\Day;
use Spatie\OpeningHours\OpeningHours;
use Spatie\OpeningHours\OpeningHoursForDay;
use Spatie\OpeningHours\Time;
use Spatie\OpeningHours\TimeRange;

abstract class Calendar extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithCalendarActions;
    use InteractsWithForms;
    use InteractsWithResource;

    /** @var array<string,mixed> */
    public array $options = [];

    protected int|string|array $columnSpan = 'full';

    /** @phpstan-param view-string $view */
    protected static string $view = 'filament-calendar::widgets.overview';

    /** @return array<int, Event>|list<Event> */
    abstract public function fetchEvents(string $start, string $end): array;

    public function create(string $start, ?string $end, bool $allDay): mixed
    {
        if (! $this->hasCreateAction()) {
            return null;
        }

        return $this->mountAction('create', compact('start', 'end', 'allDay'));
    }

    public function edit(int|string $id, string $start, ?string $end, bool $allDay): void
    {
        $timeZone = Config::string('app.timezone');

        $this->updateRecord(
            $this->resolveRecord($id),
            Carbon::parse($start)->setTimezone($timeZone),
            empty($end) ? null : Carbon::parse($end)->setTimezone($timeZone),
            $allDay
        );

        $this->refreshEvents();
        $this->success();
    }

    public function form(Form $form): Form
    {
        if (! class_exists($resource = self::getResource())) {
            return $form;
        }

        return $resource::form($form);
    }

    public function getAllDaySlot(): bool
    {
        return true;
    }

    public function getBusinessHours(): ?OpeningHours
    {
        return null;
    }

    public function getPlaceholderData(): array
    {
        return [
            'columnSpan' => $this->getColumnSpan(),
            'columnStart' => $this->getColumnStart(),
            'options' => $this->options,
        ];
    }

    public function getSlotDuration(): string
    {
        return '00:15:00';
    }

    public function getSlotExtraTime(): int
    {
        return 1;
    }

    final public function mount(): void
    {
        /** @var array<string, mixed> $options */
        $options = array_filter([
            ...$this->options,

            'allDaySlot' => $this->getAllDaySlot(),
            'businessHours' => $this->parseBusinessHours(),

            'slotDuration' => $this->getSlotDuration(),
            ...$this->slotOptions(),

            'googleCalendarApiKey' => Config::string('filament-calendar.sources.google.calendar_key'),
            'googleCalendarId' => Config::string('filament-calendar.sources.google.calendar_id'),

            'timeZone' => Config::string('app.timezone'),
        ], static fn ($element) => ! is_null($element));

        $this->options = $this->parseOptions($options);
    }

    public function placeholder(): View
    {
        return view(
            'filament-calendar::widgets.loading-section',
            [
                'height' => $this->getPlaceholderHeight(),
                ...$this->getPlaceholderData(),
            ],
        );
    }

    final public function refreshEvents(): void
    {
        $this->dispatch('filament-calendar--refresh');
    }

    public function select(int|string $id): mixed
    {
        if (! $this->resolveRecord($id)) {
            return null;
        }

        if ($this->hasViewAction()) {
            return $this->mountAction('view');
        }

        if ($this->hasEditAction()) {
            return $this->mountAction('edit');
        }

        return null;
    }

    /** @return array{daysOfWeek: list<int>, startTime: string, endTime: string}|array<empty> */
    protected function parseBusinessHours(): array
    {
        if (! $businessHours = $this->getBusinessHours()) {
            return [];
        }

        /** @var array{daysOfWeek: list<int>, startTime: string, endTime: string} */
        return $businessHours->flatMap(
            fn (OpeningHoursForDay $hoursForDay, string $day) => $hoursForDay->map(fn (TimeRange $range) => [
                'daysOfWeek' => [Day::fromName($day)->toISO()],
                'startTime' => (string) $range->start(),
                'endTime' => (string) $range->end(),
            ])
        );
    }

    /**
     * @param  array<string,mixed>  $options
     * @return array<string,mixed>
     */
    protected function parseOptions(array $options): array
    {
        if (is_string($aspectRatio = data_get($options, 'aspectRatio', 16 / 9))) {
            $aspectRatio = explode('/', $aspectRatio);
            $options['aspectRatio'] = (int) $aspectRatio[0] / (int) $aspectRatio[1];
        }

        return $options;
    }

    /** @return array{slotMinTime: string, slotMaxTime: string}|array<empty> */
    protected function slotOptions(): array
    {
        if (! $businessHours = $this->getBusinessHours()) {
            return [];
        }

        $slotMinTime = $this->timeSlot($businessHours, kind: 'min');
        $slotMaxTime = $this->timeSlot($businessHours, kind: 'max');

        return [
            'slotMinTime' => new Carbon($slotMinTime->toDateTime())
                ->subHours($this->getSlotExtraTime())
                ->format('H:i:s'),
            'slotMaxTime' => new Carbon($slotMaxTime->toDateTime())
                ->addHours($this->getSlotExtraTime())
                ->format('H:i:s'),
        ];
    }

    protected function success(): void
    {
        Notification::make()->success()
            ->title(__('filament-actions::edit.single.notifications.saved.title'))
            ->send();
    }

    protected function timeSlot(OpeningHours $businessHours, string $kind): Time
    {
        /** @var Collection<int, Time> $slots */
        $slots = collect(
            $businessHours->flatMap(
                fn (OpeningHoursForDay $hoursForDay) => $hoursForDay->map(function (TimeRange $range) use ($kind) {
                    return match ($kind) {
                        'min' => $range->start(),
                        'max' => $range->end(),
                        default => throw new InvalidArgumentException(sprintf('Invalid slot kind: %s', $kind))
                    };
                })
            )
        );

        return $slots->reduce(
            static function (?Time $result, Time $opening) use ($kind) {
                assert($result instanceof Time);

                return match ($kind) {
                    'min' => $opening->isBefore($result) ? $opening : $result,
                    'max' => $opening->isAfter($result) ? $opening : $result,
                    default => throw new InvalidArgumentException(sprintf('Invalid slot kind: %s', $kind))
                };
            },
            $slots[0]
        );
    }

    protected function updateRecord(?Model $record, Carbon $start, ?Carbon $end, bool $allDay): void
    {
        $record?->update(compact('start', 'end', 'allDay'));
    }
}
