<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Operator extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'operators';

    protected $fillable = ['name','contacts','company_vats','catalog_id','settings'];

    protected $casts = [
        'contacts'     => 'array',
        'company_vats' => 'array',
        'settings'     => 'array',
    ];
}
