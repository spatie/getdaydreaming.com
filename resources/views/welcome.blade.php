<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f0e8">
    <meta name="description" content="Daydreaming turns an image you love into a Mac wallpaper that follows the time and weather. Coming soon to macOS 26 and later.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Daydreaming for Mac">
    <meta property="og:description" content="Your favorite image, in step with the day.">
    <meta property="og:image" content="{{ asset('daydreaming-icon.png') }}">
    <title>Daydreaming for Mac</title>
    <link rel="icon" type="image/png" href="{{ asset('daydreaming-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="page-shell">
        <header class="site-header">
            <a class="brand" href="/" aria-label="Daydreaming home">
                <img src="{{ asset('daydreaming-icon.png') }}" alt="" width="40" height="40">
                <span>daydreaming</span>
            </a>
            <span class="header-note">A wallpaper with a sense of time</span>
            <span class="availability">Coming soon for Mac <span aria-hidden="true">↗</span></span>
        </header>

        <main>
            <section class="hero" aria-labelledby="hero-title">
                <div class="hero-copy">
                    <p class="eyebrow"><span class="eyebrow-dot"></span> MADE FOR THE MAC</p>
                    <h1 id="hero-title">Your favorite image,<br><em>in step with the day.</em></h1>
                    <p class="hero-description">Daydreaming turns a photo or illustration you love into a desktop wallpaper that follows the time and weather outside.</p>
                    <p class="hero-support">One image. An ever-changing view.</p>
                </div>

                <div class="preview" aria-label="Illustrated preview of a landscape wallpaper at different times and weather">
                    <div class="preview-topline">
                        <span class="preview-caption">AN IMAGE THAT LIVES WITH YOU</span>
                        <span class="preview-current" id="preview-current">01 / MORNING LIGHT</span>
                    </div>

                    <div class="scene" id="scene" data-moment="morning">
                        <svg viewBox="0 0 1440 760" role="img" aria-labelledby="scene-title" preserveAspectRatio="xMidYMid slice">
                            <title id="scene-title">A mountain landscape in morning light</title>
                            <defs>
                                <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1">
                                    <stop class="sky-top" offset="0%" />
                                    <stop class="sky-bottom" offset="100%" />
                                </linearGradient>
                                <linearGradient id="front" x1="0" y1="0" x2="0" y2="1">
                                    <stop class="front-top" offset="0%" />
                                    <stop class="front-bottom" offset="100%" />
                                </linearGradient>
                                <filter id="sun-glow" x="-120%" y="-120%" width="340%" height="340%">
                                    <feGaussianBlur stdDeviation="34" />
                                </filter>
                            </defs>
                            <rect width="1440" height="760" fill="url(#sky)" />
                            <circle class="sun-glow" cx="1010" cy="286" r="136" filter="url(#sun-glow)" />
                            <circle class="sun" cx="1010" cy="286" r="111" />
                            <path class="mountain-back" d="M0 566 204 444 412 531 710 362 964 500 1185 390 1440 506V760H0Z" />
                            <path class="mountain-middle" d="M0 614 228 520 420 583 697 440 979 585 1224 471 1440 568V760H0Z" />
                            <path class="mountain-front" d="M0 681 294 562 646 681 1017 512 1440 667V760H0Z" fill="url(#front)" />
                            <g class="rain" stroke="white" stroke-width="3" stroke-linecap="round" opacity="0">
                                <path d="m100 98-15 34m130-70-15 34m155 20-15 34m130-86-15 34m142 33-15 34m134-76-15 34m146 12-15 34m140-72-15 34m160 43-15 34m120-75-15 34" />
                                <path d="m166 231-15 34m130-70-15 34m155 20-15 34m130-86-15 34m142 33-15 34m134-76-15 34m146 12-15 34m140-72-15 34m160 43-15 34m120-75-15 34" />
                            </g>
                        </svg>
                        <div class="scene-grain" aria-hidden="true"></div>
                        <div class="scene-status" aria-hidden="true"><span class="status-dot"></span><span id="scene-status-text">A fresh start, softly lit</span></div>
                    </div>

                    <div class="moment-bar" role="group" aria-label="Preview a time and weather">
                        <span class="moment-label">SEE THE DAY UNFOLD</span>
                        <div class="moment-buttons">
                            <button class="moment-button is-active" type="button" data-select-moment="morning" aria-pressed="true">Morning</button>
                            <button class="moment-button" type="button" data-select-moment="golden" aria-pressed="false">Golden hour</button>
                            <button class="moment-button" type="button" data-select-moment="rain" aria-pressed="false">After rain</button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="details" aria-labelledby="details-title">
                <div class="details-intro">
                    <p class="eyebrow">A DESKTOP THAT FEELS ALIVE</p>
                    <h2 id="details-title">The same view.<br><em>A new feeling.</em></h2>
                </div>
                <div class="detail-list">
                    <article class="detail-item"><span class="detail-number">01</span><div><h3>Start with your image</h3><p>Choose a photo, an illustration, or whatever you want to see when you open your Mac.</p></div></article>
                    <article class="detail-item"><span class="detail-number">02</span><div><h3>Make it your own</h3><p>Use a simple prompt, or write your own instructions for how the image should change.</p></div></article>
                    <article class="detail-item"><span class="detail-number">03</span><div><h3>Let the day take over</h3><p>Daydreaming updates your wallpaper on a schedule you choose and saves generated versions on your Mac.</p></div></article>
                </div>
            </section>

            <section class="closing" aria-labelledby="closing-title">
                <div class="closing-sun" aria-hidden="true"></div>
                <p class="eyebrow">COMING SOON</p>
                <h2 id="closing-title">Look up from your work.<em>The world has changed.</em></h2>
                <p>Daydreaming is being made for macOS 26 and later. Bring your own OpenAI API key when it arrives.</p>
            </section>
        </main>

        <footer class="site-footer">
            <span>© {{ date('Y') }} Daydreaming</span>
            <span>Made for the Mac, and for the moments between.</span>
        </footer>
    </div>
</body>
</html>
