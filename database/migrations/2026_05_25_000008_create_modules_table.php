<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('table_name')->unique();
            $table->string('icon')->default('square-stack');
            $table->text('description')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('soft_deletes')->default(false);
            $table->boolean('has_timestamps')->default(true);
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
