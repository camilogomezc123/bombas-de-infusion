@props(['name' => 'heart'])
@php
    $paths = [
        'home' => 'M3 10 12 3l9 7M5 9v12h5v-7h4v7h5V9',
        'plan' => 'M9 4H5v17h14V4h-4M9 2h6v5H9zM8 12h8M8 16h5',
        'progress' => 'M4 3v17h17M8 15l4-5 4 3 5-8',
        'team' => 'M21 11a8 8 0 0 1-8 8H6l-4 3V11a9 9 0 0 1 19 0ZM7 10h9M7 14h6',
        'profile' => 'M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM4 22v-3a8 8 0 0 1 16 0v3',
        'calendar' => 'M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2ZM7 2v5M17 2v5M3 10h18M7 14h3M14 14h3',
        'book' => 'M12 5v16M12 5C9 2 4 3 2 4v15c3-1 7-1 10 2 3-3 7-3 10-2V4c-2-1-7-2-10 1Z',
        'heart' => 'M20.8 4.6a5.5 5.5 0 0 0-7.8 0l-1 1-1-1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z',
        'sun' => 'M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM12 2v2M12 20v2M2 12h2M20 12h2M5 5l1 1M18 18l1 1M5 19l1-1M18 6l1-1',
        'moon' => 'M21 14A9 9 0 0 1 10 3a9 9 0 1 0 11 11Z',
        'check' => 'm5 12 4 4L19 6',
        'arrow' => 'M4 12h16m-6-6 6 6-6 6',
        'grid' => 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z',
        'shield' => 'm12 2 9 4v6c0 5-9 10-9 10S3 17 3 12V6l9-4Zm-4 10 3 3 5-6',
        'spark' => 'm12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5L12 3Z',
    ];
@endphp
<svg {{ $attributes->class(['portal-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $paths[$name] ?? $paths['heart'] }}" /></svg>
