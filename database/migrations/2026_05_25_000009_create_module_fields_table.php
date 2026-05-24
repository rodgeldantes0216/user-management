<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('name');
            $table->string('type')->default('text');
            $table->boolean('required')->default(false);
            $table->boolean('nullable')->default(true);
            $table->boolean('unique')->default(false);
            $table->text('default_value')->nullable();
            $table->text('validation_rules')->nullable();
            $table->string('placeholder')->nullable();
            $table->json('options')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['module_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_fields');
    }
};
