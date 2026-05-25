<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_fields', function (Blueprint $table) {
            $table->string('relationship_type')->default('belongs_to')->after('relationship_display_field');
            $table->json('validation_config')->nullable()->after('validation_rules');
            $table->json('condition_config')->nullable()->after('condition_value');
            $table->json('computed_config')->nullable()->after('condition_config');
            $table->boolean('visible_in_form')->default(true)->after('show_in_list');
            $table->boolean('filterable')->default(false)->after('searchable');
            $table->string('group_name')->nullable()->after('sortable');
            $table->string('group_type')->default('section')->after('group_name');
            $table->unsignedTinyInteger('column_span')->default(1)->after('group_type');
        });

        Schema::create('module_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('label')->nullable();
            $table->json('schema');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['module_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_snapshots');

        Schema::table('module_fields', function (Blueprint $table) {
            $table->dropColumn([
                'relationship_type',
                'validation_config',
                'condition_config',
                'computed_config',
                'visible_in_form',
                'filterable',
                'group_name',
                'group_type',
                'column_span',
            ]);
        });
    }
};
