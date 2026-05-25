<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'version',
        'label',
        'schema',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
