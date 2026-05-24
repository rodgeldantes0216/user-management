<?php

namespace Tests\Feature;

use App\Livewire\Modules\Builder;
use App\Livewire\Modules\Records;
use App\Models\Module;
use App\Models\User;
use App\Services\ModuleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ModuleBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_module_builder(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('modules.builder'))
            ->assertOk();
    }

    public function test_regular_user_cannot_access_module_builder(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($user)
            ->get(route('modules.builder'))
            ->assertForbidden();
    }

    public function test_admin_can_generate_module_metadata_permissions_and_table(): void
    {
        $this->withoutGeneratedArtifacts();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        Livewire::actingAs($admin)->test(Builder::class)
            ->set('name', 'Employees')
            ->set('tableName', 'employees')
            ->set('description', 'Employee records')
            ->set('softDeletes', true)
            ->set('hasTimestamps', true)
            ->set('fields', [
                [
                    'label' => 'First Name',
                    'name' => 'first_name',
                    'type' => 'text',
                    'required' => true,
                    'unique' => false,
                    'default_value' => '',
                    'validation_rules' => 'min:2',
                    'placeholder' => 'Jane',
                    'options_text' => '',
                    'sort_order' => 0,
                ],
                [
                    'label' => 'Work Email',
                    'name' => 'work_email',
                    'type' => 'email',
                    'required' => true,
                    'unique' => true,
                    'default_value' => '',
                    'validation_rules' => '',
                    'placeholder' => 'jane@example.com',
                    'options_text' => '',
                    'sort_order' => 1,
                ],
            ])
            ->call('generate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('modules', [
            'name' => 'Employees',
            'table_name' => 'employees',
            'soft_deletes' => true,
            'has_timestamps' => true,
        ]);
        $this->assertDatabaseHas('module_fields', [
            'label' => 'First Name',
            'name' => 'first_name',
            'type' => 'text',
            'required' => true,
        ]);
        $this->assertDatabaseHas('permissions', ['name' => 'employees.view']);
        $this->assertDatabaseHas('permissions', ['name' => 'employees.create']);
        $this->assertTrue(Schema::hasTable('employees'));
        $this->assertTrue(Schema::hasColumn('employees', 'first_name'));
        $this->assertTrue(Schema::hasColumn('employees', 'work_email'));
        $this->assertTrue(Schema::hasColumn('employees', 'deleted_at'));
    }

    public function test_generated_module_records_can_be_created_updated_and_deleted(): void
    {
        $this->withoutGeneratedArtifacts();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $module = $this->generatedEmployeesModule();

        Livewire::actingAs($admin->fresh())->test(Records::class, ['module' => $module->table_name])
            ->call('create')
            ->set('form.first_name', 'Jane')
            ->set('form.work_email', 'jane@example.com')
            ->set('form.department', 'Engineering')
            ->call('save')
            ->assertHasNoErrors();

        $recordId = DB::table('employees')->where('work_email', 'jane@example.com')->value('id');

        $this->assertNotNull($recordId);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Created Employees record',
            'subject_type' => Module::class,
            'subject_id' => $module->id,
        ]);

        Livewire::actingAs($admin->fresh())->test(Records::class, ['module' => $module->table_name])
            ->call('edit', $recordId)
            ->set('form.first_name', 'Janet')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'id' => $recordId,
            'first_name' => 'Janet',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Updated Employees record',
            'subject_type' => Module::class,
            'subject_id' => $module->id,
        ]);

        Livewire::actingAs($admin->fresh())->test(Records::class, ['module' => $module->table_name])
            ->call('confirmDelete', $recordId)
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertNotNull(DB::table('employees')->where('id', $recordId)->value('deleted_at'));
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Deleted Employees record',
            'subject_type' => Module::class,
            'subject_id' => $module->id,
        ]);
    }

    public function test_generated_module_save_does_not_notify_non_recipients(): void
    {
        $this->withoutGeneratedArtifacts();

        User::factory()->count(25)->create([
            'role' => User::ROLE_USER,
        ]);
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $module = $this->generatedEmployeesModule();

        Livewire::actingAs($admin->fresh())->test(Records::class, ['module' => $module->table_name])
            ->call('create')
            ->set('form.first_name', 'Alex')
            ->set('form.work_email', 'alex@example.com')
            ->set('form.department', 'Engineering')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('app_notifications', 1);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $admin->id,
            'module_key' => 'employees',
            'title' => 'Employees changed',
        ]);
    }

    public function test_module_builder_rejects_duplicate_sanitized_field_names(): void
    {
        $this->withoutGeneratedArtifacts();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        Livewire::actingAs($admin)->test(Builder::class)
            ->set('name', 'Employees')
            ->set('tableName', 'employees')
            ->set('fields', [
                [
                    'label' => 'First Name',
                    'name' => 'first_name',
                    'type' => 'text',
                    'required' => true,
                    'unique' => false,
                    'default_value' => '',
                    'validation_rules' => '',
                    'placeholder' => '',
                    'options_text' => '',
                    'sort_order' => 0,
                ],
                [
                    'label' => 'First Name',
                    'name' => 'first_name',
                    'type' => 'text',
                    'required' => true,
                    'unique' => false,
                    'default_value' => '',
                    'validation_rules' => '',
                    'placeholder' => '',
                    'options_text' => '',
                    'sort_order' => 1,
                ],
            ])
            ->call('generate')
            ->assertHasErrors(['fields']);

        $this->assertFalse(Schema::hasTable('employees'));
    }

    protected function generatedEmployeesModule(): Module
    {
        $module = Module::query()->create([
            'name' => 'Employees',
            'table_name' => 'employees',
            'description' => 'Employee records',
            'soft_deletes' => true,
            'has_timestamps' => true,
            'settings' => [
                'search' => true,
                'pagination' => 8,
                'sorting' => true,
                'filters' => true,
            ],
        ]);

        $module->fields()->createMany([
            [
                'label' => 'First Name',
                'name' => 'first_name',
                'type' => 'text',
                'required' => true,
                'nullable' => false,
                'unique' => false,
                'sort_order' => 0,
            ],
            [
                'label' => 'Work Email',
                'name' => 'work_email',
                'type' => 'email',
                'required' => true,
                'nullable' => false,
                'unique' => true,
                'sort_order' => 1,
            ],
            [
                'label' => 'Department',
                'name' => 'department',
                'type' => 'select',
                'required' => true,
                'nullable' => false,
                'unique' => false,
                'options' => ['Engineering', 'Finance'],
                'sort_order' => 2,
            ],
        ]);

        app(ModuleGenerator::class)->generate($module);

        return $module->fresh('fields');
    }

    protected function withoutGeneratedArtifacts(): void
    {
        $this->app->bind(ModuleGenerator::class, fn () => new class extends ModuleGenerator
        {
            protected function writeMigrationFile(Module $module): void
            {
                //
            }

            protected function writeModelFile(Module $module): void
            {
                //
            }
        });
    }
}
