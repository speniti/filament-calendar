<?php

declare(strict_types=1);

use App\Filament\Widgets\Calendar;
use App\Models\User;
use Filament\Pages\Dashboard;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

describe(Calendar::class, static function () {
    it('is visible', function () {
        actingAs(User::factory()->create());

        Livewire::withoutLazyLoading()
            ->test(Dashboard::class)
            ->assertSeeLivewire(Calendar::class);
    });
});
