<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use MongoDB\Laravel\Eloquent\Model;

class Order extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'orders';

    protected $fillable = [
        'operator_id','phone','customer_vat','status','lines',
        'delivery_date','notes','requires_user_ack','operator_actions','catalog_updates',
    ];

    protected $casts = [
        'delivery_date'     => 'datetime',
        'requires_user_ack' => 'boolean',
        'lines'             => 'array',
        'operator_actions'  => 'array',
        'catalog_updates'   => 'array',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    // ---- Scopes per dashboard ----

    // filtra per operatore (ObjectId|string)
    public function scopeForOperator(Builder $q, $operatorId): Builder
    {
        return $q->where('operator_id', (string) $operatorId);
    }

    // filtra per intervallo date su created_at
    public function scopeBetween(Builder $q, $start, $end): Builder
    {
        return $q->whereBetween('created_at', [$start, $end]);
    }

    public function scopeStatus(Builder $q, array|string $status): Builder
    {
        return is_array($status) ? $q->whereIn('status', $status) : $q->where('status', $status);
    }

    // helper: “periodi” rapidi (day/week/month/year) su created_at
    public function scopeForPeriod(Builder $q, string $period, ?\DateTimeInterface $ref = null): Builder
    {
        $ref = $ref ? now()->setTimestamp($ref->getTimestamp()) : now();

        return match ($period) {
            'day'   => $q->whereBetween('created_at', [$ref->copy()->startOfDay(),   $ref->copy()->endOfDay()]),
            'week'  => $q->whereBetween('created_at', [$ref->copy()->startOfWeek(),  $ref->copy()->endOfWeek()]),
            'month' => $q->whereBetween('created_at', [$ref->copy()->startOfMonth(), $ref->copy()->endOfMonth()]),
            'year'  => $q->whereBetween('created_at', [$ref->copy()->startOfYear(),  $ref->copy()->endOfYear()]),
            default => $q,
        };
    }

    // calcolo fatturato: somma (qty * price) delle righe con price presente
    public function revenue(): float
    {
        $sum = 0.0;
        foreach (($this->lines ?? []) as $line) {
            if (isset($line['price'], $line['qty']) && is_numeric($line['price']) && is_numeric($line['qty'])) {
                $sum += (float)$line['price'] * (float)$line['qty'];
            }
        }
        return round($sum, 2);
    }
}
