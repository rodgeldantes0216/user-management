<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Module extends Model
{
    use HasFactory;

    public const PERMISSION_ABILITIES = ['view', 'create', 'update', 'delete', 'export', 'approve'];

    protected $fillable = [
        'name',
        'table_name',
        'icon',
        'description',
        'settings',
        'soft_deletes',
        'has_timestamps',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'soft_deletes' => 'boolean',
            'has_timestamps' => 'boolean',
            'generated_at' => 'datetime',
        ];
    }

    public function fields(): HasMany
    {
        return $this->hasMany(ModuleField::class)->orderBy('sort_order')->orderBy('id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ModuleSnapshot::class)->latest('version');
    }

    public function permissionName(string $ability): string
    {
        return Str::slug($this->table_name, '_').'.'.$ability;
    }

    public function routeKey(): string
    {
        return $this->table_name;
    }
}
