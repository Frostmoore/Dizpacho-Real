@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-whatsapp text-gray-900 focus:outline-none focus:border-whatsapp'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-gray-500 hover:text-whatsapp-700 hover:border-whatsapp-300 focus:outline-none focus:text-gray-700 focus:border-whatsapp-400';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
