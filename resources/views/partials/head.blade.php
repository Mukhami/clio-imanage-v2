<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' — '.config('app.name', 'MatterLynk') : config('app.name', 'MatterLynk') }}
</title>

@if (!empty($description ?? null))
    <meta name="description" content="{{ $description }}">
@endif

<meta name="robots" content="{{ $robots ?? 'noindex, nofollow' }}">

@if (!empty($canonical ?? null))
    <link rel="canonical" href="{{ $canonical }}">
@endif

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
