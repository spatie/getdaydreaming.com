<!doctype html>
<html lang="en" class="no-js" data-photo-demo>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fffcf6">
    <meta name="description" content="Choose a picture you love. Daydreaming uses AI to make Mac wallpapers that match the time of day and local weather, using your own OpenAI API key.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Daydreaming for Mac">
    <meta property="og:description" content="Your picture, made into Mac wallpapers that match the time of day and local weather.">
    <meta property="og:image" content="{{ asset('daydreaming-social.jpg') }}">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:url" content="{{ route('home') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Daydreaming for Mac, with an AI-edited evening example of the Golden Gate Bridge">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="canonical" href="{{ route('home') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <title>Daydreaming for Mac | See your favorite wallpaper in a different light.</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" sizes="16x16 32x32 48x48" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('daydreaming-favicon.png') }}">
    <script>
        document.documentElement.classList.remove('no-js');
        const photoFrames = @json($yosemiteFrames);
        const clearPhotoFrames = photoFrames.filter(frame => frame.weather === 'clear');
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
        const initialLowerFrame = clearPhotoFrames.findLast(frame => frame.minutes <= initialFrame * 60) ?? clearPhotoFrames.at(-1);
        const initialUpperFrame = clearPhotoFrames.find(frame => frame.minutes > initialFrame * 60) ?? clearPhotoFrames[0];
        const initialUpperMinutes = initialUpperFrame.minutes > initialLowerFrame.minutes ? initialUpperFrame.minutes : initialUpperFrame.minutes + 1440;
        const initialWeight = (initialFrame * 60 - initialLowerFrame.minutes) / (initialUpperMinutes - initialLowerFrame.minutes);
        const initialNearestFrame = initialWeight < .5 ? initialLowerFrame : initialUpperFrame;
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const initialPhotoFrame = reducedMotion ? initialNearestFrame : initialLowerFrame;
        const initialOverlayFrame = reducedMotion ? initialPhotoFrame : initialUpperFrame;
        const initialOverlayOpacity = reducedMotion ? 0 : initialWeight;
        const photoBase = @json(asset('examples'));
        const photoRevision = @json($photoRevision);
        const photoWidths = [640, 960, 1280, 1536];
        const photoSource = (file, width, format) => photoBase + '/' + file + '-' + width + '.' + format + '?v=' + photoRevision;
        for (const frame of [initialPhotoFrame, ...(initialOverlayOpacity > 0 ? [initialOverlayFrame] : [])]) {
            const preload = document.createElement('link');
            preload.rel = 'preload';
            preload.as = 'image';
            preload.type = 'image/avif';
            preload.imageSrcset = photoWidths.map(width => photoSource(frame.file, width, 'avif') + ' ' + width + 'w').join(', ');
            preload.imageSizes = '(max-width: 700px) 100vw, (max-width: 1128px) calc(100vw - 48px), 1080px';
            preload.fetchPriority = 'high';
            document.head.append(preload);
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="page-theme" aria-hidden="true">
        <span class="page-theme-layer"></span>
        <span class="page-theme-layer"></span>
    </div>
    <a class="skip-link" href="#main">Skip to content</a>
    <div class="page-shell">
        <canvas id="weather-page-canvas" aria-hidden="true"></canvas>
        <header class="site-header container">
            <a class="brand" href="{{ route('home') }}" aria-label="Daydreaming home">
                <img src="{{ asset('daydreaming-icon.webp') }}" alt="" width="36" height="36">
                <span>Daydreaming</span>
            </a>
            <nav aria-label="Main navigation">
                <a href="#how-it-works">How it works</a>
                <a href="#what-it-costs">What it costs</a>
                <a href="#questions">FAQ</a>
            </nav>
        </header>

        <main id="main" tabindex="-1">
            <section class="hero" aria-labelledby="hero-title">
                <div class="hero-theme" aria-hidden="true">
                    <span class="hero-theme-layer"></span>
                    <span class="hero-theme-layer"></span>
                </div>
                <div class="night-sky" aria-hidden="true">
                    <span class="moon" style="--moon-image: url('{{ asset('daydreaming-moon.webp') }}')"></span>
                    <span class="shooting-star shooting-star-one"></span>
                    <span class="shooting-star shooting-star-two"></span>
                </div>
                <div class="weather-sky" aria-hidden="true">
                    <canvas id="weather-sky-canvas"></canvas>
                    <span class="weather-illumination"></span>
                </div>
                <div class="hero-copy container">
                    <h1 id="hero-title">See your favorite wallpaper in a different light.</h1>
                    <p class="hero-description">Daydreaming uses AI to match your picture to the time and local weather, then sets it as your Mac wallpaper.</p>
                    <p class="hero-requirements">Requires macOS 26 or later and your own OpenAI API key.</p>
                </div>

                <figure class="preview" id="preview">
                    <div class="scene-picker" role="group" aria-label="Choose a picture">
                        <button type="button" data-picture-choice="yosemite" aria-pressed="true">Yosemite Valley</button>
                        <button type="button" data-picture-choice="bridge" aria-pressed="false">Golden Gate Bridge</button>
                    </div>
                    <div class="scene" id="scene" data-weather="clear" data-picture="yosemite">
                        <div class="initial-photo" id="initial-photo">
                            <picture class="hero-photo">
                                <source id="scene-avif" type="image/avif" sizes="(max-width: 700px) 100vw, (max-width: 1128px) calc(100vw - 48px), 1080px">
                                <source id="scene-webp" type="image/webp" sizes="(max-width: 700px) 100vw, (max-width: 1128px) calc(100vw - 48px), 1080px">
                                <img id="scene-image" alt="Yosemite Valley example" width="1536" height="1024" fetchpriority="high" decoding="async">
                            </picture>
                            <picture class="hero-photo initial-photo-overlay" id="initial-photo-overlay" aria-hidden="true">
                                <source id="scene-overlay-avif" type="image/avif" sizes="(max-width: 700px) 100vw, (max-width: 1128px) calc(100vw - 48px), 1080px">
                                <source id="scene-overlay-webp" type="image/webp" sizes="(max-width: 700px) 100vw, (max-width: 1128px) calc(100vw - 48px), 1080px">
                                <img id="scene-overlay-image" alt="" width="1536" height="1024" decoding="async">
                            </picture>
                        </div>
                        <script>
                            {
                                const sceneImage = document.getElementById('scene-image');
                                const overlayImage = document.getElementById('scene-overlay-image');
                                const photoContainer = document.getElementById('initial-photo');
                                const overlay = document.getElementById('initial-photo-overlay');
                                const neededImages = initialOverlayOpacity > 0 ? 2 : 1;
                                let readyImages = 0;

                                function prepareInitialImage(image, frame, avifSource, webpSource) {
                                    image.addEventListener('load', () => {
                                        image.decode().catch(() => {}).finally(() => {
                                            readyImages++;
                                            if (readyImages === neededImages) {
                                                photoContainer.classList.add('is-ready');
                                            }
                                        });
                                    }, { once: true });
                                    image.addEventListener('error', () => {
                                        if (avifSource.srcset) {
                                            avifSource.removeAttribute('srcset');
                                            return;
                                        }
                                        readyImages++;
                                        if (readyImages === neededImages) {
                                            photoContainer.classList.add('is-ready');
                                        }
                                    });
                                    avifSource.srcset = photoWidths.map(width => photoSource(frame.file, width, 'avif') + ' ' + width + 'w').join(', ');
                                    webpSource.srcset = photoWidths.map(width => photoSource(frame.file, width, 'webp') + ' ' + width + 'w').join(', ');
                                    image.src = photoSource(frame.file, 1280, 'webp');
                                }

                                prepareInitialImage(sceneImage, initialPhotoFrame, document.getElementById('scene-avif'), document.getElementById('scene-webp'));
                                sceneImage.alt = initialNearestFrame.alt;
                                overlay.style.opacity = initialOverlayOpacity;
                                if (initialOverlayOpacity > 0) {
                                    prepareInitialImage(overlayImage, initialOverlayFrame, document.getElementById('scene-overlay-avif'), document.getElementById('scene-overlay-webp'));
                                }
                            }
                        </script>
                        <noscript><img class="hero-photo-fallback" src="{{ asset('examples/yosemite-clear-day-1280.webp') }}?v={{ $photoRevision }}" alt="Yosemite Valley in daylight" width="1536" height="1024"></noscript>
                        <canvas id="weather-scene-canvas" class="weather-scene-canvas" aria-hidden="true"></canvas>
                        <span class="weather-scene-illumination" aria-hidden="true"></span>
                    </div>
                    <div class="day-controls container">
                        <p id="scene-feedback" class="scene-feedback" role="status" hidden></p>
                        <div class="scene-caption">
                            <span id="scene-time"></span>
                        </div>
                        <div class="time-selector">
                            <label class="sr-only" for="day-scrubber">Choose an hour of the day</label>
                            <input id="day-scrubber" type="range" min="0" max="24" step="any" value="0" aria-controls="scene">
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
                        document.getElementById('scene-time').textContent = initialTime;
                        document.getElementById('day-scrubber').value = initialFrame;
                        document.getElementById('day-scrubber').setAttribute('aria-valuetext', initialTime + ', ' + initialNearestFrame.label.toLowerCase() + ' example');
                        document.getElementById('scene').dataset.frame = initialNearestFrame.key;
                    </script>
                    <noscript><p class="no-script-note">Yosemite Valley example. Enable JavaScript to explore the examples.</p></noscript>
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
                        <h3>Connect OpenAI</h3>
                        <p>Add your own OpenAI API key. OpenAI reimagines your picture for the time of day and local weather. Your key stays in macOS Keychain.</p>
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
                                <span class="prompt-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }} / {{ count($promptExamples) }}</span>
                                <h3>{{ $example['title'] }}</h3>
                                <p>{{ $example['prompt'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
                <p class="prompt-location">Write your prompt in Daydreaming’s main window.</p>
                <p class="example-disclosure">Example variations made for this website with an AI image model. Not created by the Daydreaming app.</p>
                <p class="prompt-cost">In the app, each new wallpaper is a separate OpenAI request, billed to your account.</p>
                <h2 class="mac-details-heading">Made for your Mac.</h2>
                <div class="product-details">
                    <article>
                        <h3>The weather, at a glance</h3>
                        <p>The menu bar icon follows the weather. Automatic updates work while the app is running, with optional launch at login.</p>
                    </article>
                    <article>
                        <h3>Pick a style</h3>
                        <p>Natural, Subtle, Watercolor, or Cinematic. Write your own instructions in the prompt. Time and weather are included automatically.</p>
                    </article>
                    <article>
                        <h3>Across every screen</h3>
                        <p>Your wallpaper changes on every display. Use Original as Wallpaper puts your own picture back and pauses updates.</p>
                    </article>
                </div>
            </section>

            <section class="cost container section" id="what-it-costs" aria-labelledby="cost-title">
                <div class="section-heading">
                    <h2 id="cost-title">What it costs.</h2>
                    <p>Daydreaming is free while in preview. New wallpapers are made with OpenAI using your own API key, which OpenAI bills you for. Matching saved wallpapers are reused at no cost.</p>
                </div>
                <p class="cost-note">Every schedule, from 5 minutes to 24 hours. Create a wallpaper anytime. A daily safety limit you set, starting at 24. A 5-minute schedule can make up to 288 new wallpapers a day, each billed by OpenAI.</p>
            </section>

            <section class="privacy" aria-labelledby="privacy-title">
                <div class="privacy-inner container">
                    <div class="privacy-heading">
                        <h2 id="privacy-title">Your Mac, your picture, your key.</h2>
                        <p>No Daydreaming account.<br>No app analytics.</p>
                    </div>
                    <div class="privacy-details">
                        <article>
                            <div>
                                <h3>Saved on your Mac</h3>
                                <p>Your picture and saved wallpapers stay on your Mac. Your API key stays in macOS Keychain.</p>
                            </div>
                        </article>
                        <article>
                            <div>
                                <h3>Sent directly to OpenAI</h3>
                                <p>To make a new wallpaper, your picture, style and instructions go directly to OpenAI, with the local time and weather.</p>
                            </div>
                        </article>
                        <article>
                            <div>
                                <h3>Weather, with a choice</h3>
                                <p>Local weather comes from MET Norway, using approximate coordinates. Prefer not to share your location? Choose fixed weather instead.</p>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section class="makers container section" id="team" aria-labelledby="makers-title">
                <div class="makers-copy">
                    <h2 id="makers-title">Meet the dreamer.</h2>
                    <p>I'm Freek, a developer and partner at Spatie. I build tools for developers, write about what I learn, and make personal projects for the Mac and the web.</p>
                    <p>Daydreaming is one of them. It keeps the wallpaper you chose and lets the light and weather around it change through the day.</p>
                </div>
                <div class="maker-card">
                    <img src="{{ asset('freek.webp') }}" alt="Freek Van der Herten" width="256" height="256" loading="lazy" decoding="async">
                    <div class="maker-card-copy">
                        <h3>Freek Van der Herten</h3>
                        <p>Developer and partner at Spatie</p>
                        <a class="maker-spatie" href="https://spatie.be"><img src="{{ asset('spatie-logo.svg') }}" alt="Spatie" width="110" height="49" loading="lazy"></a>
                        <nav class="maker-links" aria-label="Freek's links">
                            <a href="https://freek.dev" aria-label="Freek's blog">Blog</a>
                            <a href="https://x.com/freekmurze" aria-label="Freek on X">X</a>
                            <a href="https://www.linkedin.com/in/freek-van-der-herten-3487a7181" aria-label="Freek on LinkedIn">LinkedIn</a>
                        </nav>
                    </div>
                </div>
                <div class="maker-projects">
                    <h3>More things I've made</h3>
                    <div class="maker-project-grid">
                        <a href="https://runbloom.app"><strong>Bloom</strong><span>A Mac app for coding agents</span><span aria-hidden="true">↗</span></a>
                        <a href="https://ohdear.app"><strong>Oh Dear</strong><span>Website monitoring</span><span aria-hidden="true">↗</span></a>
                        <a href="https://flareapp.io"><strong>Flare</strong><span>Error tracking for PHP</span><span aria-hidden="true">↗</span></a>
                        <a href="https://freeksrecords.com"><strong>Freek's Records</strong><span>My vinyl collection</span><span aria-hidden="true">↗</span></a>
                    </div>
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
                            <p>A Mac running macOS 26 or later, a picture you want to use, and your own OpenAI API key with API billing set up. The app is in development and a public download is not available yet.</p>
                        </div>
                    </details>
                    <details>
                        <summary>What does it cost?<span aria-hidden="true">+</span></summary>
                        <div>
                            <p>Daydreaming is free while in preview. Every schedule is available, and you can create a wallpaper anytime.</p>
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
                            <p>Your original and saved wallpapers stay in your Mac’s Application Support folder. For a new wallpaper, a JPEG copy, at most 2,560 pixels on its longest edge, goes directly to OpenAI with your style, instructions, time, weather, and any connected text. Use Original as Wallpaper returns to your picture. You can clear saved wallpapers in Settings. Both pause automatic updates; clearing keeps your original.</p>
                        </div>
                    </details>
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <div class="footer-art" aria-hidden="true"><span class="footer-horizon"></span></div>
            <div class="footer-inner container">
                <div class="footer-main">
                    <div class="footer-intro">
                        <a class="brand footer-brand" href="{{ route('home') }}"><img src="{{ asset('daydreaming-icon.webp') }}" alt="" width="40" height="40"><span>Daydreaming</span></a>
                        <p>See your favorite wallpaper in a different light.</p>
                    </div>
                    <nav class="footer-nav" aria-label="Footer navigation">
                        <div>
                            <h2>Explore</h2>
                            <a href="#how-it-works">How it works</a>
                            <a href="#what-it-costs">What it costs</a>
                            <a href="#questions">Questions</a>
                            <a href="#privacy-title">Privacy</a>
                        </div>
                        <div>
                            <h2>Elsewhere</h2>
                            <a href="https://spatie.be">Spatie</a>
                            <a href="https://github.com/spatie">Spatie on GitHub</a>
                            <a href="https://freek.dev">Freek’s blog</a>
                        </div>
                    </nav>
                </div>
                <a class="footer-credit-link" href="{{ route('credits') }}">Credits</a>
            </div>
        </footer>
        <script id="photo-frames" type="application/json">{!! json_encode(['bridge' => $photoFrames, 'yosemite' => $yosemiteFrames], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        <script id="photo-base" type="application/json">@json(['url' => asset('examples'), 'revision' => $photoRevision])</script>
    </div>
</body>
</html>
