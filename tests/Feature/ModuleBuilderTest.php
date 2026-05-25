<?php

namespace Tests\Feature;

use App\Livewire\Modules\Builder;
use App\Livewire\Modules\Records;
use App\Models\Module;
use App\Models\Role;
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

    public function test_builder_can_configure_relationships_conditions_columns_and_role_permissions(): void
    {
        $this->withoutGeneratedArtifacts();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $userRole = Role::query()->firstOrCreate(
            ['name' => User::ROLE_USER],
            ['label' => 'User']
        );
        $departmentModule = $this->generatedDepartmentsModule();

        Livewire::actingAs($admin->fresh())->test(Builder::class)
            ->set('name', 'Employees')
            ->set('tableName', 'employees')
            ->set('rolePermissions.'.$userRole->id.'.role_name', User::ROLE_USER)
            ->set('rolePermissions.'.$userRole->id.'.view', true)
            ->set('rolePermissions.'.$userRole->id.'.create', true)
            ->set('rolePermissions.'.$userRole->id.'.update', false)
            ->set('rolePermissions.'.$userRole->id.'.delete', false)
            ->set('fields', [
                [
                    ...$this->fieldPayload(),
                    'label' => 'Name',
                    'name' => 'name',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    ...$this->fieldPayload(1),
                    'label' => 'Department',
                    'name' => 'department_id',
                    'type' => 'relationship',
                    'relationship_module_id' => $departmentModule->id,
                    'relationship_display_field' => 'name',
                    'required' => true,
                ],
                [
                    ...$this->fieldPayload(2),
                    'label' => 'Employment Type',
                    'name' => 'employment_type',
                    'type' => 'select',
                    'required' => true,
                    'options_text' => "Full time\nContractor",
                    'sortable' => false,
                ],
                [
                    ...$this->fieldPayload(3),
                    'label' => 'Contract End',
                    'name' => 'contract_end',
                    'type' => 'date',
                    'required' => true,
                    'condition_field' => 'employment_type',
                    'condition_operator' => 'equals',
                    'condition_value' => 'Contractor',
                    'show_in_list' => false,
                    'searchable' => false,
                ],
            ])
            ->call('generate')
            ->assertHasNoErrors();

        $employeeModule = Module::query()->with('fields')->where('table_name', 'employees')->firstOrFail();

        $this->assertDatabaseHas('module_fields', [
            'module_id' => $employeeModule->id,
            'name' => 'department_id',
            'type' => 'relationship',
            'relationship_module_id' => $departmentModule->id,
            'relationship_display_field' => 'name',
        ]);
        $this->assertDatabaseHas('module_fields', [
            'module_id' => $employeeModule->id,
            'name' => 'contract_end',
            'condition_field' => 'employment_type',
            'condition_operator' => 'equals',
            'condition_value' => 'Contractor',
            'show_in_list' => false,
            'searchable' => false,
        ]);
        $this->assertTrue(Schema::hasColumn('employees', 'department_id'));
        $this->assertTrue(Schema::hasColumn('employees', 'contract_end'));

        $userRole->refresh()->load('permissions');
        $this->assertTrue($userRole->permissions->contains('name', 'employees.view'));
        $this->assertTrue($userRole->permissions->contains('name', 'employees.create'));
        $this->assertFalse($userRole->permissions->contains('name', 'employees.update'));
        $this->assertFalse($userRole->permissions->contains('name', 'employees.delete'));
    }

    public function test_records_support_relationship_fields_and_conditional_required_fields(): void
    {
        $this->withoutGeneratedArtifacts();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $departmentModule = $this->generatedDepartmentsModule();
        $departmentId = DB::table('departments')->insertGetId(['name' => 'Engineering']);
        $employeeModule = $this->generatedEmployeesWithRelationshipsModule($departmentModule);

        Livewire::actingAs($admin->fresh())->test(Records::class, ['module' => $employeeModule->table_name])
            ->call('create')
            ->set('form.name', 'Jane')
            ->set('form.department_id', $departmentId)
            ->set('form.employment_type', 'Full time')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'name' => 'Jane',
            'department_id' => $departmentId,
            'employment_type' => 'Full time',
            'contract_end' => null,
        ]);

        Livewire::actingAs($admin->fresh())->test(Records::class, ['module' => $employeeModule->table_name])
            ->call('create')
            ->set('form.name', 'Alex')
            ->set('form.department_id', $departmentId)
            ->set('form.employment_type', 'Contractor')
            ->call('save')
            ->assertHasErrors(['form.contract_end']);

        Livewire::actingAs($admin->fresh())->test(Records::class, ['module' => $employeeModule->table_name])
            ->call('create')
            ->set('form.name', 'Alex')
            ->set('form.department_id', $departmentId)
            ->set('form.employment_type', 'Contractor')
            ->set('form.contract_end', '2026-12-31')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($admin->fresh())->test(Records::class, ['module' => $employeeModule->table_name])
            ->assertSee('Engineering')
            ->assertDontSee('Contract End');
    }

    public function test_advanced_field_types_computed_values_and_snapshots_are_supported(): void
    {
        $this->withoutGeneratedArtifacts();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        Livewire::actingAs($admin->fresh())->test(Builder::class)
            ->set('name', 'Products')
            ->set('tableName', 'products')
            ->set('fields', [
                [
                    ...$this->fieldPayload(),
                    'label' => 'Name',
                    'name' => 'name',
                    'type' => 'text',
                    'required' => true,
                    'group_name' => 'General',
                ],
                [
                    ...$this->fieldPayload(1),
                    'label' => 'Quantity',
                    'name' => 'quantity',
                    'type' => 'number',
                    'required' => true,
                    'validation_config' => ['numeric' => true, 'min' => '1'],
                ],
                [
                    ...$this->fieldPayload(2),
                    'label' => 'Price',
                    'name' => 'price',
                    'type' => 'currency',
                    'required' => true,
                    'validation_config' => ['numeric' => true],
                ],
                [
                    ...$this->fieldPayload(3),
                    'label' => 'Total',
                    'name' => 'total',
                    'type' => 'currency',
                    'computed_config' => [
                        'expression' => 'quantity * price',
                        'mode' => 'math',
                        'persist' => true,
                        'readonly' => true,
                    ],
                ],
                [
                    ...$this->fieldPayload(4),
                    'label' => 'Brand Color',
                    'name' => 'brand_color',
                    'type' => 'color',
                    'filterable' => true,
                ],
                [
                    ...$this->fieldPayload(5),
                    'label' => 'Specs',
                    'name' => 'specs',
                    'type' => 'json',
                    'column_span' => 2,
                ],
                [
                    ...$this->fieldPayload(6),
                    'label' => 'Launch Window',
                    'name' => 'launch_window',
                    'type' => 'date_range',
                    'show_in_list' => true,
                ],
                [
                    ...$this->fieldPayload(7),
                    'label' => 'Description',
                    'name' => 'description',
                    'type' => 'rich_text',
                    'show_in_list' => false,
                ],
            ])
            ->call('generate')
            ->assertHasNoErrors();

        $module = Module::query()->with('fields')->where('table_name', 'products')->firstOrFail();

        $this->assertTrue(Schema::hasColumn('products', 'price'));
        $this->assertTrue(Schema::hasColumn('products', 'specs'));
        $this->assertTrue(Schema::hasColumn('products', 'launch_window'));
        $this->assertDatabaseHas('module_snapshots', [
            'module_id' => $module->id,
            'version' => 1,
        ]);

        Livewire::actingAs($admin->fresh())->test(Records::class, ['module' => 'products'])
            ->call('create')
            ->set('form.name', 'Desk')
            ->set('form.quantity', 3)
            ->set('form.price', 199.99)
            ->set('form.brand_color', '#2563eb')
            ->set('form.specs', '{"height":"72cm"}')
            ->set('form.launch_window.start', '2026-06-01')
            ->set('form.launch_window.end', '2026-06-30')
            ->set('form.description', '<p>Adjustable desk</p>')
            ->call('save')
            ->assertHasNoErrors();

        $product = DB::table('products')->where('name', 'Desk')->first();
        $this->assertSame('599.97', number_format((float) $product->total, 2, '.', ''));
        $this->assertSame(['start' => '2026-06-01', 'end' => '2026-06-30'], json_decode($product->launch_window, true));
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Created Products record',
            'subject_type' => Module::class,
            'subject_id' => $module->id,
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

    protected function generatedDepartmentsModule(): Module
    {
        $module = Module::query()->create([
            'name' => 'Departments',
            'table_name' => 'departments',
            'description' => 'Department records',
            'soft_deletes' => false,
            'has_timestamps' => false,
            'settings' => [
                'search' => true,
                'pagination' => 8,
                'sorting' => true,
                'filters' => true,
            ],
        ]);

        $module->fields()->create([
            ...$this->fieldModelPayload(),
            'label' => 'Name',
            'name' => 'name',
            'type' => 'text',
            'required' => true,
            'nullable' => false,
            'sort_order' => 0,
        ]);

        app(ModuleGenerator::class)->generate($module);

        return $module->fresh('fields');
    }

    protected function generatedEmployeesWithRelationshipsModule(Module $departmentModule): Module
    {
        $module = Module::query()->create([
            'name' => 'Employees',
            'table_name' => 'employees',
            'description' => 'Employee records',
            'soft_deletes' => false,
            'has_timestamps' => false,
            'settings' => [
                'search' => true,
                'pagination' => 8,
                'sorting' => true,
                'filters' => true,
            ],
        ]);

        $module->fields()->createMany([
            [
                ...$this->fieldModelPayload(),
                'label' => 'Name',
                'name' => 'name',
                'type' => 'text',
                'required' => true,
                'nullable' => false,
                'sort_order' => 0,
            ],
            [
                ...$this->fieldModelPayload(),
                'label' => 'Department',
                'name' => 'department_id',
                'type' => 'relationship',
                'relationship_module_id' => $departmentModule->id,
                'relationship_display_field' => 'name',
                'required' => true,
                'nullable' => false,
                'sort_order' => 1,
            ],
            [
                ...$this->fieldModelPayload(),
                'label' => 'Employment Type',
                'name' => 'employment_type',
                'type' => 'select',
                'required' => true,
                'nullable' => false,
                'options' => ['Full time', 'Contractor'],
                'sort_order' => 2,
            ],
            [
                ...$this->fieldModelPayload(),
                'label' => 'Contract End',
                'name' => 'contract_end',
                'type' => 'date',
                'required' => true,
                'nullable' => true,
                'condition_field' => 'employment_type',
                'condition_operator' => 'equals',
                'condition_value' => 'Contractor',
                'show_in_list' => false,
                'searchable' => false,
                'sort_order' => 3,
            ],
        ]);

        app(ModuleGenerator::class)->generate($module);

        return $module->fresh('fields');
    }

    protected function fieldPayload(int $sortOrder = 0): array
    {
        return [
            'label' => '',
            'name' => '',
            'type' => 'text',
            'relationship_module_id' => '',
            'relationship_display_field' => '',
            'relationship_type' => 'belongs_to',
            'required' => false,
            'unique' => false,
            'default_value' => '',
            'validation_rules' => '',
            'validation_config' => [
                'min' => '',
                'max' => '',
                'regex' => '',
                'email' => false,
                'numeric' => false,
                'file_mimes' => '',
                'max_file_size' => '',
                'custom' => '',
            ],
            'placeholder' => '',
            'options_text' => '',
            'condition_field' => '',
            'condition_operator' => '',
            'condition_value' => '',
            'condition_config' => [
                'boolean' => 'and',
                'rules' => [],
                'groups' => [],
            ],
            'computed_config' => [
                'expression' => '',
                'mode' => 'template',
                'persist' => true,
                'readonly' => true,
            ],
            'show_in_list' => true,
            'visible_in_form' => true,
            'searchable' => true,
            'filterable' => false,
            'sortable' => true,
            'group_name' => '',
            'group_type' => 'section',
            'column_span' => 1,
            'sort_order' => $sortOrder,
        ];
    }

    protected function fieldModelPayload(): array
    {
        return [
            'required' => false,
            'nullable' => true,
            'unique' => false,
            'default_value' => null,
            'validation_rules' => null,
            'placeholder' => null,
            'options' => null,
            'show_in_list' => true,
            'visible_in_form' => true,
            'searchable' => true,
            'filterable' => false,
            'sortable' => true,
            'relationship_type' => 'belongs_to',
            'validation_config' => null,
            'condition_config' => null,
            'computed_config' => null,
            'group_type' => 'section',
            'column_span' => 1,
        ];
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
