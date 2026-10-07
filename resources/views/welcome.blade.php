<!doctype html>
<html lang="en" class="no-js" data-photo-demo>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fffcf6">
    <meta name="description" content="Choose a picture you love. Daydreaming uses AI to make Mac wallpapers that follow the time of day and local weather.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Daydreaming for Mac">
    <meta property="og:description" content="Your picture, made into Mac wallpapers that match the time of day and local weather.">
    <meta property="og:image" content="{{ asset('daydreaming-social-20261007.jpg') }}">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:url" content="{{ route('home') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="See your old wallpaper in a new light. Daydreaming for Mac over an evening view of the Golden Gate Bridge.">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ asset('daydreaming-social-20261007.jpg') }}">
    <link rel="canonical" href="{{ route('home') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <title>Daydreaming for Mac | See your old wallpaper in a new light.</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" sizes="16x16 32x32 48x48" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('daydreaming-favicon.png') }}">
    <script>
        document.documentElement.classList.remove('no-js');
        const initialPhotoSets = {
            yosemite: @json($yosemiteFrames),
            bridge: @json($photoFrames),
        };
        function formatExampleTime(minutes) {
            const clock = new Date();
            minutes = Math.round(minutes);
            clock.setHours(Math.floor(minutes / 60), minutes % 60, 0, 0);
            return clock.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        }
        const now = new Date();
        const hour = now.getHours();
        const initialFrame = hour + now.getMinutes() / 60;
        document.documentElement.dataset.initialFrame = initialFrame;
        document.documentElement.dataset.dayPeriod = hour < 5 || hour >= 21 ? 'night' : hour < 6 ? 'dawn' : hour < 8 ? 'morning' : hour < 18 ? 'day' : hour < 20 ? 'sunset' : 'twilight';
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        function initialSceneFrames(frames) {
            const clearFrames = frames.filter(frame => frame.weather === 'clear');
            const lower = clearFrames.findLast(frame => frame.minutes <= initialFrame * 60) ?? clearFrames.at(-1);
            const upper = clearFrames.find(frame => frame.minutes > initialFrame * 60) ?? clearFrames[0];
            const upperMinutes = upper.minutes > lower.minutes ? upper.minutes : upper.minutes + 1440;
            const weight = (initialFrame * 60 - lower.minutes) / (upperMinutes - lower.minutes);
            const nearest = weight < .5 ? lower : upper;
            const first = reducedMotion ? nearest : lower;

            return { first, second: reducedMotion ? first : upper, nearest, opacity: reducedMotion ? 0 : weight };
        }
        const initialYosemite = initialSceneFrames(initialPhotoSets.yosemite);
        const initialBridge = initialSceneFrames(initialPhotoSets.bridge);
        const photoBase = @json(asset('examples'));
        const photoRevision = @json($photoRevision);
        const photoWidths = [640, 960, 1280, 1536];
        const photoSource = (file, width, format) => photoBase + '/' + file + '-' + width + '.' + format + '?v=' + photoRevision;
        document.documentElement.style.setProperty('--initial-yosemite-image', 'url("' + photoSource(initialYosemite.nearest.file, 640, 'webp') + '")');
        document.documentElement.style.setProperty('--initial-bridge-image', 'url("' + photoSource(initialBridge.nearest.file, 640, 'webp') + '")');
        for (const [picture, selection] of Object.entries({ yosemite: initialYosemite, bridge: initialBridge })) {
            for (const frame of [selection.first, ...(selection.opacity > 0 ? [selection.second] : [])]) {
                const preload = document.createElement('link');
                preload.rel = 'preload';
                preload.as = 'image';
                preload.type = 'image/avif';
                preload.imageSrcset = photoWidths.map(width => photoSource(frame.file, width, 'avif') + ' ' + width + 'w').join(', ');
                preload.imageSizes = picture === 'yosemite'
                    ? '(max-width: 700px) 64vw, (max-width: 1128px) 57vw, 560px'
                    : '(max-width: 700px) 65vw, (max-width: 1128px) 42vw, 400px';
                preload.fetchPriority = picture === 'yosemite' ? 'high' : 'low';
                document.head.append(preload);
            }
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="page-theme" aria-hidden="true"></div>
    <a class="skip-link" href="#main">Skip to content</a>
    <div class="page-shell">
        <canvas id="weather-page-canvas" aria-hidden="true"></canvas>
        <header class="site-header container">
            <a class="brand" href="{{ route('home') }}" aria-label="Daydreaming home">
                <img src="{{ asset('daydreaming-icon.webp') }}" alt="" width="36" height="36">
                <span>Daydreaming</span>
            </a>
            <nav aria-label="Main navigation">
                <a href="{{ route('changelog') }}">Changelog</a>
                <a class="header-github" href="https://github.com/spatie/daydreaming-app" aria-label="Daydreaming on GitHub" title="Daydreaming on GitHub">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
                </a>
                @if($latestRelease)
                    <a class="nav-download" href="{{ route('download') }}" data-download-celebration>Download</a>
                @else
                    <button class="nav-download" type="button" disabled>Download soon</button>
                @endif
            </nav>
        </header>

        <main id="main" tabindex="-1">
            <section class="hero" aria-labelledby="hero-title">
                <div class="hero-theme" aria-hidden="true"></div>
                <div class="night-sky" aria-hidden="true">
                    <span class="milky-way"></span>
                    <span class="moon" style="--moon-image: url('{{ asset('daydreaming-moon.webp') }}')"></span>
                    <span class="shooting-star shooting-star-one"></span>
                    <span class="shooting-star shooting-star-two"></span>
                </div>
                <div class="weather-sky" aria-hidden="true">
                    <canvas id="weather-sky-canvas"></canvas>
                    <span class="weather-illumination"></span>
                </div>
                <div class="hero-copy container">
                    <h1 id="hero-title">See your old wallpaper in a new light.</h1>
                    <p class="hero-description">Daydreaming uses AI to match your picture to the time and local weather, then sets it as your Mac wallpaper.</p>
                </div>

                <figure class="preview" id="preview">
                    <figcaption class="sr-only">Yosemite Valley and Golden Gate Bridge wallpapers on two Macs, controlled together.</figcaption>
                    <div class="device-stage">
                        <div class="desktop-device">
                            <div class="scene" id="scene" data-weather="clear" data-picture="yosemite">
                                <div class="initial-photo" id="scene-initial-photo">
                                    <picture class="hero-photo">
                                        <source id="scene-avif" type="image/avif" sizes="(max-width: 700px) 64vw, (max-width: 1128px) 57vw, 560px">
                                        <source id="scene-webp" type="image/webp" sizes="(max-width: 700px) 64vw, (max-width: 1128px) 57vw, 560px">
                                        <img id="scene-image" alt="Yosemite Valley example" width="1536" height="1024" fetchpriority="high" decoding="async">
                                    </picture>
                                    <picture class="hero-photo initial-photo-overlay" id="scene-initial-photo-overlay" aria-hidden="true">
                                        <source id="scene-overlay-avif" type="image/avif" sizes="(max-width: 700px) 64vw, (max-width: 1128px) 57vw, 560px">
                                        <source id="scene-overlay-webp" type="image/webp" sizes="(max-width: 700px) 64vw, (max-width: 1128px) 57vw, 560px">
                                        <img id="scene-overlay-image" alt="" width="1536" height="1024" decoding="async">
                                    </picture>
                                </div>
                                <noscript><img class="hero-photo-fallback" src="{{ asset('examples/yosemite-clear-day-1280.webp') }}?v={{ $photoRevision }}" alt="Yosemite Valley in daylight" width="1536" height="1024"></noscript>
                                <canvas class="weather-scene-canvas" aria-hidden="true"></canvas>
                                <span class="weather-scene-illumination" aria-hidden="true"></span>
                            </div>
                            <img class="device-frame" src="{{ asset('imac-frame.webp') }}" alt="" width="1500" height="1266" aria-hidden="true" fetchpriority="high">
                            <img class="device-frame dark-device-frame" src="{{ asset('imac-frame.webp') }}" alt="" width="1500" height="1266" aria-hidden="true">
                        </div>
                        <div class="laptop-device">
                            <div class="scene" id="bridge-scene" data-weather="clear" data-picture="bridge">
                                <div class="initial-photo" id="bridge-initial-photo">
                                    <picture class="hero-photo">
                                        <source id="bridge-avif" type="image/avif" sizes="(max-width: 700px) 65vw, (max-width: 1128px) 42vw, 400px">
                                        <source id="bridge-webp" type="image/webp" sizes="(max-width: 700px) 65vw, (max-width: 1128px) 42vw, 400px">
                                        <img id="bridge-image" alt="Golden Gate Bridge example" width="1536" height="1024" decoding="async">
                                    </picture>
                                    <picture class="hero-photo initial-photo-overlay" id="bridge-initial-photo-overlay" aria-hidden="true">
                                        <source id="bridge-overlay-avif" type="image/avif" sizes="(max-width: 700px) 65vw, (max-width: 1128px) 42vw, 400px">
                                        <source id="bridge-overlay-webp" type="image/webp" sizes="(max-width: 700px) 65vw, (max-width: 1128px) 42vw, 400px">
                                        <img id="bridge-overlay-image" alt="" width="1536" height="1024" decoding="async">
                                    </picture>
                                </div>
                                <noscript><img class="hero-photo-fallback" src="{{ asset('examples/bridge-noon-1280.webp') }}?v={{ $photoRevision }}" alt="Golden Gate Bridge in daylight" width="1536" height="1024"></noscript>
                                <canvas class="weather-scene-canvas" aria-hidden="true"></canvas>
                                <span class="weather-scene-illumination" aria-hidden="true"></span>
                            </div>
                            <img class="device-frame" src="{{ asset('macbook-pro-frame.webp') }}" alt="" width="1500" height="920" aria-hidden="true">
                            <img class="device-frame dark-device-frame" src="{{ asset('macbook-pro-frame.webp') }}" alt="" width="1500" height="920" aria-hidden="true">
                        </div>
                    </div>
                    <script>
                        function prepareInitialScene(prefix, selection) {
                            const image = document.getElementById(prefix + '-image');
                            const overlayImage = document.getElementById(prefix + '-overlay-image');
                            const photoContainer = document.getElementById(prefix + '-initial-photo');
                            const overlay = document.getElementById(prefix + '-initial-photo-overlay');
                            const neededImages = selection.opacity > 0 ? 2 : 1;
                            let resolveReady;
                            const ready = new Promise(resolve => { resolveReady = resolve; });
                            let readyImages = 0;

                            function markReady() {
                                readyImages++;
                                if (readyImages === neededImages) {
                                    resolveReady(photoContainer);
                                }
                            }

                            function prepareImage(target, frame, avifSource, webpSource) {
                                target.addEventListener('load', () => target.decode().catch(() => {}).finally(markReady), { once: true });
                                target.addEventListener('error', () => {
                                    if (avifSource.srcset) {
                                        avifSource.removeAttribute('srcset');
                                        return;
                                    }
                                    markReady();
                                });
                                avifSource.srcset = photoWidths.map(width => photoSource(frame.file, width, 'avif') + ' ' + width + 'w').join(', ');
                                webpSource.srcset = photoWidths.map(width => photoSource(frame.file, width, 'webp') + ' ' + width + 'w').join(', ');
                                target.src = photoSource(frame.file, prefix === 'scene' ? 1280 : 960, 'webp');
                            }

                            prepareImage(image, selection.first, document.getElementById(prefix + '-avif'), document.getElementById(prefix + '-webp'));
                            image.alt = selection.nearest.alt;
                            overlay.style.opacity = selection.opacity;
                            if (selection.opacity > 0) {
                                prepareImage(overlayImage, selection.second, document.getElementById(prefix + '-overlay-avif'), document.getElementById(prefix + '-overlay-webp'));
                            }
                            return ready;
                        }

                        Promise.all([
                            prepareInitialScene('scene', initialYosemite),
                            prepareInitialScene('bridge', initialBridge),
                        ]).then(containers => requestAnimationFrame(() => {
                            containers.forEach(container => container.classList.add('is-ready'));
                        }));
                    </script>
                    <div class="day-controls container">
                        <p id="scene-feedback" class="scene-feedback" role="status" hidden></p>
                        <div class="scene-caption">
                            <span id="scene-time" class="scene-clock" role="img">
                                <span class="scene-clock-dial" aria-hidden="true">
                                    <span class="scene-clock-minute"></span>
                                    <span class="scene-clock-hour"></span>
                                </span>
                            </span>
                        </div>
                        <div class="time-selector">
                            <label class="sr-only" for="day-scrubber">Choose an hour of the day</label>
                            <input id="day-scrubber" type="range" min="0" max="24" step="any" value="0" aria-controls="scene bridge-scene">
                            <div class="time-labels" aria-hidden="true"><span>Night</span><span>Morning</span><span>Afternoon</span><span>Evening</span><span>Night</span></div>
                        </div>
                        <div class="weather-buttons" role="group" aria-label="Choose weather">
                            @foreach(['clear' => 'Clear', 'rain' => 'Rain', 'snow' => 'Snow', 'fog' => 'Fog', 'storm' => 'Storm'] as $weather => $label)
                                <button type="button" data-weather-choice="{{ $weather }}" aria-label="{{ $label }} weather example" aria-pressed="{{ $weather === 'clear' ? 'true' : 'false' }}">
                                    @include('weatherIcon', ['weather' => $weather])
                                    <span>{{ $label }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <p id="scene-announcement" class="sr-only" role="status"></p>
                    <script>
                        const initialTime = formatExampleTime(initialFrame * 60);
                        document.getElementById('scene-time').style.setProperty('--clock-hour-angle', initialFrame * 30 + 'deg');
                        document.getElementById('scene-time').setAttribute('aria-label', initialTime + ' in the wallpaper preview');
                        document.getElementById('day-scrubber').value = initialFrame;
                        document.getElementById('day-scrubber').setAttribute('aria-valuetext', initialTime + ', clear weather example');
                        document.getElementById('scene').dataset.frame = initialYosemite.nearest.key;
                        document.getElementById('bridge-scene').dataset.frame = initialBridge.nearest.key;
                    </script>
                    <noscript><p class="no-script-note">Enable JavaScript to explore both wallpaper examples.</p></noscript>
                </figure>
            </section>

            <section class="setup container section" id="how-it-works" aria-labelledby="setup-title">
                <div class="section-heading">
                    <h2 id="setup-title">Three steps.</h2>
                    <p>Start with a photo, a piece of art, or the wallpaper you already love. </p>
                </div>
                <ol class="setup-steps">
                    <li>
                        <h3>Pick a picture</h3>
                        <p>Choose a picture of your own, or start with the built-in Yosemite Valley. Daydreaming keeps the scene and changes the atmosphere.</p>
                    </li>
                    <li>
                        <h3>Create with AI</h3>
                        <p>Add your OpenAI API key to set up automatic wallpapers. Later, you can prepare a picture and prompt for a one-off creation in Codex.</p>
                    </li>
                    <li>
                        <h3>Start Daydreaming</h3>
                        <p>Use local weather or choose your own, then pick how often your wallpaper changes.</p>
                    </li>
                </ol>

            </section>

            <section class="details-section container section" aria-labelledby="details-title">
                <div class="section-heading">
                    <h2 id="details-title">Or write your own.</h2>
                    <p>Keep the bridge, then change the world around it. A few words in the prompt can take the same picture somewhere entirely new.</p>
                </div>
                <div class="prompt-stack">
                    @foreach($promptExamples as $example)
                        <article class="prompt-card">
                            <picture>
                                <img src="{{ asset('examples/'.$example['file'].'-1280.webp') }}?v={{ $photoRevision }}" srcset="{{ asset('examples/'.$example['file'].'-640.webp') }}?v={{ $photoRevision }} 640w, {{ asset('examples/'.$example['file'].'-960.webp') }}?v={{ $photoRevision }} 960w, {{ asset('examples/'.$example['file'].'-1280.webp') }}?v={{ $photoRevision }} 1280w, {{ asset('examples/'.$example['file'].'-1536.webp') }}?v={{ $photoRevision }} 1536w" sizes="(max-width: 700px) calc(100vw - 40px), 580px" alt="{{ $example['alt'] }}" width="1536" height="1024" loading="lazy" decoding="async" fetchpriority="low">
                            </picture>
                            <div class="prompt-card-copy">
                                <h3>{{ $example['title'] }}</h3>
                                <p>{{ $example['prompt'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="appearance container section" aria-labelledby="appearance-title">
                <div class="section-heading">
                    <div>
                        <span class="appearance-eyebrow">Photos, art, abstracts</span>
                        <h2 id="appearance-title">One wallpaper. Two moods.</h2>
                    </div>
                    <p>Daydreaming works with more than photos. Start with an abstract wallpaper or a piece of art, then describe how it should feel in Light and Dark Mode. When macOS switches appearance, your active wallpaper follows.</p>
                </div>
                <div class="appearance-comparison" role="group" aria-label="Illustrative Light and Dark appearance comparison of the same abstract wallpaper">
                    <figure class="appearance-example appearance-example-light">
                        <img src="{{ asset('examples/abstract-light-1280.webp') }}" srcset="{{ asset('examples/abstract-light-640.webp') }} 640w, {{ asset('examples/abstract-light-1280.webp') }} 1280w" sizes="(max-width: 700px) calc(100vw - 40px), 560px" alt="Abstract flowing ribbons in warm ivory, coral, and soft blue" width="1280" height="854" loading="lazy" decoding="async">
                        <figcaption><span class="appearance-glyph" aria-hidden="true">☀</span> Light Mode</figcaption>
                    </figure>
                    <figure class="appearance-example appearance-example-dark">
                        <img src="{{ asset('examples/abstract-dark-1280.webp') }}" srcset="{{ asset('examples/abstract-dark-640.webp') }} 640w, {{ asset('examples/abstract-dark-1280.webp') }} 1280w" sizes="(max-width: 700px) calc(100vw - 40px), 560px" alt="The same flowing ribbons in midnight indigo, cobalt, and copper" width="1280" height="854" loading="lazy" decoding="async">
                        <figcaption><span class="appearance-glyph" aria-hidden="true">☾</span> Dark Mode</figcaption>
                    </figure>
                </div>
            </section>

            <section class="makers container section" id="team" aria-labelledby="makers-title">
                <div class="makers-copy">
                    <h2 id="makers-title">Meet the dreamer.</h2>
                    <p>I'm Freek, a developer and partner at Spatie. I build tools for developers, write about what I learn, and make personal projects for the Mac and the web.</p>
                    <p>Daydreaming is one of them. It keeps the wallpaper you chose and lets the light and weather around it change through the day.</p>
                    <p>Daydreaming is free and postcardware. A postcard is entirely optional. <a href="{{ route('support') }}#postcardware">Here is where to send one.</a></p>
                </div>
                <div class="maker-card">
                    <img src="{{ asset('freek.webp') }}" alt="Freek Van der Herten" width="256" height="256" loading="lazy" decoding="async">
                    <div class="maker-card-copy">
                        <h3>Freek Van der Herten</h3>
                        <p>Developer and partner at Spatie</p>
                        <a class="maker-spatie" href="https://spatie.be"><img src="{{ asset('spatie-logo.svg') }}" alt="Spatie" width="110" height="49" loading="lazy"></a>
                        <nav class="maker-links" aria-label="Freek's links">
                            <a class="maker-blog-link" href="https://freek.dev" aria-label="Freek's blog">Blog</a>
                            <a class="maker-icon-link" href="https://x.com/freekmurze" aria-label="Freek on X">
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M14.234 10.162 22.977 0h-2.072l-7.591 8.824L7.251 0H.258l9.168 13.343L.258 24H2.33l8.016-9.318L16.749 24h6.993zm-2.837 3.299-.929-1.329L3.076 1.56h3.182l5.965 8.532.929 1.329 7.754 11.09h-3.182z"/></svg>
                            </a>
                            <a class="maker-icon-link" href="https://www.linkedin.com/in/freek-van-der-herten-3487a7181" aria-label="Freek on LinkedIn">
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </a>
                            <a class="maker-icon-link" href="https://freeksrecords.com" aria-label="Freek's Records">
                                <svg class="vinyl-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <circle cx="12" cy="12" r="9.5"></circle>
                                    <circle cx="12" cy="12" r="6.4"></circle>
                                    <circle cx="12" cy="12" r="2.4"></circle>
                                    <circle class="vinyl-center" cx="12" cy="12" r=".65"></circle>
                                </svg>
                            </a>
                        </nav>
                    </div>
                </div>
                <div class="maker-projects">
                    <h3>More things I've made</h3>
                    <ul class="maker-project-list">
                        <li><img class="maker-project-icon mailcoach-icon" src="{{ asset('mailcoach-favicon.svg') }}" alt="" width="28" height="28" loading="lazy"><div><a href="https://mailcoach.app">Mailcoach</a><span>Email marketing</span></div></li>
                        <li><img class="maker-project-icon" src="{{ asset('flare-icon.png') }}" alt="" width="28" height="28" loading="lazy"><div><a href="https://flareapp.io">Flare</a><span>Error tracking for PHP</span></div></li>
                        <li><img class="maker-project-icon" src="{{ asset('there-there-icon.png') }}" alt="" width="28" height="28" loading="lazy"><div><a href="https://there-there.app">There There</a><span>Helpdesk software</span></div></li>
                        <li><img class="maker-project-icon" src="{{ asset('ohdear-icon.png') }}" alt="" width="28" height="28" loading="lazy"><div><a href="https://ohdear.app">Oh Dear</a><span>Website monitoring</span></div></li>
                        <li><img class="maker-project-icon" src="{{ asset('bloom-icon.png') }}" alt="" width="28" height="28" loading="lazy"><div><a href="https://runbloom.app">Bloom</a><span>A Mac app for coding agents</span></div></li>
                        <li><img class="maker-project-icon" src="{{ asset('wordstockt-icon.png') }}" alt="" width="28" height="28" loading="lazy"><div><a href="https://wordstockt.com">WordStockt</a><span>Multiplayer word game</span></div></li>
                    </ul>
                </div>
            </section>

            <section class="faq container section" id="questions" aria-labelledby="faq-title">
                <div class="section-heading">
                    <h2 id="faq-title">Questions?</h2>
                </div>
                <div class="faq-list">
                    <details>
                        <summary>What do I need to use Daydreaming?<span aria-hidden="true">+</span></summary>
                        <div>
                            <p>A Mac running macOS 26 or later, a picture you want to use, and an OpenAI API key with API billing set up. OpenAI powers automatic wallpapers; the Codex handoff is an extra option after setup. @if($latestRelease) <a href="{{ route('download') }}">Download Daydreaming</a> to get started. @else A public download is not available yet. @endif</p>
                        </div>
                    </details>
                    <details>
                        <summary>Which AIs can I use?<span aria-hidden="true">+</span></summary>
                        <div>
                            <p>OpenAI creates wallpapers automatically, including scheduled updates, using your own API key. You can also choose Create in Codex for a one-off idea. Daydreaming saves your picture and instructions in a local folder and opens a prepared chat in the Codex desktop app. You review and send that chat yourself. Scheduled updates still use OpenAI.</p>
                        </div>
                    </details>
                    <details>
                        <summary>Can I suggest a feature?<span aria-hidden="true">+</span></summary>
                        <div>
                            <p>Yes. In Daydreaming, choose Help &gt; Submit a Prompt and tell us what you would like the app to do. Only the text you enter, your app version and build, and any optional credit name or reply email are sent. Your current wallpaper idea, pictures, logs and API key are not attached.</p>
                        </div>
                    </details>
                    <details>
                        <summary>What does it cost?<span aria-hidden="true">+</span></summary>
                        <div>
                            <p>Daydreaming is free. Every schedule is available, and you can create a wallpaper anytime.</p>
                            <p>Use your own OpenAI API key. OpenAI bills your account directly for each new wallpaper. Usage is <a href="https://help.openai.com/en/articles/9039756-managing-billing-for-chatgpt-and-the-api-platform">billed separately from ChatGPT subscriptions</a>. Picture quality, model, and picture size affect cost. See <a href="https://openai.com/api/pricing/">OpenAI’s API pricing</a>.</p>
                            <p>Choose a schedule from 5 minutes to 24 hours, including custom intervals. Set a daily safety limit from 1 to 288; new installs start at 24. A 5-minute schedule can request up to 288 new wallpapers a day. The limit counts submitted requests, including attempts that may have been billed even if they fail. Turning on automatic updates checks immediately and can create a new wallpaper.</p>
                        </div>
                    </details>
                    <details>
                        <summary>Will every update cost me?<span aria-hidden="true">+</span></summary>
                        <div>
                            <p>With reuse enabled, Daydreaming can apply a saved wallpaper when your picture, time slot, weather, style, instructions, and settings match. That means no new OpenAI charge. Changing instructions or connected text can mean a new picture is needed. You can create a fresh wallpaper anytime, within your daily limit.</p>
                        </div>
                    </details>
                    <details>
                        <summary>Does the app need to stay open?<span aria-hidden="true">+</span></summary>
                        <div>
                            <p>Daydreaming needs to be running for automatic updates, and checks the schedule while your Mac is awake. It lives in the menu bar, with no Dock icon. Launch at login is optional. You can also hide the menu bar icon and reopen the window by opening the app again.</p>
                        </div>
                    </details>
                    <details>
                        <summary>Can I use it without sharing my location?<span aria-hidden="true">+</span></summary>
                        <div>
                            <p>Yes. Choose a fixed weather condition in Customize. Automatic weather requires location permission and sends coordinates rounded to two decimal places to MET Norway. The forecast is cached between checks.</p>
                        </div>
                    </details>
                    <details>
                        <summary>Can I give it my own direction?<span aria-hidden="true">+</span></summary>
                        <div>
                            <p>Yes. Pick Natural, Subtle, Watercolor, or Cinematic in Customize, and write your own instructions in the main window’s prompt. Time and weather are included automatically. Mention a public HTTPS page or drop a text, Markdown, HTML, or JSON file into the prompt. Daydreaming reads its text before each new wallpaper, with up to three sources. Typed file paths ask for permission first; website scripts are not run.</p>
                        </div>
                    </details>
                    <details>
                        <summary>Where does my picture go?<span aria-hidden="true">+</span></summary>
                        <div>
                            <p>Your original and saved wallpapers stay in your Mac’s Application Support folder. For an automatic wallpaper, a JPEG copy, at most 2,560 pixels on its longest edge, goes directly to OpenAI with your style, instructions, time, weather, and any connected text. Create in Codex saves a copy and your instructions in a local folder; you decide whether to send the prepared chat. Use Original as Wallpaper returns to your picture. You can clear saved wallpapers in Settings. Both pause automatic updates; clearing keeps your original.</p>
                        </div>
                    </details>
                </div>
            </section>

            <section class="closing-cta container section" aria-labelledby="closing-cta-title">
                <div class="closing-cta-inner">
                    <div class="closing-cta-mark" aria-hidden="true">
                        <img src="{{ asset('daydreaming-icon.webp') }}" alt="" width="176" height="176" loading="lazy">
                    </div>
                    <div class="closing-cta-content">
                        <h2 id="closing-cta-title">See your old wallpaper in a new light.</h2>
                        <p>Give the picture you love a new light as the hours and weather change.</p>
                        @if($latestRelease)
                            <a class="download-button" href="{{ route('download') }}" data-download-celebration>Download Daydreaming</a>
                            <span class="download-note">Version {{ $latestRelease['version'] }} · macOS 26 or later</span>
                        @else
                            <button class="download-button" type="button" disabled>Download soon</button>
                            <span class="download-note">For macOS 26 or later. Free.</span>
                        @endif
                    </div>
                </div>
            </section>
        </main>

        @include('siteFooter')
        <script id="photo-frames" type="application/json">{!! json_encode(['bridge' => $photoFrames, 'yosemite' => $yosemiteFrames], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        <script id="photo-base" type="application/json">@json(['url' => asset('examples'), 'revision' => $photoRevision])</script>
    </div>
</body>
</html>
