<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>419 - Page Expired</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('images/logo-login.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
            <div class="w-full sm:max-w-md">
                <!-- Logo -->
                <div class="flex justify-center mb-8">
                    <a href="/">
                        <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                    </a>
                </div>

                <!-- Error Card -->
                <div class="bg-white dark:bg-gray-800 shadow-md sm:rounded-lg p-8">
                    <!-- Error Icon -->
                    <div class="flex justify-center mb-6">
                        <div class="bg-red-100 dark:bg-red-900/20 rounded-full p-4">
                            <svg class="h-12 w-12 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Error Code -->
                    <div class="text-center mb-6">
                        <h1 class="text-4xl font-bold text-red-600 dark:text-red-400 mb-2">419</h1>
                        <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 mb-2">
                            Sesión Expirada
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                            Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente para continuar.
                        </p>
                    </div>

                    <!-- Alert Box -->
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 dark:border-yellow-600 p-4 mb-6 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400 dark:text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                    Por tu seguridad, tu sesión fue cerrada automáticamente.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="flex gap-4">
                        <a href="/login" class="flex-1 inline-flex justify-center items-center px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            Iniciar Sesión Nuevamente
                        </a>
                        <a href="/" class="inline-flex justify-center items-center px-4 py-3 bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-100 font-medium rounded-lg transition-colors">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 0110-8.94m0 0a9 9 0 019 8.94m-9-8.94v17m0 0a9 9 0 01-9-8.94m9 8.94a9 9 0 0018.94 0m-18.94 0a9 9 0 019-8.94"/>
                            </svg>
                        </a>
                    </div>

                    <!-- Additional Info -->
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                            Si el problema persiste, contacta al soporte técnico.
                        </p>
                    </div>
                </div>

                <!-- Footer Message -->
                <div class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
                    <p>© {{ date('Y') }} {{ config('app.name', 'FerreNet') }}. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </body>
</html>
