<?php

namespace App\Services;

use App\Models\Module;
use App\Models\ModuleField;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\ModuleNameGuard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ModuleGenerator
{
    public function generate(Module $module): void
    {
        $module->load('fields');
        ModuleNameGuard::assertIdentifier($module->table_name, 'table_name');

        if ($module->fields->isEmpty()) {
            throw ValidationException::withMessages([
                'fields' => 'Add at least one field before generating the module.',
            ]);
        }

        if (Schema::hasTable($module->table_name) && ! $module->generated_at) {
            throw ValidationException::withMessages([
                'table_name' => 'A database table with this name already exists.',
            ]);
        }

        $this->syncPermissions($module);

        if (! Schema::hasTable($module->table_name)) {
            $this->createRuntimeTable($module);
        }

        $this->writeMigrationFile($module);
        $this->writeModelFile($module);

        $module->forceFill(['generated_at' => now()])->save();
    }

    public function export(Module $module): array
    {
        $module->load('fields');

        return [
            'module' => $module->only(['name', 'table_name', 'icon', 'description', 'settings', 'soft_deletes', 'has_timestamps']),
            'fields' => $module->fields
                ->map(fn (ModuleField $field) => $field->only([
                    'label',
                    'name',
                    'type',
                    'required',
                    'nullable',
                    'unique',
                    'default_value',
                    'validation_rules',
                    'placeholder',
                    'options',
                    'sort_order',
                ]))
                ->values()
                ->all(),
        ];
    }

    protected function createRuntimeTable(Module $module): void
    {
        Schema::create($module->table_name, function (Blueprint $table) use ($module) {
            $table->id();

            foreach ($module->fields as $field) {
                $column = $this->column($table, $field);

                if ($field->nullable && ! $field->required) {
                    $column->nullable();
                }

                if ($field->unique) {
                    $column->unique();
                }

                if ($field->default_value !== null && ! in_array($field->type, ['file', 'image', 'password'], true)) {
                    $column->default($field->default_value);
                }
            }

            if ($module->soft_deletes) {
                $table->softDeletes();
            }

            if ($module->has_timestamps) {
                $table->timestamps();
            }
        });
    }

    protected function column(Blueprint $table, ModuleField $field): mixed
    {
        return match ($field->type) {
            'textarea' => $table->text($field->name),
            'number' => $table->decimal($field->name, 12, 2),
            'email', 'password', 'select', 'radio', 'file', 'image' => $table->string($field->name),
            'checkbox', 'toggle' => $table->boolean($field->name)->default((bool) $field->default_value),
            'date' => $table->date($field->name),
            'datetime' => $table->dateTime($field->name),
            default => $table->string($field->name),
        };
    }

    protected function syncPermissions(Module $module): void
    {
        foreach (['view', 'create', 'update', 'delete'] as $ability) {
            Permission::query()->firstOrCreate(
                ['name' => $module->permissionName($ability)],
                [
                    'label' => Str::of($module->permissionName($ability))->replace('.', ' ')->title()->toString(),
                    'group' => $module->table_name,
                ],
            );
        }

        $adminRole = Role::query()->where('name', User::ROLE_ADMIN)->first();

        if ($adminRole) {
            $adminRole->permissions()->syncWithoutDetaching(
                Permission::query()
                    ->where('group', $module->table_name)
                    ->pluck('id')
                    ->all()
            );
        }
    }

    protected function writeMigrationFile(Module $module): void
    {
        $existing = File::glob(database_path('migrations/*_create_'.$module->table_name.'_table.php'));
        $path = $existing[0] ?? database_path('migrations/'.now()->format('Y_m_d_His').'_create_'.$module->table_name.'_table.php');

        File::put($path, $this->migrationStub($module));
    }

    protected function writeModelFile(Module $module): void
    {
        $class = Str::of($module->table_name)->singular()->studly()->toString();
        $directory = app_path('Models/Generated');

        File::ensureDirectoryExists($directory);
        File::put($directory.'/'.$class.'.php', $this->modelStub($module, $class));
    }

    protected function migrationStub(Module $module): string
    {
        $fields = $module->fields->map(function (ModuleField $field) {
            $nullable = $field->nullable && ! $field->required ? '->nullable()' : '';
            $unique = $field->unique ? '->unique()' : '';
            $default = $field->default_value !== null && ! in_array($field->type, ['file', 'image', 'password'], true)
                ? "->default('".str_replace("'", "\\'", $field->default_value)."')"
                : '';
            $method = match ($field->type) {
                'textarea' => "text('{$field->name}')",
                'number' => "decimal('{$field->name}', 12, 2)",
                'checkbox', 'toggle' => "boolean('{$field->name}')",
                'date' => "date('{$field->name}')",
                'datetime' => "dateTime('{$field->name}')",
                default => "string('{$field->name}')",
            };

            return "            \$table->{$method}{$nullable}{$unique}{$default};";
        })->implode(PHP_EOL);

        $softDeletes = $module->soft_deletes ? PHP_EOL.'            $table->softDeletes();' : '';
        $timestamps = $module->has_timestamps ? PHP_EOL.'            $table->timestamps();' : '';

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('{$module->table_name}')) {
            return;
        }

        Schema::create('{$module->table_name}', function (Blueprint \$table) {
            \$table->id();
{$fields}{$softDeletes}{$timestamps}
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$module->table_name}');
    }
};
PHP;
    }

    protected function modelStub(Module $module, string $class): string
    {
        $fillable = $module->fields
            ->pluck('name')
            ->map(fn (string $name) => "        '{$name}',")
            ->implode(PHP_EOL);

        $softDeletes = $module->soft_deletes ? PHP_EOL.'use Illuminate\Database\Eloquent\SoftDeletes;' : '';
        $trait = $module->soft_deletes ? 'HasFactory, SoftDeletes' : 'HasFactory';

        return <<<PHP
<?php

namespace App\Models\Generated;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;{$softDeletes}

class {$class} extends Model
{
    use {$trait};

    protected \$table = '{$module->table_name}';

    protected \$fillable = [
{$fillable}
    ];
}
PHP;
    }
}
