<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-100">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/png" href="{{ asset('images/icono.png') }}">

        <title>{{ config('app.name', 'APZ Motors') }} — {{ $titulo ?? 'Sistema de Ventas' }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=chakra-petch:500,600,700|space-grotesk:400,500,600|ibm-plex-mono:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased h-full">
        <div class="min-h-full">
            <div class="flex h-screen overflow-hidden bg-gray-100">
                @include('layouts.sidebar')

                <div class="flex flex-1 flex-col overflow-hidden">
                    <!-- Barra superior -->
                    <header class="bg-white border-b border-gray-200">
                        <div class="flex items-center justify-between px-6 py-3">
                            <h1 class="font-display text-lg font-bold tracking-tight text-gray-900 truncate">
                                {{ $titulo ?? 'Dashboard' }}
                            </h1>
                            <div class="flex items-center gap-4">
                                @php $alertasPendientes = \App\Models\AlertaStock::where('leida', false)->count(); @endphp
                                <a href="{{ route('alertas.index') }}" class="relative text-gray-500 hover:text-orange-600 transition" title="Alertas de stock">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                    </svg>
                                    @if ($alertasPendientes > 0)
                                        <span class="absolute -top-1.5 -right-1.5 badge badge-alarm text-[10px] leading-none">{{ $alertasPendientes }}</span>
                                    @endif
                                </a>

                                @can('ver pedidos')
                                    @php $pedidosPendientes = \App\Models\Pedido::where('estado', 'Pendiente')->count(); @endphp
                                    <a href="{{ route('pedidos.index') }}" class="relative text-gray-500 hover:text-orange-600 transition" title="Pedidos en línea pendientes">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                        </svg>
                                        @if ($pedidosPendientes > 0)
                                            <span class="absolute -top-1.5 -right-1.5 badge badge-flame text-[10px] leading-none">{{ $pedidosPendientes }}</span>
                                        @endif
                                    </a>
                                @endcan

                                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                    <button @click="open = !open" class="flex items-center gap-2.5 text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none">
                                        <span class="w-8 h-8 rounded-full bg-orange-600 text-white flex items-center justify-center font-display font-bold">
                                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                        </span>
                                        <span class="hidden sm:block">{{ auth()->user()->name }}</span>
                                        <span class="hidden sm:block eyebrow !text-[10px]">{{ auth()->user()->getRoleNames()->first() ?? 'Usuario' }}</span>
                                    </button>
                                    <div x-show="open" x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg ring-1 ring-black/5 py-1 z-50">
                                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Mi perfil</a>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Cerrar sesión</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </header>

                    <!-- Contenido -->
                    <main class="flex-1 overflow-y-auto p-6">
                        @if (session('status'))
                            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm" role="alert">
                                <span class="num font-semibold">✓</span> {{ session('status') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm" role="alert">
                                <ul class="list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{ $slot }}
                    </main>

                    <footer class="border-t border-gray-200 bg-white px-6 py-3 text-xs text-gray-500 flex items-center justify-between gap-2">
                        <span>© {{ date('Y') }} APZ Motor's — La Paz, Bolivia · Sistema de Ventas e Inventario</span>
                        <span class="num text-[10px] tracking-widest text-gray-400">v1.0 · MVP</span>
                    </footer>
                </div>
            </div>
        </div>
    </body>
</html>
