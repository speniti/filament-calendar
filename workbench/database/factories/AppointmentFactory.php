<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Appointment;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Spatie\Color\Rgb;

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

        $colors = FilamentColor::getColors();

        /** @var string $key */
        $key = $this->faker->randomElement(array_keys($colors));

        /** @var string $rgb */
        $rgb = Arr::get($colors, "$key.500");

        return [
            'summary' => $this->faker->paragraph(1),
            'notes' => $this->faker->text(),
            'start' => $start,
            'color' => Rgb::fromString("rgb($rgb)"),
        ];
    }
}
