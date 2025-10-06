<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('mongodb')->table('users', function (Blueprint $c) {
            $c->index('username', 'users_username_unique', ['unique' => true, 'sparse' => true]);
        });
    }
    public function down(): void
    {
        Schema::connection('mongodb')->table('users', function (Blueprint $c) {
            $c->dropIndex('users_username_unique');
        });
    }
};
