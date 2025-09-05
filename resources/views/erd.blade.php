<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel ERD - Entity Relationship Diagram</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Load compiled CSS from Vite build --}}
    @php
        $packagePath = base_path('vendor/looaf/laravel-erd/public/vendor/laravel-erd');
        $cssPath = $packagePath . '/css/app.css';
        $jsPath = $packagePath . '/js/app.js';
        
        // For local development, also check relative paths
        if (!file_exists($cssPath)) {
            $localPackagePath = base_path('packages/api/vendor/looaf/laravel-erd/public/vendor/laravel-erd');
            $cssPath = $localPackagePath . '/css/app.css';
            $jsPath = $localPackagePath . '/js/app.js';
        }
    @endphp
    
    @if(file_exists($cssPath))
        <style>{!! file_get_contents($cssPath) !!}</style>
    @else
        {{-- Fallback styles if build assets don't exist --}}
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: #f8fafc;
                color: #374151;
            }

            .fallback-message {
                background: #fef3c7;
                border: 1px solid #f59e0b;
                color: #92400e;
                padding: 1rem;
                margin: 1rem;
                border-radius: 0.375rem;
                text-align: center;
            }

            #erd-app {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        </style>
    @endif
</head>

<body>
    {{-- React app container --}}
    <div id="erd-app">
        {{-- Loading fallback while React loads --}}
        <div class="flex items-center justify-center min-h-screen">
            <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                <p class="text-gray-600">Loading ERD...</p>
            </div>
        </div>
    </div>

    {{-- Hidden form for CSRF token refresh --}}
    <form id="refresh-form" style="display: none;" method="POST" action="{{ route(config('erd.route.name', 'erd') . '.refresh') }}">
        @csrf
    </form>

    {{-- Load compiled JavaScript from Vite build --}}
    @if(file_exists($jsPath))
        <script>{!! file_get_contents($jsPath) !!}</script>
    @else
        {{-- Fallback message if build assets don't exist --}}
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const app = document.getElementById('erd-app');
                app.innerHTML = `
                    <div class="fallback-message">
                        <h3 style="margin-bottom: 0.5rem;">ERD Assets Not Built</h3>
                        <p>Please run the following commands to build the ERD assets:</p>
                        <pre style="background: #f3f4f6; padding: 0.5rem; margin: 1rem 0; border-radius: 0.25rem; font-family: monospace;">
yarn install
yarn build</pre>
                        <p>Or for development:</p>
                        <pre style="background: #f3f4f6; padding: 0.5rem; margin: 1rem 0; border-radius: 0.25rem; font-family: monospace;">
yarn install
yarn dev</pre>
                        <p style="font-size: 0.8rem; color: #6b7280; margin-top: 1rem;">
                            Looking for: {{ $jsPath }}<br>
                            CSS exists: {{ file_exists($cssPath) ? 'Yes' : 'No' }}<br>
                            JS exists: {{ file_exists($jsPath) ? 'Yes' : 'No' }}
                        </p>
                    </div>
                `;
            });
        </script>
    @endif

    {{-- Global configuration for React app --}}
    <script>
        // Make ERD configuration available to React app
        window.ErdConfig = {
            apiEndpoint: '{{ route(config("erd.route.name", "erd") . ".data") }}',
            refreshEndpoint: '{{ route(config("erd.route.name", "erd") . ".refresh") }}',
            csrfToken: '{{ csrf_token() }}',
            routes: {
                data: '{{ route(config("erd.route.name", "erd") . ".data") }}',
                refresh: '{{ route(config("erd.route.name", "erd") . ".refresh") }}'
            }
        };
    </script>
</body>

</html>