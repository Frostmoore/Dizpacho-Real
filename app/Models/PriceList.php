<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use MongoDB\Laravel\Eloquent\Model;

class PriceList extends Model
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'price_lists';

    protected $fillable = [
        'operator_id',      // ObjectId|string dell'operator
        'name',             // es. "Listino 2025" (facoltativo)
        'currency',         // es. "EUR"
        'items',            // array di voci
        'version',          // int
        'notes',
        'updated_by',       // user_id che ha fatto l'ultimo update
        'is_active',        // bool
    ];

    protected $casts = [
        'items'      => 'array',
        'version'    => 'int',
        'is_active'  => 'boolean',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * items[] schema consigliato:
     * [
     *   'sku'      => (string|null),
     *   'name'     => (string),          // "Pomodori ramati"
     *   'unit'     => (string|null),     // "kg", "pz", ...
     *   'price'    => (float|null),      // 2.50
     *   'active'   => (bool) default true
     *   'tags'     => (array) opzionale
     * ]
     */

    /* -----------------------------------------
     |  Scopes
     | -----------------------------------------
     */
    public function scopeForOperator(Builder $q, $operatorId): Builder
    {
        return $q->where('operator_id', (string)$operatorId);
    }

    public function scopeActive(Builder $q, bool $active = true): Builder
    {
        return $q->where('is_active', $active);
    }

    public function scopeSearch(Builder $q, string $term): Builder
    {
        $term = trim($term);
        return $q->where(function ($qq) use ($term) {
            $qq->where('name', 'regex', "/$term/i")
               ->orWhere('items', 'elemMatch', [
                   '$or' => [
                       ['name' => ['$regex' => $term, '$options' => 'i']],
                       ['sku'  => ['$regex' => $term, '$options' => 'i']],
                   ],
               ]);
        });
    }

    /* -----------------------------------------
     |  Helpers voci listino
     | -----------------------------------------
     */

    /**
     * Trova una voce per SKU o per nome (case-insensitive).
     */
    public function findItem(?string $sku = null, ?string $name = null): ?array
    {
        $items = $this->items ?? [];
        foreach ($items as $it) {
            if ($sku && !empty($it['sku']) && strcasecmp($it['sku'], $sku) === 0) {
                return $it;
            }
            if ($name && !empty($it['name']) && strcasecmp($it['name'], $name) === 0) {
                return $it;
            }
        }
        return null;
    }

    /**
     * Restituisce prezzo e unità (se presenti) per SKU o nome.
     */
    public function priceAndUnit(?string $sku = null, ?string $name = null): array
    {
        $it = $this->findItem($sku, $name);
        return [
            'price' => $it['price'] ?? null,
            'unit'  => $it['unit']  ?? null,
        ];
    }

    /**
     * Upsert (inserisce o aggiorna) una voce del listino.
     * Key di matching: SKU se presente, altrimenti nome (case-insensitive).
     */
    public function upsertItem(array $data): self
    {
        $items = $this->items ?? [];

        $sku  = $data['sku']  ?? null;
        $name = $data['name'] ?? null;

        $idx = null;
        foreach ($items as $i => $it) {
            $matchBySku  = $sku  && !empty($it['sku'])  && strcasecmp($it['sku'],  $sku)  === 0;
            $matchByName = $name && !empty($it['name']) && strcasecmp($it['name'], $name) === 0;
            if ($matchBySku || $matchByName) {
                $idx = $i; break;
            }
        }

        $clean = [
            'sku'    => $sku,
            'name'   => $name,
            'unit'   => $data['unit']  ?? ($idx !== null ? ($items[$idx]['unit']  ?? null) : null),
            'price'  => isset($data['price']) ? (float)$data['price'] : ($idx !== null ? ($items[$idx]['price'] ?? null) : null),
            'active' => array_key_exists('active', $data) ? (bool)$data['active'] : ($idx !== null ? ($items[$idx]['active'] ?? true) : true),
            'tags'   => $data['tags'] ?? ($idx !== null ? ($items[$idx]['tags'] ?? []) : []),
        ];

        if ($idx === null) {
            $items[] = $clean;
        } else {
            $items[$idx] = array_merge($items[$idx], $clean);
        }

        $this->items = $items;
        $this->touchVersion();
        return $this;
    }

    /**
     * Aumenta la versione ad ogni modifica semantica.
     */
    public function touchVersion(): void
    {
        $this->version = (int)($this->version ?? 0) + 1;
    }

    /**
     * Popola/aggiorna automaticamente il listino a partire da un ordine accettato:
     * - se una riga non esiste, la aggiunge con prezzo/unit se presenti nell'ordine
     * - se esiste ma mancano price/unit, li integra
     */
    public function upsertFromOrder(Order $order): self
    {
        $lines = $order->lines ?? [];
        foreach ($lines as $line) {
            $this->upsertItem([
                'sku'   => $line['sku']   ?? null,
                'name'  => $line['name']  ?? null,
                'unit'  => $line['unit']  ?? null,
                'price' => $line['price'] ?? null,
            ]);
        }
        return $this;
    }
}
