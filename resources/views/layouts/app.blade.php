<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $title ?? 'API Tester' }}</title>
        

        @BeartropyAssets
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <script>window.__bootStart = performance.now();</script>
        {{ $slot }}
        <script>
            console.log('[boot] HTML rendered: ' + Math.round(performance.now() - window.__bootStart) + 'ms');
            document.addEventListener('livewire:init', () => {
                console.log('[boot] Livewire init: ' + Math.round(performance.now() - window.__bootStart) + 'ms');
            });
            document.addEventListener('livewire:navigated', () => {
                console.log('[boot] Livewire navigated: ' + Math.round(performance.now() - window.__bootStart) + 'ms');
            });
            window.addEventListener('load', () => {
                console.log('[boot] Window load: ' + Math.round(performance.now() - window.__bootStart) + 'ms');
            });
        </script>
    </body>
    <x-bt-toast position="bottom-right" />
</html>
