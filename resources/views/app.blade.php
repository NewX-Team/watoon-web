@php
    $isLightPage = in_array($page['component'] ?? '', ['Welcome', 'auth/Login', 'auth/Register']);
    $initialBg = $isLightPage ? '#FAFAF7' : '#0E0F11';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $isLightPage ? '' : 'dark' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="{{ $initialBg }}">

        {{-- Prevent overscroll bounce and white canvas flash on Mac/iOS --}}
        <style>
            html,
            body {
                background-color: {{ $initialBg }} !important;
                overscroll-behavior: none !important;
                overscroll-behavior-y: none !important;
                overscroll-behavior-x: none !important;
            }

            html.dark,
            html.dark body {
                background-color: #0E0F11 !important;
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Watoon') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased {{ $isLightPage ? 'bg-[#FAFAF7] text-[#0E0F11]' : 'bg-[#0E0F11] text-[#F2EFEA]' }}">
        <x-inertia::app />
    </body>
</html>
