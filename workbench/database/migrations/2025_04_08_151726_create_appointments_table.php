<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', static function (Blueprint $table) {
            $table->ulid()->primary();
            $table->string('summary');
            $table->text('notes')->nullable();
            $table->timestamp('start');
            $table->string('color')->nullable();
        });
    }
};
