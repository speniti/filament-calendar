<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

final class AppointmentPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return true;
    }

    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return true;
    }

    public function restore(User $user, Appointment $appointment): bool
    {
        return true;
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return true;
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
