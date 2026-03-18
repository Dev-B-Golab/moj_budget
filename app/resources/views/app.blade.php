<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#4f46e5">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="icon" type="image/x-icon" href="/favicon.ico">

        <!-- PWA -->
        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
        <meta name="apple-mobile-web-app-title" content="Mój Budżet">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        <!-- Full-page coin loader — visible instantly, hidden after Vue mounts -->
        <div id="app-loader" style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:#0f172a;transition:opacity .4s ease">
            <div style="display:flex;flex-direction:column;align-items:center;gap:1rem">
                <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#fde68a 0%,#fbbf24 40%,#d97706 100%);box-shadow:inset 0 -4px 8px rgba(120,53,15,.3),inset 0 4px 8px rgba(253,230,138,.4),0 8px 32px rgba(251,191,36,.3);position:relative;animation:_cs 1.2s ease-in-out infinite">
                    <span style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:800;color:#78350f;opacity:.8">$</span>
                    <span style="position:absolute;inset:6px;border-radius:50%;border:2px solid rgba(120,53,15,.2)"></span>
                </div>
                <span style="color:#94a3b8;font-size:.875rem;font-weight:500;letter-spacing:.025em">Ładowanie...</span>
            </div>
        </div>
        <style>@keyframes _cs{0%{transform:rotateY(0) scale(1)}50%{transform:rotateY(180deg) scale(.85)}100%{transform:rotateY(360deg) scale(1)}}</style>

        @inertia
        <script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js').then(function(reg) {
                    // Check for updates every 60s
                    setInterval(function() { reg.update(); }, 60000);
                    // Auto-reload when new SW activates
                    var refreshing = false;
                    navigator.serviceWorker.addEventListener('controllerchange', function() {
                        if (!refreshing) {
                            refreshing = true;
                            window.location.reload();
                        }
                    });
                });
            }
        </script>
    </body>
</html>
