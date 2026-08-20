@props(['icon', 'class' => 'w-5 h-5'])

@switch($icon)
    @case('scale')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3v18M7 21h10M4 7h16M4 7l-2 6a3 3 0 0 0 6 0l-2-6M20 7l-2 6a3 3 0 0 0 6 0l-2-6" />
        </svg>
        @break
    @case('ultrasound')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 18c0-6 3-11 8-11s8 5 8 11" /><circle cx="12" cy="14" r="2.2" /><path d="M12 3v2.2" />
        </svg>
        @break
    @case('rfid')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="7" width="18" height="12" rx="2" /><path d="M7 3.5a8 8 0 0 1 10 0M9 6a5 5 0 0 1 6 0" /><circle cx="12" cy="13" r="1.6" />
        </svg>
        @break
    @case('fencing')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 4v16M12 4v16M19 4v16M2 9h20M2 15h20" />
        </svg>
        @break
    @case('solar')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="8" height="8" rx="1" /><path d="M3 8h8M7 4v8" /><path d="M15 13v8M11 21h8M13.5 15.5 15 13l1.5 2.5" />
        </svg>
        @break
    @default
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9" />
        </svg>
@endswitch
