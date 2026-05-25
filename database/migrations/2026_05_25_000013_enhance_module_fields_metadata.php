<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_fields', function (Blueprint $table) {
            $table->foreignId('relationship_module_id')->nullable()->after('type')->constrained('modules')->nullOnDelete();
            $table->string('relationship_display_field')->nullable()->after('relationship_module_id');
            $table->string('condition_field')->nullable()->after('options');
            $table->string('condition_operator')->nullable()->after('condition_field');
            $table->text('condition_value')->nullable()->after('condition_operator');
            $table->boolean('show_in_list')->default(true)->after('condition_value');
            $table->boolean('searchable')->default(true)->after('show_in_list');
            $table->boolean('sortable')->default(true)->after('searchable');
        });
    }

    public function down(): void
    {
        Schema::table('module_fields', function (Blueprint $table) {
            $table->dropForeign(['relationship_module_id']);
            $table->dropColumn([
                'relationship_module_id',
                'relationship_display_field',
                'condition_field',
                'condition_operator',
                'condition_value',
                'show_in_list',
                'searchable',
                'sortable',
            ]);
        });
    }
};
