<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
// ⬇️ questa è la classe giusta per auth con Mongo
use MongoDB\Laravel\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    protected $connection = 'mongodb';
    protected $collection = 'users';

    protected $primaryKey = '_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name', 'email', 'username', 'password',
        // aggiungeremo 'phone' più avanti quando spostiamo il login su telefono
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
