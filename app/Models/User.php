<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use MongoDB\Laravel\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    // ruoli possibili
    public const ROLE_ADMIN    = 'admin';
    public const ROLE_OPERATOR = 'operator';
    public const ROLE_USER     = 'user';

    protected $connection = 'mongodb';
    protected $collection = 'users';

    protected $primaryKey = '_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name', 
        'email', 
        'username', 
        'password', 
        'phone', 
        'role',
        'piva',
        'fornitori',
    ];

    protected $hidden = [
        'password', 
        'remember_token'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // helper veloci
    public function isAdmin(): bool    { return $this->role === self::ROLE_ADMIN; }
    public function isOperator(): bool { return $this->role === self::ROLE_OPERATOR; }
}
