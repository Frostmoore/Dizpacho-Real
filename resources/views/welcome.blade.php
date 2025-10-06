<x-guest-layout>
    <div class="flex min-h-[70vh] items-center justify-center px-6">
        <div class="w-full max-w-xl text-center">
            {{-- Logo --}}
            <div class="mx-auto mb-8 h-40 w-40">
                <img src="{{ asset('images/logo.png') }}" alt="Dizpacho" class="h-full w-full object-contain">
            </div>

            {{-- Descrizione --}}
            <p class="mt-3 text-base text-gray-600">
                Ordini via chat, gestione operatori e listini — tutto in un unico flusso.
                Semplice ora, sempre potente.
            </p>

            {{-- CTA --}}
            <div class="mt-8 flex items-center justify-center gap-3">
                @auth
                    <a href="{{ url('/dashboard') }}"
                       class="inline-flex items-center rounded-lg px-5 py-2.5 text-sm font-medium text-white bg-gray-900 hover:bg-black transition">
                        Vai alla dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center rounded-lg px-5 py-2.5 text-sm font-medium text-white bg-gray-900 hover:bg-black transition">
                        Accedi
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center rounded-lg px-5 py-2.5 text-sm font-medium text-gray-900 ring-1 ring-gray-300 hover:bg-gray-50 transition">
                            Registrati
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </div>
</x-guest-layout>
