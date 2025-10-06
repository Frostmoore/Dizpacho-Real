<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl leading-tight text-white">
                Dashboard
            </h2>

            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                @php $ranges = ['day'=>'Oggi','week'=>'Settimana','month'=>'Mese','year'=>'Anno']; @endphp
                <select name="range"
                        onchange="this.form.submit()"
                        class="rounded-md bg-white/90 text-whatsapp-900 text-sm border-transparent
                            focus:ring-2 focus:ring-white focus:border-white">
                    @foreach($ranges as $key=>$label)
                        <option value="{{ $key }}" @selected($range === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </x-slot>


    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- KPI --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-lg border p-4 bg-white">
                    <div class="text-sm text-gray-500">Ordini ({{ $range }})</div>
                    <div class="mt-2 text-3xl font-semibold">{{ number_format($ordersCount) }}</div>
                </div>
                <div class="rounded-lg border p-4 bg-white">
                    <div class="text-sm text-gray-500">Fatturato ({{ $range }})</div>
                    <div class="mt-2 text-3xl font-semibold">€ {{ number_format($revenueTotal, 2, ',', '.') }}</div>
                </div>
                <div class="rounded-lg border p-4 bg-white">
                    <div class="text-sm text-gray-500">Periodo</div>
                    <div class="mt-2 text-lg">{{ $from->format('d/m/Y') }} → {{ $to->format('d/m/Y') }}</div>
                </div>
                <div class="rounded-lg border p-4 bg-white">
                    <div class="text-sm text-gray-500">Operator/Admin</div>
                    <div class="mt-2 text-lg">{{ auth()->user()->name }}</div>
                </div>
            </div>

            {{-- Charts --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 min-h-0">
                <div class="rounded-lg border bg-white p-4 min-h-0">
                    <div class="mb-3 text-sm text-gray-600">Ordini nel periodo</div>

                    {{-- wrapper con altezza fissa --}}
                    <div class="relative h-64 md:h-80 min-h-0 overflow-hidden">
                        <canvas id="ordersChart" class="absolute inset-0 w-full h-full"></canvas>
                    </div>
                </div>

                <div class="rounded-lg border bg-white p-4 min-h-0">
                    <div class="mb-3 text-sm text-gray-600">Fatturato nel periodo</div>

                    {{-- wrapper con altezza fissa --}}
                    <div class="relative h-64 md:h-80 min-h-0 overflow-hidden">
                        <canvas id="revenueChart" class="absolute inset-0 w-full h-full"></canvas>
                    </div>
                </div>
            </div>


            {{-- Ultimi ordini pendenti --}}
            <div class="rounded-lg border bg-white">
                <div class="p-4 border-b text-sm text-gray-600">Ultimi ordini non elaborati</div>
                <div class="divide-y">
                    @forelse($pendingOrders as $o)
                        <div class="p-4 flex items-center justify-between">
                            <div>
                                <div class="font-medium">{{ $o->customer_name ?? 'Cliente' }}</div>
                                <div class="text-xs text-gray-500">{{ $o->created_at?->format('d/m/Y H:i') }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold">€ {{ number_format($o->total_gross ?? 0, 2, ',', '.') }}</div>
                                <a href="{{ route('orders.show', (string)$o->_id) }}" class="btn btn-primary btn-sm">Apri</a>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-sm text-gray-500">Nessun ordine in attesa 👌</div>
                    @endforelse
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Listino --}}
                <div class="rounded-lg border bg-white">
                    <div class="p-4 border-b text-sm text-gray-600">Listino prezzi</div>
                    <div class="divide-y">
                        @forelse($priceList as $p)
                            <div class="p-4 flex items-center justify-between">
                                <div>
                                    <div class="font-medium">{{ $p->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $p->unit ?? '-' }}</div>
                                </div>
                                <div class="font-semibold">€ {{ number_format($p->price ?? 0, 2, ',', '.') }}</div>
                            </div>
                        @empty
                            <div class="p-4 text-sm text-gray-500">Ancora vuoto.</div>
                        @endforelse
                    </div>
                    <div class="p-3 text-right">
                        <a href="{{ route('pricelist.index') }}" class="btn btn-primary">Gestisci listino</a>
                    </div>
                </div>

                {{-- Clienti --}}
                <div class="rounded-lg border bg-white">
                    <div class="p-4 border-b text-sm text-gray-600">Clienti recenti</div>
                    <div class="divide-y">
                        @forelse($customers as $c)
                            <div class="p-4">
                                <div class="font-medium">{{ $c->name }}</div>
                                <div class="text-xs text-gray-500">{{ $c->email }} @if($c->phone) • {{ $c->phone }} @endif</div>
                            </div>
                        @empty
                            <div class="p-4 text-sm text-gray-500">Nessun cliente recente.</div>
                        @endforelse
                    </div>
                    <div class="p-3 text-right">
                        <a href="{{ route('customers.index') }}" class="btn btn-primary">Tutti i clienti</a>
                    </div>
                </div>

                {{-- Aggiornamenti --}}
                <div class="rounded-lg border bg-white">
                    <div class="p-4 border-b text-sm text-gray-600">Aggiornamenti importanti</div>
                    <div class="divide-y">
                        @forelse($announcements as $a)
                            <div class="p-4">
                                <div class="font-medium">{{ $a['title'] ?? 'Update' }}</div>
                                <div class="text-xs text-gray-500 mb-1">
                                    {{ optional($a['created_at'] ?? null)->format('d/m/Y H:i') }}
                                </div>
                                <div class="text-sm text-gray-700 line-clamp-3">{{ $a['body'] ?? '' }}</div>
                            </div>
                        @empty
                            <div class="p-4 text-sm text-gray-500">Nessun aggiornamento.</div>
                        @endforelse
                    </div>
                    @can('manage-announcements')
                        <div class="p-3 text-right">
                            <a href="{{ route('announcements.index') }}" class="text-sm text-indigo-600 hover:underline">Gestisci aggiornamenti</a>
                        </div>
                    @endcan
                </div>
            </div>

        </div>
    </div>

    {{-- Scripts grafici --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const labelsOrders  = @json($labelsOrders);
            const dataOrders    = @json($dataOrders);
            const labelsRevenue = @json($labelsRevenue);
            const dataRevenue   = @json($dataRevenue);

            // ORDERS
            const ordersEl = document.getElementById('ordersChart');
            if (window._ordersChart) window._ordersChart.destroy();
            window._ordersChart = new Chart(ordersEl, {
                type: 'line',
                data: {
                    labels: labelsOrders,
                    datasets: [{
                        label: 'Ordini',
                        data: dataOrders,
                        tension: 0.25,
                        borderWidth: 2,
                        pointRadius: 0,
                        fill: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // fondamentale con il wrapper a h-64/h-80
                    animation: false,
                    scales: {
                        x: { ticks: { maxRotation: 0 } },
                        y: { beginAtZero: true }
                    }
                }
            });

            // REVENUE
            const revenueEl = document.getElementById('revenueChart');
            if (window._revenueChart) window._revenueChart.destroy();
            window._revenueChart = new Chart(revenueEl, {
                type: 'bar',
                data: {
                    labels: labelsRevenue,
                    datasets: [{
                        label: 'Fatturato (€)',
                        data: dataRevenue,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // idem
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: v => '€ ' + Number(v).toLocaleString('it-IT') }
                        }
                    }
                }
            });
        });
    </script>

</x-app-layout>
