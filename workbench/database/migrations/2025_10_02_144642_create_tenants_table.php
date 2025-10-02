<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }

    public function up(): void
    {
        Schema::create('tenants', static function (Blueprint $table) {
            $table->uuid()->primary();
            $table->string('name');
            $table->timestamps();
        });
    }
};
