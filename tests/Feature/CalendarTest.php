<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

use App\Filament\Resources\AppointmentResource;
use App\Filament\Widgets\Calendar;
use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Livewire;
use Peniti\FilamentCalendar\Calendar\Event;
use Peniti\FilamentCalendar\Widgets\Calendar as BaseCalendar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

describe('Calendar Component', function () {
    beforeEach(function () {
        actingAs(User::factory()->create());
        Filament::setTenant(Tenant::factory()->create());
    });

    it('is visible on the dashboard', function () {
        /**
         * @noinspection PhpUndefinedMethodInspection,
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
        Livewire::test(Calendar::class)
            ->call(
                'create',
                Carbon::now()->addDay()->format('Y-m-d H:i:s'),
                Carbon::now()->addDay()->addHour()->format('Y-m-d H:i:s'),
                false,
            )
            ->assertActionMounted(CreateAction::class)
            ->fillForm(['summary' => 'Test Appointment'])
            ->callMountedAction()
            ->assertHasNoFormErrors();

        assertDatabaseHas(Appointment::class, ['summary' => 'Test Appointment']);
    });

    it('edits events correctly', function () {
        $appointment = Appointment::factory()->create([
            'start' => Carbon::now()->addDay(),
        ]);

        $testable = Livewire::test(Calendar::class)
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

        $testable->call('select', $appointment->ulid)
            ->assertActionMounted(EditAction::class)
            ->fillForm([
                'summary' => 'Updated Appointment',
                'color' => null,
            ])
            ->callMountedAction()
            ->assertHasNoFormErrors();

        $appointment->refresh();

        expect($appointment->summary)->toBe('Updated Appointment');
    });

    it('deletes events correctly', function () {
        $appointment = Appointment::factory()->create();

        Livewire::test(Calendar::class)
            ->call('select', $appointment->ulid)
            ->assertActionMounted(EditAction::class)
            ->callAction(DeleteAction::class);

        assertDatabaseMissing(Appointment::class, ['ulid' => $appointment->ulid]);
    });

    it('selects events correctly', function () {
        $appointment = Appointment::factory()->create();
        $result = Livewire::test(Calendar::class)
            ->call('select', $appointment->ulid)
            ->assertActionMounted('edit');

        // Verify the method doesn't throw an exception;
        // the actual action dispatch depends on the implementation.
        expect($result)->not->toBeNull();

        $result = Livewire::test(
            new class extends Calendar
            {
                protected function calendarActions(): array
                {
                    return [
                        ...parent::calendarActions(),
                        ViewAction::make(),
                    ];
                }
            },
        )
            ->call('select', $appointment->ulid)
            ->assertActionMounted('view');

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
                json_decode($event->toJson(), true, 512, JSON_THROW_ON_ERROR),
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

        /** @var Event $event */
        $event = collect($events)->firstWhere('id', $appointment->ulid);
        expect($event->allDay)->toBeTrue();
    });

    it('respects calendar display options', function () {
        $options = Livewire::test(Calendar::class)->get('options');

        expect($options)->toHaveKey('aspectRatio')
            ->and($options)->toHaveKey('headerToolbar')
            ->and($options)->toHaveKey('initialView')
            ->and($options)->toHaveKey('navLinks')
            ->and($options)->toHaveKey('businessHours')
            ->and(data_get($options, 'businessHours'))->toBeArray();
    });

    it('can render a placeholder', function () {
        $calendar = new class extends BaseCalendar
        {
            public function fetchEvents(string $start, string $end): array
            {
                return [];
            }
        };

        $view = $calendar->placeholder();

        expect($view)->toBeInstanceOf(View::class)
            ->and($view->getName())->toBe('filament-calendar::widgets.loading-section');
    });

    it('does not have business hours configured by default', function () {
        $calendar = new class extends BaseCalendar
        {
            public function fetchEvents(string $start, string $end): array
            {
                return [];
            }
        };
        $options = Livewire::test($calendar)->get('options');

        expect($calendar->getBusinessHours())->toBeNull()
            ->and(data_get($options, 'slotMinTime'))->toBeNull()
            ->and(data_get($options, 'slotMaxTime'))->toBeNull();
    });

    it('returns an empty schema if no resource is configured', function () {
        $calendar = new class extends BaseCalendar
        {
            public function fetchEvents(string $start, string $end): array
            {
                return [];
            }
        };

        expect($calendar->schema($schema = new Schema()))->toBe($schema);
    });

    it('throws an exception if the create action is not configured', function () {
        new class extends BaseCalendar
        {
            public function fetchEvents(string $start, string $end): array
            {
                return [];
            }
        }->create('2024-12-05', null, true);
    })->throws(BadMethodCallException::class, 'No create action defined for this calendar.');

    it('throws an exception when an event is selected if the edit or view actions have not been configured', function () {
        $appointment = Appointment::factory()->create([
            'start' => Carbon::now()->startOfDay(),
        ]);

        new class extends BaseCalendar
        {
            protected static string $resource = AppointmentResource::class;

            public function fetchEvents(string $start, string $end): array
            {
                return [];
            }
        }->select($appointment->ulid);
    })->throws(BadMethodCallException::class, 'No view or edit action defined for this calendar.');

    it('throws an exception when an event is selected and the resource has not been configured', function () {
        new class extends BaseCalendar
        {
            public function fetchEvents(string $start, string $end): array
            {
                return [];
            }
        }->select(mb_strtolower((string) Str::ulid()));
    })->throws(BadMethodCallException::class, 'No resource defined for this calendar.');

    it("throws an exception when an event is selected if the record can't be found", function () {
        new class extends BaseCalendar
        {
            protected static string $resource = AppointmentResource::class;

            public function fetchEvents(string $start, string $end): array
            {
                return [];
            }
        }->select(mb_strtolower((string) Str::ulid()));
    })->throws(ModelNotFoundException::class, 'Record not found.');

    it('throws an exception if action are not extending Filament\Actions\Action', function () {
        $calendar = new class extends BaseCalendar
        {
            protected static string $resource = AppointmentResource::class;

            public function fetchEvents(string $start, string $end): array
            {
                return [];
            }

            protected function calendarActions(): array
            {
                /** @phpstan-ignore-next-line */
                return [new class {}];
            }
        };

        Livewire::test($calendar);
    })->throws(Exception::class, sprintf('Calendar actions must be an instance of %s.', Action::class));

    it('throws an exception if custom actions are specified using the wrong method', function () {
        $calendar = new class extends BaseCalendar
        {
            protected static string $resource = AppointmentResource::class;

            public function fetchEvents(string $start, string $end): array
            {
                return [];
            }

            protected function calendarActions(): array
            {
                return [Action::make('link')->url('https://example.com')];
            }
        };

        Livewire::test($calendar);
    })->throws(Exception::class, sprintf(
        'Calendar actions must be an instance of %s, %s, %s, or %s. '.
        'If you want to add custom actions, you can override the %s method.',
        CreateAction::class,
        EditAction::class,
        DeleteAction::class,
        ViewAction::class,
        BaseCalendar::class.'::customCalendarActions',
    ));

    it('supports custom actions', function () {
        Livewire::test(Calendar::class)
            ->assertActionExists('refresh')
            ->callAction('refresh')
            ->assertDispatched('filament-calendar--refresh');
    });

    it('renders custom actions', function () {
        $options = Livewire::test(Calendar::class)->get('options');
        $customButtons = data_get($options, 'customButtons');

        expect($options)->toBeArray()
            ->and($options)->toHaveKey('customButtons')
            ->and($customButtons)->toBeArray()
            ->and($customButtons)->toHaveKey('refresh');
    });
});
