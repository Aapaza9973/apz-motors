<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/png" href="{{ asset('images/icono.png') }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=chakra-petch:500,600,700|space-grotesk:400,500,600|ibm-plex-mono:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen grid lg:grid-cols-2">
            <!-- Panel de marca: banner APZ Motor's a sangre, con scrim para contraste -->
            <div class="login-banner relative hidden lg:flex flex-col justify-end p-10">
                <div class="rise">
                    <img src="{{ asset('images/icono.png') }}" alt="Icono APZ Motor's" class="w-12 h-12 object-contain">
                    <p class="mt-4 font-display text-2xl font-bold text-white tracking-tight">APZ Motor's</p>
                    <p class="mt-1 text-sm text-gray-300">Repuestos y accesorios para motocicletas</p>
                </div>
                <div class="mt-10 rise rise-1">
                    <p class="eyebrow !text-[9px] text-gray-400">Sistema de ventas e inventario · La Paz, Bolivia</p>
                </div>
                <div class="hazard h-1.5 mt-8"></div>
            </div>

            <!-- Panel del formulario -->
            <div class="flex flex-col justify-center items-center px-6 py-10 bg-gray-100">
                <div class="mb-2 rise">
                    <a href="/">
                        <img src="{{ asset('images/logo.png') }}" alt="APZ Motor's — Repuestos y accesorios para motocicletas" class="h-14 w-auto">
                    </a>
                </div>
                <p class="eyebrow mb-6 rise rise-1">Acceso al sistema de ventas</p>

                <div class="w-full sm:max-w-md mt-2 rise rise-2 overflow-hidden rounded-xl">
                    <!-- Cinta de precaución: firma del taller -->
                    <div class="hazard h-1.5"></div>
                    <div class="bg-white shadow-sm px-6 py-5">
                        {{ $slot }}
                    </div>
                </div>

                <p class="mt-6 text-xs text-gray-400 num">© {{ date('Y') }} APZ Motor's · La Paz, Bolivia</p>
                <a href="{{ route('catalogo.index') }}" class="mt-2 text-xs text-gray-500 hover:text-gray-700 transition font-medium">Ver catálogo público →</a>
            </div>
        </div>
    </body>
</html>
