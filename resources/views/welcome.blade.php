<!doctype html>
<html lang="en">
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
    <meta property="og:image:alt" content="Daydreaming for Mac, with an app design preview showing sample artwork">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="canonical" href="{{ route('home') }}">
    <link rel="apple-touch-icon" href="{{ asset('daydreaming-icon.png') }}">
    <title>Daydreaming for Mac | Keep the scene. Change the atmosphere.</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('daydreaming-favicon.png') }}">
    <script>
        const localMinutes = new Date().getHours() * 60 + new Date().getMinutes();
        document.documentElement.dataset.localMinutes = localMinutes;
        document.documentElement.dataset.heroTone = localMinutes >= 420 && localMinutes < 1020 ? 'light' : 'dark';
        document.documentElement.dataset.dayPeriod = localMinutes < 360 || localMinutes >= 1260 ? 'night' : localMinutes < 660 ? 'morning' : localMinutes < 1020 ? 'day' : 'golden';
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
                <img src="{{ asset('daydreaming-icon.png') }}" alt="" width="36" height="36">
                <span>Daydreaming</span>
            </a>
            <nav aria-label="Main navigation">
                <a href="#how-it-works">How it works</a>
                <a href="#free-and-pro">Free &amp; Pro</a>
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

                <div class="hero-lights" aria-hidden="true">
                    <svg class="day-clouds" viewBox="0 0 600 180">
                        <defs>
                            <linearGradient id="cloud-light" x2="0" y2="1">
                                <stop stop-color="#fff8e9" stop-opacity=".75" />
                                <stop offset="1" stop-color="#fff8e9" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                        <path d="M22 91c0-12 10-21 23-21 3-26 39-36 58-17 17-8 36 0 39 17 14-1 24 9 24 21Z M271 143c-3-14 10-25 25-25 3-24 22-41 46-37 9-26 48-25 57 2 22-9 49 6 50 27 20-2 37 13 36 33Z" fill="url(#cloud-light)" />
                    </svg>
                    <div class="sun-disc"></div>
                    <div class="moon-disc"></div>
                </div>
                <figure class="preview" id="preview">
                    <div class="scene" id="scene" data-weather="clear">
                        @include('landscape', ['prefix' => 'scene', 'title' => $moments[2]['alt']])
                        @foreach($moments as $moment)
                            @if($moment['picture'])
                                <picture class="day-frame" data-frame="{{ $moment['key'] }}" @if($moment['key'] !== 'golden') hidden @endif>
                                    @foreach($moment['sources'] as $source)
                                        <source type="{{ $source['type'] }}" srcset="{{ $source['srcset'] }}" sizes="(max-width: 700px) 100vw, 1280px">
                                    @endforeach
                                    <img src="{{ asset($moment['picture']) }}" alt="{{ $moment['alt'] }}" width="1440" height="760" @if($moment['key'] === 'golden') fetchpriority="high" @else loading="lazy" @endif decoding="async">
                                </picture>
                            @endif
                        @endforeach
                    </div>
                    <div class="day-controls container">
                        <div class="scene-caption" aria-live="polite" aria-atomic="true">
                            <span id="scene-time">19:00</span>
                            <span id="scene-weather">Clear evening</span>
                        </div>
                        <div class="time-selector">
                            <label class="sr-only" for="day-scrubber">Move through the day</label>
                            <input id="day-scrubber" type="range" min="0" max="1439" step="1" value="1140" aria-controls="scene" aria-valuetext="19:00, clear evening">
                            <div class="time-labels" aria-hidden="true"><span>Midnight</span><span>Morning</span><span>Noon</span><span>Evening</span><span>Midnight</span></div>
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
                    <figcaption class="preview-note">Illustrations shown, not generated app output. Real wallpapers depend on your picture.</figcaption>
                </figure>
            </section>

            <section class="setup container section" id="how-it-works" aria-labelledby="setup-title">
                <div class="section-heading">
                    <h2 id="setup-title">Three steps.</h2>
                    <p>Start with a photo, a piece of art, or the wallpaper you already love. </p>
                </div>
                <ol class="setup-steps">
                    <li>
                        <h3>Choose your picture</h3>
                        <p>Keep what makes it yours. Daydreaming asks OpenAI to preserve the composition and character of your picture.</p>
                    </li>
                    <li>
                        <h3>Add your OpenAI API key</h3>
                        <p>Use your own OpenAI API key. It stays in macOS Keychain. OpenAI bills new wallpapers separately to your account.</p>
                    </li>
                    <li>
                        <h3>Pick a schedule</h3>
                        <p>Morning and evening, or more often with Pro. Follow local weather or choose your own. Choose Start Daydreaming when you’re ready.</p>
                    </li>
                </ol>

                <figure class="app-window mock-window" aria-label="App design preview with sample artwork">
                    <div class="window-toolbar" aria-hidden="true"><span></span><span></span><span></span></div>
                    <div class="window-scene">
                        @include('landscape', ['prefix' => 'app', 'title' => 'Sample artwork in a preview of the new app design'])
                        <div class="window-status">Clear evening · next wallpaper around 7:00 <span aria-hidden="true">···</span></div>
                    </div>
                    <figcaption>App design preview with sample artwork.</figcaption>
                </figure>
            </section>

            <section class="details-section container section" aria-labelledby="details-title">
                <div class="section-heading">
                    <h2 id="details-title">Made for your Mac.</h2>
                </div>
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

            <section class="plans container section" id="free-and-pro" aria-labelledby="plans-title">
                <div class="section-heading">
                    <h2 id="plans-title">Free and Pro.</h2>
                </div>
                <div class="plan-cards">
                    <article class="plan-card" aria-labelledby="free-title">
                        <h3 id="free-title">Free</h3>
                        <p class="plan-promise">Morning and evening</p>
                        <ul>
                            <li>Around 7 am and 7 pm, local time</li>
                            <li>Every style, every weather</li>
                            <li>Matching saved wallpapers, no new OpenAI cost</li>
                        </ul>
                    </article>
                    <article class="plan-card plan-pro" aria-labelledby="pro-title">
                        <h3 id="pro-title">Pro</h3>
                        <p class="plan-promise">As often as you like</p>
                        <ul>
                            <li>Create a wallpaper anytime</li>
                            <li>Update as often as every 5 minutes</li>
                            <li>A daily limit you set (starts at 24)</li>
                        </ul>
                        <p class="plan-availability">Pro is not on sale yet.</p>
                    </article>
                </div>
                <p class="plan-note">Both plans use your own OpenAI API key. OpenAI bills your account directly for each new wallpaper. Reused wallpapers need no new OpenAI call.</p>
            </section>

            <section class="privacy" aria-labelledby="privacy-title">
                <div class="privacy-inner container">
                    <div class="privacy-heading">
                        <h2 id="privacy-title">Your Mac, your picture, your key.</h2>
                        <p>No Daydreaming account.<br>No app analytics. No license server.</p>
                    </div>
                    <div class="privacy-details">
                        <article>
                            <div>
                                <h3>Saved on your Mac</h3>
                                <p>Your picture and saved wallpapers stay on your Mac. Your API key and Pro license stay in macOS Keychain.</p>
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
                            <p>Free includes up to two new wallpapers a day, in Morning and evening. A manual creation fills the current slot, leaving the other one for later. Pro is not on sale yet.</p>
                            <p>Both plans use your own OpenAI API key. OpenAI bills your account directly. Usage is <a href="https://help.openai.com/en/articles/9039756-managing-billing-for-chatgpt-and-the-api-platform">billed separately from ChatGPT subscriptions</a>. Picture quality, model, and size affect cost. See <a href="https://openai.com/api/pricing/">OpenAI’s API pricing</a>.</p>
                            <p>Pro allows schedules from 5 minutes to 24 hours, including custom intervals, and uses a signed license verified offline on your Mac. Set a daily safety limit from 1 to 288; new installs start at 24. A 5-minute schedule can request up to 288 new wallpapers a day. Turning on automatic updates checks immediately and can create a new wallpaper.</p>
                        </div>
                    </details>
                    <details>
                        <summary>Will every update cost me?<span aria-hidden="true">+</span></summary>
                        <div>
                            <p>With reuse enabled, Daydreaming can apply a saved wallpaper when your picture, time slot, weather, style, instructions, and settings match. That means no new OpenAI charge. Changing instructions or connected text can mean a new picture is needed. On Free, a manual creation fills the current morning or evening slot, leaving the other one for later. Pro can create a fresh wallpaper anytime.</p>
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
            <a class="brand footer-brand" href="{{ route('home') }}"><img src="{{ asset('daydreaming-icon.png') }}" alt="" width="24" height="24"><span>Daydreaming</span></a>
            <span>© {{ date('Y') }} Daydreaming</span>
            <span class="weather-credit">App weather: <a href="https://www.met.no/en">MET Norway</a> · <a href="https://creativecommons.org/licenses/by/4.0/">CC BY 4.0</a></span>
            <a href="#privacy-title">Privacy</a>
        </footer>
        @foreach(['clear', 'rain', 'snow', 'fog', 'night'] as $weather)
            <template data-icon-template="{{ $weather }}">@include('weatherIcon', ['weather' => $weather])</template>
        @endforeach
    </div>
</body>
</html>
