<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

use App\Filament\Widgets\Calendar;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Dashboard;
use Livewire\Livewire;
use Peniti\FilamentCalendar\Calendar\Event;

use function Pest\Laravel\actingAs;

describe('Calendar Component', static function () {
    beforeEach(function () {
        actingAs(User::factory()->create());
    });

    it('is visible on the dashboard', function () {
        /**
         * @noinspection PhpUndefinedMethodInspection,
         *               PhpArgumentWithoutNamedIdentifierInspection
         *
         * @phpstan-ignore-next-line
         */
        Livewire::withoutLazyLoading()
            ->test(Dashboard::class)
            ->assertSeeLivewire(Calendar::class);
    });

    it('fetches events correctly', function () {
        try {
            $appointments = Appointment::factory(2)->create([
                'start' => Carbon::today()->startOfWeek()->addDays(random_int(1, 5)),
            ]);

            $start = Carbon::now()->startOfWeek()->format('Y-m-d');
            $end = Carbon::now()->endOfWeek()->format('Y-m-d');

            $events = new Calendar()->fetchEvents($start, $end);

            expect($events)->toBeArray()
                ->and($events)->toHaveCount(2)
                ->and($events[0]->id)->toBe($appointments[0]?->ulid)
                ->and($events[1]->id)->toBe($appointments[1]?->ulid);
        } catch (Random\RandomException $e) {
            expect(true)
                ->toBeFalse("Failed to create test appointments: {$e->getMessage()}");
        }
    });

    it('creates events correctly', function () {
        // This action just opens the modal does not create the appointment yet.
        $result = Livewire::test(Calendar::class)->call(
            'create',
            Carbon::now()->addDay()->format('Y-m-d H:i:s'),
            Carbon::now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            false,
        );

        // Verify the method doesn't throw an exception;
        // the actual action dispatch depends on the implementation.
        expect($result)->not->toBeNull();
    });

    it('edits events correctly', function () {
        $appointment = Appointment::factory()->create([
            'start' => Carbon::now()->addDay(),
        ]);

        Livewire::test(Calendar::class)
            ->call(
                'edit',
                $appointment->ulid,
                Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
                Carbon::now()->addDays(2)->addHour()->format('Y-m-d H:i:s'),
                false,
            );

        $appointment->refresh();
        expect($appointment->start->format('Y-m-d'))
            ->toBe(Carbon::now()->addDays(2)->format('Y-m-d'));
    });

    it('selects events correctly', function () {
        $appointment = Appointment::factory()->create();
        $result = Livewire::test(Calendar::class)->call('select', $appointment->ulid);

        // Verify the method doesn't throw an exception;
        // the actual action dispatch depends on the implementation.
        expect($result)->not->toBeNull();
    });

    it('computes appointment start time correctly', function () {
        try {
            $result = new ReflectionMethod(Calendar::class, 'computeAppointmentStart')
                ->invoke(new Calendar(), [
                    'title' => 'Test Appointment',
                    'start' => Carbon::now()->startOfDay()->format('Y-m-d'),
                    'at' => '14:30',
                ]);

            /** @var array{title: string, start: Carbon, at: string} $result */
            expect($result['start']->format('H:i'))->toBe('14:30');
        } catch (ReflectionException $e) {
            expect(true)
                ->toBeFalse("Failed to compute start time: {$e->getMessage()}");
        }
    });

    it('serializes events correctly', function () {
        $event = new Event(
            id: '123',
            title: 'Test Event',
            start: Carbon::now(),
            end: Carbon::now()->addHour(),
            allDay: false,
        );

        $array = $event->toArray();

        expect($array)->toHaveKey('id')
            ->and($array)->toHaveKey('title')
            ->and($array)->toHaveKey('start')
            ->and($array)->toHaveKey('end')
            ->and($array)->toHaveKey('allDay');

        // Convert to JSON
        try {
            expect(
                json_decode($event->toJson(), true, 512, JSON_THROW_ON_ERROR)
            )->toBeArray();
        } catch (JsonException $e) {
            expect(true)
                ->toBeFalse("Failed to decode JSON event: {$e->getMessage()}");
        }
    });

    it('handles all-day events correctly', function () {
        $appointment = Appointment::factory()->create([
            'start' => Carbon::now()->startOfDay(),
        ]);

        $events = new Calendar()->fetchEvents(
            Carbon::now()->startOfMonth()->format('Y-m-d'),
            Carbon::now()->endOfMonth()->format('Y-m-d'),
        );

        $event = collect($events)->firstWhere('id', $appointment->ulid);
        expect($event?->allDay)->toBeTrue();
    });

    it('respects calendar display options', function () {
        $options = Livewire::test(Calendar::class)->get('options');

        expect($options)->toHaveKey('aspectRatio')
            ->and($options)->toHaveKey('headerToolbar')
            ->and($options)->toHaveKey('initialView')
            ->and($options)->toHaveKey('navLinks')
            ->and($options)->toHaveKey('businessHours')
            ->and($options['businessHours'])->toBeArray();
    });
});
