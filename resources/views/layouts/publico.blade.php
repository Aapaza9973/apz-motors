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
                    <div class="flex items-center gap-4">
                        <a href="{{ route('carrito.ver') }}" class="relative text-xs text-gray-300 hover:text-white transition font-medium flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z" /></svg>
                            Carrito
                            @if (\App\Support\Carrito::cantidadTotal() > 0)
                                <span class="absolute -top-2 -right-3 min-w-[18px] h-[18px] px-1 rounded-full bg-[#f54505] text-white text-[10px] font-bold flex items-center justify-center">
                                    {{ \App\Support\Carrito::cantidadTotal() }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('login') }}" class="text-xs text-gray-400 hover:text-white transition font-medium">
                            Acceso al sistema →
                        </a>
                    </div>
                </div>
                <div class="hazard h-1"></div>
            </header>

            <!-- Banner de marca a su aspecto nativo -->
            <div class="relative">
                <img src="{{ asset('images/banner.png') }}" alt="APZ Motor's — Tu ruta, nuestro compromiso"
                    class="w-full object-cover" style="max-height: 230px;">
            </div>

            <!-- Mensajes de estado del flujo público -->
            @if (session('status'))
                <div class="max-w-6xl mx-auto px-4 pt-4">
                    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {{ session('status') }}
                    </div>
                </div>
            @endif
            @if (session('status-error'))
                <div class="max-w-6xl mx-auto px-4 pt-4">
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {{ session('status-error') }}
                    </div>
                </div>
            @endif
            @if ($errors->any())
                <div class="max-w-6xl mx-auto px-4 pt-4">
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 space-y-1">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Contenido -->
            <main class="flex-1 w-full max-w-6xl mx-auto px-4 py-8">
                @yield('contenido')
            </main>

            <!-- Pie -->
            <footer class="bg-gray-900 text-gray-400">
                <div class="hazard h-1"></div>
                <div class="max-w-6xl mx-auto px-4 py-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <p class="num">© {{ date('Y') }} APZ Motor's · La Paz, Bolivia</p>
                    <a href="{{ route('pedidos.consultar') }}" class="text-gray-400 hover:text-white transition font-medium">Consultar mi pedido →</a>
                    <p class="font-mono tracking-[0.14em] uppercase text-[10px] text-gray-500">Tu ruta, nuestro compromiso.</p>
                </div>
            </footer>
        </div>
    </body>
</html>
