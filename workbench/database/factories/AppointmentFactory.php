<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Appointment;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Config;

/**
 * @template TModel of Appointment
 *
 * @extends Factory<TModel>
 */
final class AppointmentFactory extends Factory
{
    /** @var class-string<TModel> */
    protected $model = Appointment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $start = fake()->dateTimeThisMonth('+2 week', Config::get('app.timezone'));

        if (fake()->boolean()) {
            $start->setTime(0, 0);
        }

        /** @var string $color */
        $color = $this->faker->randomElement(
            array_keys(FilamentColor::getColors())
        );

        return [
            'tenant_uuid' => Filament::getTenant(),
            'summary' => $this->faker->paragraph(1),
            'notes' => $this->faker->text(),
            'start' => $start,
            'color' => $color,
        ];
    }
}
