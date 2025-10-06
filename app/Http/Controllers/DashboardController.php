<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\UTCDateTime;
use App\Models\Order;
use App\Models\PriceList;
use App\Models\User;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $range = $request->get('range', 'month'); // day|week|month|year
        [$from, $to, $groupFmt] = $this->rangeBounds($range);

        // Bounds per pipeline Mongo (UTCDateTime in ms)
        $fromUtc = new UTCDateTime($from->getTimestamp() * 1000);
        $toUtc   = new UTCDateTime($to->getTimestamp() * 1000);

        // KPI base con Eloquent (ok con mongodb/laravel)
        $ordersCount = Order::whereBetween('created_at', [$from, $to])->count();

        // === Aggregazioni "native" su orders ===
        $mdb        = DB::connection('mongodb')->getMongoDB();          // \MongoDB\Database
        $ordersColl = $mdb->selectCollection('orders');                 // \MongoDB\Collection

        $matchWindow = ['created_at' => ['$gte' => $fromUtc, '$lte' => $toUtc]];

        // Fatturato totale del periodo (sommo price*qty delle righe)
        $revenueAgg = $ordersColl->aggregate([
            ['$match' => $matchWindow],
            [
                '$project' => [
                    'order_total' => [
                        '$sum' => [
                            '$map' => [
                                'input' => ['$ifNull' => ['$lines', []]],
                                'as'   => 'l',
                                'in'   => [
                                    '$multiply' => [
                                        ['$toDouble' => ['$ifNull' => ['$$l.price', 0]]],
                                        ['$toDouble' => ['$ifNull' => ['$$l.qty',   0]]],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            ['$group' => ['_id' => null, 'sum' => ['$sum' => '$order_total']]],
        ])->toArray();

        $revenueTotal = (!empty($revenueAgg) && isset($revenueAgg[0]['sum']))
            ? (float) $revenueAgg[0]['sum']
            : 0.0;

        // Serie: ordini per bucket
        $ordersSeries = $ordersColl->aggregate([
            ['$match' => $matchWindow],
            ['$group' => [
                '_id'   => ['$dateToString' => ['format' => $groupFmt, 'date' => '$created_at']],
                'count' => ['$sum' => 1],
            ]],
            ['$sort' => ['_id' => 1]],
        ])->toArray();

        // Serie: fatturato per bucket
        $revenueSeries = $ordersColl->aggregate([
            ['$match' => $matchWindow],
            ['$project' => [
                'bucket' => ['$dateToString' => ['format' => $groupFmt, 'date' => '$created_at']],
                'order_total' => [
                    '$sum' => [
                        '$map' => [
                            'input' => ['$ifNull' => ['$lines', []]],
                            'as'   => 'l',
                            'in'   => [
                                '$multiply' => [
                                    ['$toDouble' => ['$ifNull' => ['$$l.price', 0]]],
                                    ['$toDouble' => ['$ifNull' => ['$$l.qty',   0]]],
                                ],
                            ],
                        ],
                    ],
                ],
            ]],
            ['$group' => ['_id' => '$bucket', 'sum' => ['$sum' => '$order_total']]],
            ['$sort'  => ['_id' => 1]],
        ])->toArray();

        // Ultimi ordini non elaborati (usa i tuoi status reali)
        $pendingOrders = Order::whereIn('status', ['PENDING_OPERATOR_REVIEW', 'OP_ACCEPT_REQUIRED_FIELDS', 'pending'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['_id','customer_name','created_at','lines']);

        // Price list (metadati principali)
        $priceList = PriceList::orderBy('updated_at','desc')
            ->limit(10)
            ->get(['_id','name','currency','version','updated_by','updated_at']);

        // Clienti recenti (adatta i ruoli alla tua tassonomia)
        $customers = User::where('role', 'customer')
        ->orderBy('created_at','desc')
        ->limit(10)
        ->get(['_id','name','email','phone','created_at']);


        // Annunci admin — coerente con lo stile nativo (NO ->collection su Database)
        $annColl = $mdb->selectCollection('announcements');
        $annCursor = $annColl->find(
            [],
            [
                'sort'       => ['created_at' => -1],
                'limit'      => 5,
                'projection' => ['title' => 1, 'body' => 1, 'created_at' => 1],
            ]
        );
        $announcements = iterator_to_array($annCursor);

        // Labels/series grafici
        $labelsOrders  = array_map(fn($x) => (string) $x['_id'], $ordersSeries);
        $dataOrders    = array_map(fn($x) => (int) $x['count'], $ordersSeries);
        $labelsRevenue = array_map(fn($x) => (string) $x['_id'], $revenueSeries);
        $dataRevenue   = array_map(fn($x) => (float)  $x['sum'], $revenueSeries);

        return view('dashboard', [
            'range'         => $range,
            'from'          => $from,
            'to'            => $to,
            'ordersCount'   => $ordersCount,
            'revenueTotal'  => $revenueTotal,
            'labelsOrders'  => $labelsOrders,
            'dataOrders'    => $dataOrders,
            'labelsRevenue' => $labelsRevenue,
            'dataRevenue'   => $dataRevenue,
            'pendingOrders' => $pendingOrders,
            'priceList'     => $priceList,
            'customers'     => $customers,
            'announcements' => $announcements,
        ]);
    }

    private function rangeBounds(string $range): array
    {
        $now = Carbon::now();

        return match ($range) {
            'day'  => [$now->copy()->startOfDay(),  $now->copy()->endOfDay(),  '%H:00'],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek(), '%Y-%m-%d'],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear(), '%Y-%m'],
            default /* month */ =>
                [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), '%Y-%m-%d'],
        };
    }
}
