<?php

namespace App\Services;

use App\Models\Module;
use App\Models\ModuleField;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Modules\FieldTypeRegistry;
use App\Services\Modules\ModuleSnapshotService;
use App\Support\ModuleNameGuard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ModuleGenerator
{
    public function __construct(
        protected FieldTypeRegistry $fieldTypes = new FieldTypeRegistry,
        protected ModuleSnapshotService $snapshots = new ModuleSnapshotService,
    ) {}

    public function generate(Module $module): void
    {
        $module->load('fields.relationshipModule');
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
        $this->snapshots->snapshot($module, auth()->user(), 'Generated schema');
    }

    public function export(Module $module): array
    {
        return [
            'export_version' => 2,
            ...$this->snapshots->schema($module),
        ];
    }

    protected function createRuntimeTable(Module $module): void
    {
        Schema::create($module->table_name, function (Blueprint $table) use ($module) {
            $table->id();

            foreach ($module->fields as $field) {
                $column = $this->column($table, $field);

                if ($field->nullable || $field->hasCondition()) {
                    $column->nullable();
                }

                if ($field->unique) {
                    $column->unique();
                }

                if ($field->default_value !== null && $this->fieldTypes->canHaveDefault($field)) {
                    $column->default($field->default_value);
                }

                if ($field->type === 'relationship' && $field->relationshipModule && $field->relationship_type === 'belongs_to') {
                    $foreign = $table->foreign($field->name)
                        ->references('id')
                        ->on($field->relationshipModule->table_name);

                    $field->nullable ? $foreign->nullOnDelete() : $foreign->restrictOnDelete();
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
        return $this->fieldTypes->addColumn($table, $field);
    }

    protected function syncPermissions(Module $module): void
    {
        foreach (Module::PERMISSION_ABILITIES as $ability) {
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
            $nullable = $field->nullable || $field->hasCondition() ? '->nullable()' : '';
            $unique = $field->unique ? '->unique()' : '';
            $default = $field->default_value !== null && $this->fieldTypes->canHaveDefault($field)
                ? "->default('".str_replace("'", "\\'", $field->default_value)."')"
                : '';
            $method = $this->fieldTypes->migrationMethod($field);

            return "            \$table->{$method}{$nullable}{$unique}{$default};";
        })->implode(PHP_EOL);
        $foreignKeys = $module->fields
            ->filter(fn (ModuleField $field) => $field->type === 'relationship' && $field->relationshipModule && $field->relationship_type === 'belongs_to')
            ->map(function (ModuleField $field) {
                $deleteRule = $field->nullable ? 'nullOnDelete' : 'restrictOnDelete';

                return "            \$table->foreign('{$field->name}')->references('id')->on('{$field->relationshipModule->table_name}')->{$deleteRule}();";
            })
            ->implode(PHP_EOL);

        $softDeletes = $module->soft_deletes ? PHP_EOL.'            $table->softDeletes();' : '';
        $timestamps = $module->has_timestamps ? PHP_EOL.'            $table->timestamps();' : '';
        $foreignKeys = $foreignKeys ? PHP_EOL.$foreignKeys : '';

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
{$fields}{$foreignKeys}{$softDeletes}{$timestamps}
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
        $casts = $module->fields
            ->filter(fn (ModuleField $field) => in_array($field->type, ['checkbox', 'toggle', 'json', 'date_range', 'currency', 'number'], true))
            ->map(function (ModuleField $field) {
                $cast = match ($field->type) {
                    'checkbox', 'toggle' => 'boolean',
                    'json', 'date_range' => 'array',
                    'currency', 'number' => 'decimal:2',
                    default => 'string',
                };

                return "        '{$field->name}' => '{$cast}',";
            })
            ->implode(PHP_EOL);

        $softDeletes = $module->soft_deletes ? PHP_EOL.'use Illuminate\Database\Eloquent\SoftDeletes;' : '';
        $relationImports = collect([
            $module->fields->contains(fn (ModuleField $field) => $field->type === 'relationship' && $field->relationship_type === 'belongs_to') ? 'use Illuminate\Database\Eloquent\Relations\BelongsTo;' : null,
            $module->fields->contains(fn (ModuleField $field) => $field->type === 'relationship' && $field->relationship_type === 'has_many') ? 'use Illuminate\Database\Eloquent\Relations\HasMany;' : null,
            $module->fields->contains(fn (ModuleField $field) => $field->type === 'relationship' && $field->relationship_type === 'belongs_to_many') ? 'use Illuminate\Database\Eloquent\Relations\BelongsToMany;' : null,
        ])->filter()->map(fn (string $import) => PHP_EOL.$import)->implode('');
        $trait = $module->soft_deletes ? 'HasFactory, SoftDeletes' : 'HasFactory';
        $relationships = $module->fields
            ->where('type', 'relationship')
            ->map(function (ModuleField $field) use ($module) {
                $relatedClass = Str::of($field->relationshipModule?->table_name ?? 'modules')->singular()->studly()->toString();
                $method = Str::of($field->name)->replaceEnd('_id', '')->camel()->toString();
                $foreignKey = Str::of($module->table_name)->singular()->snake()->append('_id')->toString();

                if ($field->relationship_type === 'has_many') {
                    return <<<PHP

    public function {$method}(): HasMany
    {
        return \$this->hasMany({$relatedClass}::class, '{$foreignKey}');
    }
PHP;
                }

                if ($field->relationship_type === 'belongs_to_many') {
                    return <<<PHP

    public function {$method}(): BelongsToMany
    {
        return \$this->belongsToMany({$relatedClass}::class);
    }
PHP;
                }

                return <<<PHP

    public function {$method}(): BelongsTo
    {
        return \$this->belongsTo({$relatedClass}::class, '{$field->name}');
    }
PHP;
            })
            ->implode(PHP_EOL);

        return <<<PHP
<?php

namespace App\Models\Generated;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;{$relationImports}{$softDeletes}

class {$class} extends Model
{
    use {$trait};

    protected \$table = '{$module->table_name}';

    protected \$fillable = [
{$fillable}
    ];

    protected \$casts = [
{$casts}
    ];
{$relationships}
}
PHP;
    }
}
