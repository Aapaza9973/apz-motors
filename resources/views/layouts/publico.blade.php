<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/png" href="{{ asset('images/icono.png') }}">

        <title>@yield('titulo', config('app.name', 'APZ Motor\'s')) · {{ config('app.name', 'APZ Motor\'s') }}</title>
        <meta name="description" content="Catálogo público de repuestos y accesorios para motocicletas de APZ Motor's — La Paz, Bolivia.">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=chakra-petch:500,600,700|space-grotesk:400,500,600|ibm-plex-mono:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col">
            <!-- Barra de marca sobre el banner -->
            <header class="bg-gray-900">
                <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2.5">
                        <img src="{{ asset('images/icono.png') }}" alt="Icono APZ Motor's" class="w-8 h-8 object-contain">
                        <div>
                            <p class="text-white font-display font-bold leading-none text-[15px]">APZ Motor's</p>
                            <p class="text-[9px] font-mono tracking-[0.18em] text-gray-500 uppercase mt-0.5">Catálogo de repuestos</p>
                        </div>
                    </div>
                    <a href="{{ route('login') }}" class="text-xs text-gray-400 hover:text-white transition font-medium">
                        Acceso al sistema →
                    </a>
                </div>
                <div class="hazard h-1"></div>
            </header>

            <!-- Banner de marca a su aspecto nativo -->
            <div class="relative">
                <img src="{{ asset('images/banner.png') }}" alt="APZ Motor's — Tu ruta, nuestro compromiso"
                    class="w-full object-cover" style="max-height: 230px;">
            </div>

            <!-- Contenido -->
            <main class="flex-1 w-full max-w-6xl mx-auto px-4 py-8">
                @yield('contenido')
            </main>

            <!-- Pie -->
            <footer class="bg-gray-900 text-gray-400">
                <div class="hazard h-1"></div>
                <div class="max-w-6xl mx-auto px-4 py-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <p class="num">© {{ date('Y') }} APZ Motor's · La Paz, Bolivia</p>
                    <p class="font-mono tracking-[0.14em] uppercase text-[10px] text-gray-500">Tu ruta, nuestro compromiso.</p>
                </div>
            </footer>
        </div>
    </body>
</html>
