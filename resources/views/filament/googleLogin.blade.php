@if(config('services.google.client_id') && config('services.google.client_secret'))
    <div style="margin-top: 1.5rem">
        @if(session('googleLoginError'))
            <p role="alert" style="margin-bottom: 1rem; color: #b91c1c; font-size: .875rem">{{ session('googleLoginError') }}</p>
        @endif

        <div aria-hidden="true" style="display: flex; align-items: center; gap: .75rem; margin-bottom: 1.25rem; color: #6b7280; font-size: .75rem">
            <span style="flex: 1; height: 1px; background: #e5e7eb"></span>
            or
            <span style="flex: 1; height: 1px; background: #e5e7eb"></span>
        </div>

        <x-filament::button tag="a" :href="route('admin.google.redirect')" color="gray" outlined style="display: flex; width: 100%; justify-content: center; min-height: 2.5rem">
            Continue with Google
        </x-filament::button>
    </div>
@endif
