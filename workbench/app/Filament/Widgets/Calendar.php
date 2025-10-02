<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\AppointmentResource;
use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentColor;
use Peniti\FilamentCalendar\Calendar\Event;
use Peniti\FilamentCalendar\Widgets\Calendar as BaseCalendar;
use Spatie\OpeningHours\OpeningHours;

class Calendar extends BaseCalendar
{
    public array $options = [
        'aspectRatio' => '16/10',
        'headerToolbar' => [
            'right' => 'prev,next today,refresh',
            'center' => 'title',
            'left' => 'dayGridMonth,dayGridWeek',
        ],
        'initialView' => 'dayGridWeek',
        'navLinks' => true,
        'navLinkDayClick' => 'dayGridWeek',
    ];

    protected static string $resource = AppointmentResource::class;

    public function fetchEvents(string $start, string $end): array
    {
        $appointments = Appointment::query()
            ->whereBetween('start', [Carbon::parse($start), Carbon::parse($end)])->get();

        return $appointments
            ->map(function (Appointment $appointment) {
                /** @var ?string $color */
                $color = data_get(FilamentColor::getColors(), "$appointment->color.500");

                return new Event(
                    id: $appointment->ulid,
                    title: $appointment->summary,
                    start: $appointment->start,
                    allDay: $appointment->allDay,
                    backgroundColor: $color,
                    startEditable: true,
                );
            })
            ->all();
    }

    public function getBusinessHours(): OpeningHours
    {
        return OpeningHours::create([
            'monday' => ['09:00-19:00'],
            'tuesday' => ['09:00-19:00'],
            'wednesday' => ['09:00-19:00'],
            'thursday' => ['09:00-19:00'],
            'friday' => ['09:00-19:00'],
        ]);
    }

    protected function calendarActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->modalWidth(Width::Medium)
                ->mutateDataUsing($this->computeAppointmentStart(...)),
            DeleteAction::make(),
            EditAction::make()
                ->slideOver()
                ->modalWidth(Width::Medium)
                ->fillForm(fn (Appointment $appointment) => [
                    ...$appointment->toArray(),
                    'color' => (string) $appointment->color,
                    'at' => $appointment->allDay ? null : $appointment->start->format('H:i'),
                ])
                ->mutateDataUsing($this->computeAppointmentStart(...)),
        ];
    }

    /**
     * @param  array{title: string, start: Carbon, at: string}  $data
     * @return array{title: string, start: Carbon, at: string}
     */
    protected function computeAppointmentStart(array $data): array
    {
        $data['start'] = Carbon::parse(
            sprintf('%s %s', $data['start'], $data['at'] ?? '00:00')
        );

        return $data;
    }

    protected function customCalendarActions(): array
    {
        return [Action::make('refresh')
            ->label('Refresh')
            ->tooltip('Refresh calendar events')
            ->action(fn () => $this->refreshEvents()),
        ];
    }
}
