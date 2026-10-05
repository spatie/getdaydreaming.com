<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    @switch($weather)
        @case('rain')
            <path d="M6 14a4 4 0 1 1 1-8 6 6 0 0 1 11-1 4.5 4.5 0 1 1 1 9M8 18l-1 3m6-3-1 3m6-3-1 3" />
            @break
        @case('snow')
            <path d="M6 14a4 4 0 1 1 1-8 6 6 0 0 1 11-1 4.5 4.5 0 1 1 1 9M8 18v4m-2-2h4m6-2v4m-2-2h4" />
            @break
        @case('fog')
            <path d="M6 12a4 4 0 1 1 1-8 6 6 0 0 1 11-1 4.5 4.5 0 1 1 1 9M4 17h16M6 21h12" />
            @break
        @case('night')
            <path d="M20 16A9 9 0 0 1 8 4a9 9 0 1 0 12 12Z" />
            @break
        @default
            <circle cx="12" cy="12" r="4" />
            <path d="M12 1v3m0 16v3M1 12h3m16 0h3M4 4l2 2m12 12 2 2M4 20l2-2M18 6l2-2" />
    @endswitch
</svg>
