<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use Database\Factories\AppointmentFactory;
use Database\Factories\UserFactory;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::factory()->create();

        UserFactory::new()
            ->hasAttached($tenant)
            ->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        AppointmentFactory::new()
            ->for($tenant)
            ->count(100)
            ->create();
    }
}
