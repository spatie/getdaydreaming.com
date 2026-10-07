<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fff5d7">
    <meta name="description" content="@yield('description')">
    <link rel="canonical" href="@yield('canonical')">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <title>@yield('title') | Daydreaming for Mac</title>
    @vite(['resources/css/app.css', 'resources/js/site-header.js'])
</head>
<body class="credits-page">
    <div class="page-theme" aria-hidden="true"></div>
    <a class="skip-link" href="#main">Skip to content</a>
    <div class="page-shell">
        <header class="site-header container">
            <a class="brand" href="{{ route('home') }}" aria-label="Daydreaming home">
                <img src="{{ asset('daydreaming-icon.webp') }}" alt="" width="36" height="36">
                <span>Daydreaming</span>
            </a>
            <nav aria-label="Main navigation"><a href="{{ route('home') }}">Back to home</a></nav>
        </header>
        <main id="main" tabindex="-1">
            <section class="content-page container section" aria-labelledby="page-title">
                @yield('content')
            </section>
        </main>
        @include('siteFooter')
    </div>
</body>
</html>
