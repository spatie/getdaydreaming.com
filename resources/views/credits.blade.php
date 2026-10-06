<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fff5d7">
    <meta name="description" content="Weather and original photo credits for Daydreaming.">
    <link rel="canonical" href="{{ route('credits') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <title>Credits | Daydreaming for Mac</title>
    @vite(['resources/css/app.css'])
</head>
<body class="credits-page">
    <div class="page-theme" aria-hidden="true"><span class="page-theme-layer"></span></div>
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
            <section class="container section" aria-labelledby="credits-title">
                <div class="section-heading">
                    <h1 id="credits-title">Credits.</h1>
                    <p>Weather data and original photographs used for the Daydreaming website examples.</p>
                </div>

                <div class="product-details">
                    <article>
                        <h2>Weather data</h2>
                        <p>Weather data from <a href="https://www.met.no/en">MET Norway</a>, licensed under <a href="https://creativecommons.org/licenses/by/4.0/">CC BY 4.0</a>.</p>
                    </article>
                    @foreach($photoCredits as $credit)
                        <article>
                            <h2>{{ $credit['picture'] }}</h2>
                            <p>Original photograph by <a href="{{ $credit['source'] }}">{{ $credit['author'] }}</a>, licensed as <a href="{{ $credit['licenseUrl'] }}">{{ $credit['license'] }}</a>.</p>
                            <p>Source downloaded {{ $credit['downloaded'] }}.</p>
                        </article>
                    @endforeach
                </div>

                <p class="example-disclosure">Example edits made for this website with an AI image model.</p>
                <a class="makers-github" href="{{ route('home') }}">Back to Daydreaming</a>
            </section>
        </main>
    </div>
</body>
</html>
