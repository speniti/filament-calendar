<?php

declare(strict_types=1);

namespace App\Models;

use function assert;

use Carbon\Carbon;
use Database\Factories\AppointmentFactory;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $ulid
 * @property string $summary
 * @property ?string $notes
 * @property Carbon $start
 * @property ?string $color
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

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', static function (Builder $builder) {
            $tenant = Filament::getTenant();
            assert($tenant instanceof Model);

            $builder->whereBelongsTo($tenant);
        });
    }

    protected function allDay(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->start->isStartOfDay(),
        );
    }

    protected function casts(): array
    {
        return ['start' => 'datetime'];
    }
}
