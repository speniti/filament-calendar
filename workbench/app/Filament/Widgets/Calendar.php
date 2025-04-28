<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\AppointmentResource;
use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\MaxWidth;
use Peniti\FilamentCalendar\Calendar\Event;
use Peniti\FilamentCalendar\Widgets\Calendar as BaseCalendar;

final class Calendar extends BaseCalendar
{
    public array $options = [
        'aspectRatio' => '1.8',
        'headerToolbar' => [
            'right' => 'prev,next today',
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
                return new Event(
                    id: $appointment->ulid,
                    title: $appointment->summary,
                    start: $appointment->start,
                    allDay: $appointment->allDay,
                    backgroundColor: $appointment->color,
                    startEditable: true,
                );
            })
            ->all();
    }

    protected function calendarActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->modalWidth(MaxWidth::Medium)
                ->mutateFormDataUsing($this->computeAppointmentStart(...)),
            DeleteAction::make(),
            EditAction::make()
                ->slideOver()
                ->modalWidth(MaxWidth::Medium)
                ->fillForm(fn (Appointment $appointment) => [
                    ...$appointment->toArray(),
                    'color' => (string) $appointment->color,
                    'at' => $appointment->allDay ? null : $appointment->start->format('H:i'),
                ])
                ->mutateFormDataUsing($this->computeAppointmentStart(...)),
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
}
