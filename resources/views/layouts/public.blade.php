<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white antialiased dark:bg-zinc-900">
    <main class="mx-auto flex min-h-svh w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
        {{ $slot }}
    </main>

    @fluxScripts
</body>

</html>