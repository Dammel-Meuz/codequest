
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'CodeQuest' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-950 text-white antialiased">
    <a href="#main-content"
       class="sr-only z-50 rounded-lg bg-white px-4 py-2 font-semibold text-slate-950 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">
        Aller au contenu principal
    </a>

    @auth
        @if (auth()->user()->hasVerifiedEmail())
            @include('components.navigation')
        @endif
    @endauth

    @if (session('success') || (session('status') && ! in_array(session('status'), [
        'verification-link-sent',
        'password-reset',
        'password-updated',
    ], true)))
        <div role="status" aria-live="polite" class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
            <p class="rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-200">
                {{ session('success') ?? session('status') }}
            </p>
        </div>
    @endif

    @if (session('error'))
        <div role="alert" class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
            <p class="rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                {{ session('error') }}
            </p>
        </div>
    @endif

    <main id="main-content" tabindex="-1">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
