<!doctype html>
<html lang="en" data-photo-demo>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#112737">
    <meta name="description" content="Keep the scene. Change the atmosphere. Daydreaming reimagines your picture for the time and weather, using your own OpenAI API key. Coming soon for Mac.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Daydreaming for Mac">
    <meta property="og:description" content="Keep the scene. Change the atmosphere. Your picture, reimagined for the time and weather on your Mac.">
    <meta property="og:image" content="{{ asset('daydreaming-social.png') }}">
    <meta property="og:url" content="{{ route('home') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Daydreaming for Mac, with an AI-edited evening example of the Golden Gate Bridge">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="canonical" href="{{ route('home') }}">
    <link rel="apple-touch-icon" href="{{ asset('daydreaming-icon.png') }}">
    <title>Daydreaming for Mac | Keep the scene. Change the atmosphere.</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('daydreaming-favicon.png') }}">
    <script>
        const hour = new Date().getHours();
        const initialFrame = hour < 5 || hour >= 21 ? 3 : hour < 10 ? 0 : hour < 17 ? 1 : 2;
        document.documentElement.dataset.initialFrame = initialFrame;
        document.documentElement.dataset.dayPeriod = ['morning', 'day', 'golden', 'night'][initialFrame];
        const initialPhoto = ['bridge-morning', 'bridge-day', 'bridge-evening', 'bridge-night'][initialFrame];
        const photoBase = @json(asset('examples'));
        const photoWidths = initialFrame === 1 ? [640, 960, 1280, 1920, 2560] : [640, 960, 1280, 1536];
        const preload = document.createElement('link');
        preload.rel = 'preload';
        preload.as = 'image';
        preload.type = 'image/avif';
        preload.imageSrcset = photoWidths.map(width => photoBase + '/' + initialPhoto + '-' + width + '.avif ' + width + 'w').join(', ');
        preload.imageSizes = '100vw';
        preload.fetchPriority = 'high';
        document.head.append(preload);
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>
    <div class="page-shell">
        <div class="desktop-menu" aria-hidden="true">
            <span>Daydreaming</span>
            <span class="desktop-weather"><span id="desktop-weather-icon">@include('weatherIcon', ['weather' => 'clear'])</span><span id="desktop-clock"></span></span>
        </div>
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
                <div class="hero-copy container">
                    <p class="release-note">Coming soon for Mac</p>
                    <h1 id="hero-title">Keep the scene.<br>Change the atmosphere.</h1>
                    <p class="hero-description">Daydreaming redraws your favorite picture for the time and weather, and sets it as your Mac wallpaper.</p>
                    <p class="hero-requirements">For macOS 26 and later. Uses your own OpenAI API key.</p>
                </div>

                <figure class="preview" id="preview">
                    <div class="scene" id="scene" data-weather="clear">
                        <picture class="hero-photo">
                            <source id="scene-avif" type="image/avif" sizes="100vw">
                            <source id="scene-webp" type="image/webp" sizes="100vw">
                            <img id="scene-image" alt="Golden Gate Bridge example" width="1536" height="1024" fetchpriority="high" decoding="async">
                        </picture>
                        <script>
                            document.getElementById('scene-avif').srcset = photoWidths.map(width => photoBase + '/' + initialPhoto + '-' + width + '.avif ' + width + 'w').join(', ');
                            document.getElementById('scene-webp').srcset = photoWidths.map(width => photoBase + '/' + initialPhoto + '-' + width + '.webp ' + width + 'w').join(', ');
                            document.getElementById('scene-image').src = photoBase + '/' + initialPhoto + '-1280.webp';
                        </script>
                        <noscript><img class="hero-photo-fallback" src="{{ asset('examples/bridge-day-1280.webp') }}" alt="Golden Gate Bridge in daylight" width="1536" height="1024"></noscript>
                        <div class="original-photo">
                            <picture>
                                <source type="image/avif" srcset="{{ asset('examples/bridge-day-320.avif') }}">
                                <img src="{{ asset('examples/bridge-day-320.webp') }}" alt="Original Golden Gate Bridge photograph" width="320" height="213" loading="lazy" fetchpriority="low">
                            </picture>
                            <span>Original</span>
                        </div>
                    </div>
                    <div class="day-controls container">
                        <div class="scene-caption" aria-live="polite" aria-atomic="true">
                            <span id="scene-time">19:00</span>
                            <span id="scene-weather">Clear evening</span>
                        </div>
                        <div class="time-selector">
                            <label class="sr-only" for="day-scrubber">Choose one of four times of day</label>
                            <input id="day-scrubber" type="range" min="0" max="3" step="1" value="2" aria-controls="scene" aria-valuetext="19:00, clear evening">
                            <div class="time-labels" aria-hidden="true"><span>Dawn</span><span>Noon</span><span>Dusk</span><span>Night</span></div>
                        </div>
                        <div class="weather-buttons" role="group" aria-label="Choose weather">
                            @foreach(['clear' => 'Clear', 'rain' => 'Rain', 'snow' => 'Snow', 'fog' => 'Fog'] as $weather => $label)
                                <button type="button" data-weather-choice="{{ $weather }}" aria-label="{{ $label }} weather" aria-pressed="{{ $weather === 'clear' ? 'true' : 'false' }}">
                                    @include('weatherIcon', ['weather' => $weather])
                                    <span>{{ $label }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <figcaption class="preview-note">Example variations made for this website with an AI image model. Not created by the Daydreaming app.</figcaption>
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
                        <p>Add your own OpenAI API key. AI reimagines your picture for the time of day and local weather. Your key stays in macOS Keychain.</p>
                    </li>
                    <li>
                        <h3>Allow local weather</h3>
                        <p>Use local weather, or choose the weather yourself. Choose Start Daydreaming when you’re ready, then pick how often your wallpaper changes.</p>
                    </li>
                </ol>

                <figure class="app-window mock-window" aria-label="App design preview. Example wallpaper edited for this website">
                    <div class="window-toolbar" aria-hidden="true"><span></span><span></span><span></span></div>
                    <div class="window-scene">
                        <picture>
                            <source type="image/avif" srcset="{{ asset('examples/bridge-evening-640.avif') }} 640w, {{ asset('examples/bridge-evening-1280.avif') }} 1280w" sizes="(max-width: 700px) calc(100vw - 40px), 850px">
                            <img src="{{ asset('examples/bridge-evening-1280.webp') }}" alt="AI-edited evening bridge wallpaper in a preview of the app design" width="1536" height="1024" loading="lazy" decoding="async" fetchpriority="low">
                        </picture>
                        <div class="window-status">Clear evening · updated 19:02 <span aria-hidden="true">···</span></div>
                    </div>
                    <figcaption>App design preview. Example wallpaper edited for this website.</figcaption>
                </figure>
            </section>

            <section class="details-section container section" aria-labelledby="details-title">
                <div class="section-heading">
                    <h2 id="details-title">Any picture. Any style.</h2>
                </div>
                <div class="landmark-examples">
                    @foreach($photoExamples as $example)
                        <figure class="landmark-example">
                            <h3>{{ $example['name'] }}</h3>
                            <div class="example-pair">
                                <figure>
                                    <picture>
                                        <source type="image/avif" srcset="{{ asset('examples/'.$example['original'].'-640.avif') }} 640w, {{ asset('examples/'.$example['original'].'-1280.avif') }} 1280w" sizes="(max-width: 700px) calc((100vw - 54px) / 2), 550px">
                                        <img src="{{ asset('examples/'.$example['original'].'-1280.webp') }}" srcset="{{ asset('examples/'.$example['original'].'-640.webp') }} 640w, {{ asset('examples/'.$example['original'].'-1280.webp') }} 1280w" sizes="(max-width: 700px) calc((100vw - 54px) / 2), 550px" alt="Original {{ $example['name'] }} photograph" width="1280" height="{{ $example['height'] }}" loading="lazy" decoding="async" fetchpriority="low">
                                    </picture>
                                    <figcaption>Original</figcaption>
                                </figure>
                                <figure>
                                    <picture>
                                        <source type="image/avif" srcset="{{ asset('examples/'.$example['edited'].'-640.avif') }} 640w, {{ asset('examples/'.$example['edited'].'-1280.avif') }} 1280w" sizes="(max-width: 700px) calc((100vw - 54px) / 2), 550px">
                                        <img src="{{ asset('examples/'.$example['edited'].'-1280.webp') }}" srcset="{{ asset('examples/'.$example['edited'].'-640.webp') }} 640w, {{ asset('examples/'.$example['edited'].'-1280.webp') }} 1280w" sizes="(max-width: 700px) calc((100vw - 54px) / 2), 550px" alt="{{ $example['alt'] }}" width="1280" height="{{ $example['height'] }}" loading="lazy" decoding="async" fetchpriority="low">
                                    </picture>
                                    <figcaption>{{ $example['label'] }}</figcaption>
                                </figure>
                            </div>
                        </figure>
                    @endforeach
                </div>
                <p class="example-disclosure">Example variations made for this website with an AI image model. Not created by the Daydreaming app.</p>
                <h2 class="mac-details-heading">Made for your Mac.</h2>
                <div class="product-details">
                    <article>
                        <div class="weather-icons" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="4" /><path d="M12 1v3m0 16v3M1 12h3m16 0h3M4 4l2 2m12 12 2 2M4 20l2-2M18 6l2-2" /></svg>
                            <svg viewBox="0 0 24 24"><path d="M6 18a4 4 0 1 1 1-8 6 6 0 0 1 11-1 4.5 4.5 0 1 1 1 9Z" /></svg>
                            <svg viewBox="0 0 24 24"><path d="M6 14a4 4 0 1 1 1-8 6 6 0 0 1 11-1 4.5 4.5 0 1 1 1 9M8 18l-1 3m6-3-1 3m6-3-1 3" /></svg>
                            <svg viewBox="0 0 24 24"><path d="M12 2v20M3 7l18 10M3 17 21 7M9 4l3 3 3-3M9 20l3-3 3 3" /></svg>
                            <svg viewBox="0 0 24 24"><path d="M20 16A9 9 0 0 1 8 4a9 9 0 1 0 12 12Z" /></svg>
                        </div>
                        <h3>The weather, at a glance</h3>
                        <p>The menu bar icon follows the weather. Automatic updates work while the app is running, with optional launch at login.</p>
                    </article>
                    <article>
                        <h3>Pick a style</h3>
                        <p>Natural, Subtle, Watercolor, or Cinematic. Add a touch of your own. The time and weather come along automatically.</p>
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
                                <p>To make a new wallpaper, your picture, style and instructions go directly to OpenAI, with the local time and weather. OpenAI bills your account for each new picture. Saved matches return without a new image call.</p>
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
                            <p>Choose a schedule from 5 minutes to 24 hours, including custom intervals. Set a daily safety limit from 1 to 288; new installs start at 24. A 5-minute schedule can request up to 288 new wallpapers a day. Turning on automatic updates checks immediately and can create a new wallpaper.</p>
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
                            <p>Yes. Choose a fixed weather condition in Settings. Automatic weather requires location permission and sends coordinates rounded to two decimal places to MET Norway. The forecast is cached between checks.</p>
                        </div>
                    </details>
                    <details>
                        <summary>Can I give it my own direction?<span aria-hidden="true">+</span></summary>
                        <div>
                            <p>Yes. Pick a style (Natural, Subtle, Watercolor, or Cinematic) and add anything else you’d like, such as falling leaves. Time and weather are included automatically. You can also connect text from a file or an HTTPS page, preview it, and include it in the instructions sent to OpenAI. Up to five sources are supported; website scripts are not run.</p>
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

        <footer class="site-footer container">
            <a class="brand footer-brand" href="{{ route('home') }}"><img src="{{ asset('daydreaming-icon.webp') }}" alt="" width="24" height="24"><span>Daydreaming</span></a>
            <span>© {{ date('Y') }} Daydreaming</span>
            <span class="weather-credit">App weather: <a href="https://www.met.no/en">MET Norway</a> · <a href="https://creativecommons.org/licenses/by/4.0/">CC BY 4.0</a></span>
            <a href="#privacy-title">Privacy</a>
        </footer>
        <p class="photo-credits container">Original photos:
            @foreach($photoCredits as $credit)
                <a href="{{ $credit['source'] }}">{{ $credit['author'] }}</a>
                @if($credit['license'] === 'CC0')
                    (<a href="https://creativecommons.org/publicdomain/zero/1.0/">CC0</a>)
                @else
                    ({{ $credit['license'] }})
                @endif
                @if(!$loop->last), @endif
            @endforeach
            Example edits made for this website. No claim to original U.S. Government works.
        </p>
        <script id="photo-frames" type="application/json">{!! json_encode($photoFrames, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        <script id="photo-base" type="application/json">@json(asset('examples'))</script>
        @foreach(['clear', 'rain', 'snow', 'fog', 'night'] as $weather)
            <template data-icon-template="{{ $weather }}">@include('weatherIcon', ['weather' => $weather])</template>
        @endforeach
    </div>
</body>
</html>
