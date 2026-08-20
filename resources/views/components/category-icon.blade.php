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
    @case('irrigation')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2c3 4 5 7 5 10a5 5 0 0 1-10 0c0-3 2-6 5-10z" /><path d="M4 21h16" />
        </svg>
        @break
    @case('laser')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="6" y="9" width="12" height="7" rx="1" /><path d="M9 9V7a3 3 0 0 1 6 0v2" /><path d="M2 12.5h4M18 12.5h4" />
        </svg>
        @break
    @case('moisture')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="5" y="4" width="14" height="16" rx="1.5" /><path d="M9 9h6M9 13h6M9 17h3" /><path d="M16 15.5a1.5 1.5 0 1 1-1.5-1.5" />
        </svg>
        @break
    @case('rebar')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 12h4M16 12h4M8 12a2 2 0 0 1 2-2h4a2 2 0 0 1 0 4h-4a2 2 0 0 1-2-2z" /><path d="M12 4v3M12 17v3" stroke-dasharray="1.5 1.5" />
        </svg>
        @break
    @case('theodolite')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 21V13M6 21h12" /><circle cx="12" cy="8" r="4.5" /><path d="M7 8h-2M19 8h-2M12 3.5V6" />
        </svg>
        @break
    @case('gps')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 21s7-6.5 7-12a7 7 0 0 0-14 0c0 5.5 7 12 7 12z" /><circle cx="12" cy="9" r="2.3" />
        </svg>
        @break
    @case('mppt')
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="4" y="5" width="16" height="14" rx="1.5" /><path d="M8 15l2.5-5 2 3 2-4 1.5 6" />
        </svg>
        @break
    @default
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9" />
        </svg>
@endswitch
