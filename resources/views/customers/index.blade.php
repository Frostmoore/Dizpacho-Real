<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Clienti</h2>
    </x-slot>

    <div class="p-6 space-y-4">
        <form class="flex gap-2">
            <x-text-input name="q" value="{{ $q }}" placeholder="Cerca nome, email o telefono" />
            <x-primary-button>Cerca</x-primary-button>
        </form>

        <div class="bg-white shadow rounded">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left">
                        <th class="p-3">Nome</th>
                        <th class="p-3">Email</th>
                        <th class="p-3">Telefono</th>
                        <th class="p-3">Creato il</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $c)
                        <tr class="border-t">
                            <td class="p-3">{{ $c->name }}</td>
                            <td class="p-3">{{ $c->email }}</td>
                            <td class="p-3">{{ $c->phone ?? '—' }}</td>
                            <td class="p-3">{{ $c->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td class="p-4" colspan="4">Nessun cliente trovato.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $customers->links() }}
    </div>
</x-app-layout>
