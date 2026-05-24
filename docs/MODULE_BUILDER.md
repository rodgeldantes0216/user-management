# CRUD Maker / Module Builder

The Module Builder lets admins define simple CRUD modules from the UI. A generated module stores metadata in `modules` and `module_fields`, creates a physical database table, syncs permissions, and serves records through a reusable Livewire CRUD runtime.

## Folder Structure

```text
app/
  Livewire/Modules/
    Builder.php
    Records.php
  Models/
    Module.php
    ModuleField.php
    Generated/
  Services/
    ModuleGenerator.php
  Support/
    ModuleNameGuard.php

database/migrations/
  *_create_modules_table.php
  *_create_module_fields_table.php
  *_create_<dynamic>_table.php

resources/views/livewire/modules/
  builder.blade.php
  records.blade.php
```

## Database Tables

`modules` stores the module definition:

```php
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
```

`module_fields` stores form and column definitions:

```php
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
```

## Step By Step

1. Run migrations.

```bash
php artisan migrate
```

2. Make sure the admin role has the new builder permissions. On a fresh database this happens through `DatabaseSeeder`.

```bash
php artisan db:seed
```

3. Open `Module Builder` in the sidebar.

4. Add a module.

```text
Module Name: Employees
Table Name: employees
Soft Deletes: enabled or disabled
Timestamps: enabled or disabled
```

5. Add fields.

```text
Label: First Name
Name: first_name
Type: text
Required: true
Unique: false
Validation: min:2|max:80
```

6. Click `Generate module`.

The module becomes available at `/modules/employees`.

## Livewire Runtime

`App\Livewire\Modules\Builder` owns module creation, field ordering, JSON import/export, and regeneration.

`App\Livewire\Modules\Records` owns every generated CRUD page. It reads the module metadata and provides:

- create/edit/delete modals
- dynamic validation
- search across text-like fields
- pagination
- sortable table columns
- filters for select, radio, checkbox, and toggle fields
- file and image uploads to the public disk
- soft delete support when enabled
- audit logs through `Activity::log()`
- notifications through `NotificationCenter`
- permission checks for `view`, `create`, `update`, and `delete`

## Generator Logic

`ModuleGenerator` performs the core generation:

```php
$module->load('fields');
ModuleNameGuard::assertIdentifier($module->table_name, 'table_name');

if ($module->fields->isEmpty()) {
    throw ValidationException::withMessages([
        'fields' => 'Add at least one field before generating the module.',
    ]);
}

$this->syncPermissions($module);

if (! Schema::hasTable($module->table_name)) {
    $this->createRuntimeTable($module);
}

$this->writeMigrationFile($module);
$this->writeModelFile($module);

$module->forceFill(['generated_at' => now()])->save();
```

The generator creates the database table immediately and writes traceable artifacts:

- migration artifact under `database/migrations`
- model artifact under `app/Models/Generated`

The route is generic, so generated modules do not require route-file edits:

```php
Route::get('/modules/{module}', ModuleRecords::class)->name('modules.records');
```

## Security Rules

Identifiers are sanitized through `ModuleNameGuard`:

- table names and field names must start with a letter
- only lowercase letters, numbers, and underscores are allowed
- reserved SQL-ish keywords are blocked
- duplicate tables are blocked
- duplicate sanitized field names are blocked
- builder access requires an admin user with `modules.view`
- generated CRUD access requires the module permission being used

## Regeneration

`Regenerate` safely re-syncs permissions and rewrites generated migration/model artifacts. It does not drop or rebuild existing tables, because that could destroy production data. Additive schema updates should be handled through a future schema-diff action that only adds missing nullable columns or asks for explicit confirmation.

## Import / Export

The builder can export a module definition as JSON and load it back into the form. This exports metadata only. It does not export table records.

## Best Practices

- Treat generated modules as admin/internal tools, not public forms.
- Prefer simple fields first. Add relationships and advanced field types as explicit future features.
- Keep validation rules conservative and test them before entering production data.
- Use soft deletes for business records that should be recoverable.
- Run generated migrations into version control so production deployments remain reproducible.
- Avoid changing field names after records exist unless a schema-diff migration is also planned.
