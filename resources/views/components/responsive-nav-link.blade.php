@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-whatsapp text-whatsapp-800 bg-whatsapp-50 focus:outline-none focus:bg-whatsapp-50 focus:text-whatsapp-900'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-gray-600 hover:text-whatsapp-700 hover:border-whatsapp-300 hover:bg-whatsapp-50 focus:outline-none focus:text-whatsapp-700 focus:bg-whatsapp-50 focus:border-whatsapp-400';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
