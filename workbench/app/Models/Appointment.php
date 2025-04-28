<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\Rgb;
use Carbon\Carbon;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Color\Rgb as Color;

/**
 * @property string $ulid
 * @property string $summary
 * @property ?string $notes
 * @property Carbon $start
 * @property ?Color $color
 * @property bool $allDay
 */
final class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    use HasUlids;

    public $timestamps = false;

    protected $fillable = ['summary', 'color', 'start', 'notes'];

    protected $primaryKey = 'ulid';

    protected function allDay(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->start->isStartOfDay(),
        );
    }

    protected function casts(): array
    {
        return [
            'color' => Rgb::class,
            'start' => 'datetime',
        ];
    }
}
