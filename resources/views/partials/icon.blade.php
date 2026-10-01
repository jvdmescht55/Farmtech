{{-- Tiny line icons. Usage: @include('partials.icon', ['name' => 'home', 'class' => 'w-4 h-4']) --}}
@php
    $paths = [
        'home' => 'M3 11l9-8 9 8M5 10v10h5v-6h4v6h5V10',
        'herd' => 'M4 7h16M4 12h16M4 17h10',
        'scale' => 'M12 3v3M5 9h14l-2 11H7L5 9zM9 13a3 3 0 006 0',
        'bell' => 'M6 16V11a6 6 0 1112 0v5l2 2H4l2-2zM10 21h4',
        'note' => 'M6 3h9l3 3v15H6zM9 9h6M9 13h6M9 17h4',
        'more' => 'M5 12h.01M12 12h.01M19 12h.01',
        'drop' => 'M12 3s7 7.5 7 12a7 7 0 11-14 0c0-4.5 7-12 7-12z',
        'pin' => 'M12 21s7-6.5 7-12a7 7 0 10-14 0c0 5.5 7 12 7 12zM12 11a2 2 0 100-4 2 2 0 000 4z',
        'list' => 'M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01',
        'plug' => 'M9 3v6M15 3v6M6 9h12v3a6 6 0 01-12 0V9zM12 18v3',
        'bulb' => 'M9 18h6M10 21h4M12 3a6 6 0 00-4 10.5c.7.7 1 1.5 1 2.5h6c0-1 .3-1.8 1-2.5A6 6 0 0012 3z',
        'help' => 'M9.1 9a3 3 0 015.8 1c0 2-3 2.5-3 4.5M12 18h.01M12 22a10 10 0 110-20 10 10 0 010 20z',
        'start' => 'M5 3l14 9-14 9V3z',
        'table' => 'M3 5h18v14H3zM3 10h18M9 5v14',
        'chip' => 'M7 7h10v10H7zM9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3',
        'link' => 'M10 14a4 4 0 005.66 0l3-3a4 4 0 00-5.66-5.66l-1 1M14 10a4 4 0 00-5.66 0l-3 3a4 4 0 005.66 5.66l1-1',
        'book' => 'M4 5a2 2 0 012-2h13v16H6a2 2 0 00-2 2V5zM4 19a2 2 0 012-2h13',
        'tag' => 'M3 12V4h8l10 10-8 8L3 12zM7.5 7.5h.01',
        'plus' => 'M12 5v14M5 12h14',
        'upload' => 'M12 16V4M7 9l5-5 5 5M4 20h16',
        'live' => 'M12 12h.01M8.5 8.5a5 5 0 000 7M15.5 8.5a5 5 0 010 7M5.6 5.6a9 9 0 000 12.8M18.4 5.6a9 9 0 010 12.8',
    ];
@endphp
<svg class="{{ $class ?? 'w-4 h-4' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['more'] }}"/></svg>
