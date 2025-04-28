<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Spatie\Color\Rgb as Color;

/** @implements CastsAttributes<Color, Color> */
final class Rgb implements CastsAttributes
{
    /** @param  array<string, mixed>  $attributes */
    public function get(Model $model, string $key, mixed $value, array $attributes): Color
    {
        assert(is_string($value));

        $color = Color::fromString($value);
        assert($color instanceof Color);

        return $color;
    }

    /** @param  array<string, mixed>  $attributes  */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        assert($value instanceof Color || is_string($value));

        // Casting to color to ensure a consistent format.
        if (is_string($value)) {
            $value = Color::fromString($value);
        }

        /** @var Color $value */
        return (string) $value;
    }
}
