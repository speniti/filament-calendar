<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }

    public function up(): void
    {
        Schema::create('staff', static function (Blueprint $table) {
            $table->foreignIdFor(Tenant::class);
            $table->foreignIdFor(User::class);

            $table->primary(['tenant_uuid', 'user_id']);
        });
    }
};
