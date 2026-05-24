<?php

namespace App\Models\Generated;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrudsTable extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cruds_tables';

    protected $fillable = [
        'name',
        'title',
    ];
}