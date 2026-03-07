@php
    $reverbConnection = config('broadcasting.connections.reverb', []);
    $realtimeEnabled = config('broadcasting.default') === 'reverb'
        && filled($reverbConnection['key'] ?? null)
        && filled($reverbConnection['secret'] ?? null)
        && filled($reverbConnection['app_id'] ?? null);
@endphp

<script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@2.2.4/dist/echo.iife.js"></script>
<script>
    (function () {
        const enabled = @json($realtimeEnabled);
        if (!enabled) {
            return;
        }

        if (typeof window.Pusher === 'undefined' || typeof window.Echo === 'undefined') {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
        };

        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }

        window.Pusher.logToConsole = false;
        window.UQSEcho = new window.Echo({
            broadcaster: 'reverb',
            key: @json($reverbConnection['key'] ?? ''),
            wsHost: @json($reverbConnection['options']['host'] ?? '127.0.0.1'),
            wsPort: @json((int) ($reverbConnection['options']['port'] ?? 8080)),
            wssPort: @json((int) ($reverbConnection['options']['port'] ?? 8080)),
            forceTLS: @json((bool) ($reverbConnection['options']['useTLS'] ?? false)),
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: { headers },
        });

        document.dispatchEvent(new CustomEvent('uqs:echo-ready'));
    })();
</script>
