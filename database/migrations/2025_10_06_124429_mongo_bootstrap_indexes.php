<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        // users
        Schema::connection('mongodb')->create('users', function (Blueprint $c) {
            $c->index('email', 'users_email_unique', ['unique' => true]);
            $c->index('phone', 'users_phone_unique', ['unique' => true, 'sparse' => true]);
            $c->index('created_at', 'users_created_at_idx');
        });

        // operators
        Schema::connection('mongodb')->create('operators', function (Blueprint $c) {
            $c->index('name', 'operators_name_idx');
            $c->index('company_vats', 'operators_company_vats_idx'); // array index ok
            $c->index('created_at', 'operators_created_at_idx');
        });

        // conversations (unique per coppia phone+operator)
        Schema::connection('mongodb')->create('conversations', function (Blueprint $c) {
            $c->index(['phone' => 1, 'operator_id' => 1], 'conv_phone_operator_unique', ['unique' => true]);
            $c->index('state', 'conv_state_idx');
            $c->index('last_message_at', 'conv_last_msg_idx');
        });

        // messages
        Schema::connection('mongodb')->create('messages', function (Blueprint $c) {
            $c->index('conversation_id', 'msg_conv_idx');
            $c->index('ts', 'msg_ts_idx');
            $c->index('type', 'msg_type_idx');
            $c->index('direction', 'msg_direction_idx');
        });

        // orders
        Schema::connection('mongodb')->create('orders', function (Blueprint $c) {
            $c->index('operator_id', 'ord_operator_idx');
            $c->index('phone', 'ord_phone_idx');
            $c->index('customer_vat', 'ord_customer_vat_idx');
            $c->index('status', 'ord_status_idx');
            $c->index('delivery_date', 'ord_delivery_date_idx');
            $c->index('created_at', 'ord_created_at_idx');
        });

        // catalogs (campi annidati nell’array products)
        Schema::connection('mongodb')->create('catalogs', function (Blueprint $c) {
            $c->index('products.sku', 'cat_products_sku_idx');   // non unique: è un array di subdoc
            $c->index('products.name', 'cat_products_name_idx');
        });

        // notifications
        Schema::connection('mongodb')->create('notifications', function (Blueprint $c) {
            $c->index('order_id', 'notif_order_idx');
            $c->index('channel', 'notif_channel_idx');
            $c->index('status', 'notif_status_idx');
            $c->index('ts', 'notif_ts_idx');
        });
    }


    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('notifications');
        Schema::connection('mongodb')->dropIfExists('catalogs');
        Schema::connection('mongodb')->dropIfExists('orders');
        Schema::connection('mongodb')->dropIfExists('messages');
        Schema::connection('mongodb')->dropIfExists('conversations');
        Schema::connection('mongodb')->dropIfExists('operators');
        Schema::connection('mongodb')->dropIfExists('users');
    }
};
