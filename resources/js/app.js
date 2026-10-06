import { framePair, keyboardHour, shouldAutoplay } from './photo-demo';
import { skyPalette } from './sky-theme';
import { createWeatherAtmosphere } from './weather-atmosphere';

const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
const saveData = navigator.connection?.saveData === true;
const baseElement = document.getElementById('photo-base');
const { url: base, revision: photoRevision } = baseElement ? JSON.parse(baseElement.textContent) : { url: '', revision: '' };
const imageCache = new Map();
const photoToneCache = new Map();
const demos = [];
const heroElement = document.querySelector('.hero');
const paintHeroSurface = createThemeSurface([...heroElement.querySelectorAll('.hero-theme-layer')]);
const paintPageSurface = createPageThemeSurface([...document.querySelectorAll('.page-theme-layer')]);
const idle = (callback) => window.requestIdleCallback ? window.requestIdleCallback(callback) : setTimeout(callback, 200);
let themeHour = Number(document.getElementById('day-scrubber')?.value ?? new Date().getHours());
let renderedPagePalette = {};
let renderedPagePeriod = '';
let lastPageUpdate = 0;
let renderedStars;
let lastHeroUpdate = 0;
let pendingHeroPalette;
let heroUpdateTimer;
let pageUpdateTimer;

function createThemeSurface(layers) {
    let active = 0;
    let rendered = '';
    let clearTimer;

    return (background) => {
        if (rendered === background) {
            return;
        }

        if (rendered) {
            const outgoing = layers[active];
            const incoming = layers[1 - active];
            clearTimeout(clearTimer);
            incoming.style.background = background;
            incoming.style.transition = '';
            incoming.style.zIndex = '1';
            outgoing.style.zIndex = '0';
            incoming.style.opacity = '1';
            clearTimer = setTimeout(() => {
                outgoing.style.transition = 'none';
                outgoing.style.opacity = '0';
            }, 190);
            active = 1 - active;
        } else {
            layers[active].style.background = background;
        }

        rendered = background;
    };
}

function createPageThemeSurface(layers) {
    const container = layers[0].parentElement;
    let active = layers[0];
    let rendered = '';
    let target = '';
    let pending = '';
    let transitioning = false;
    let lastWeather = '';
    let cleanupTimer;
    layers.slice(1).forEach(layer => layer.remove());

    function transitionTo(background) {
        const duration = motionPreference.matches || saveData ? 0 : 900;
        const incoming = document.createElement('span');
        incoming.className = 'page-theme-layer';
        incoming.style.background = background;
        incoming.style.opacity = '0';
        container.append(incoming);
        clearTimeout(cleanupTimer);
        target = background;
        transitioning = true;

        if (duration === 0) {
            incoming.style.transition = 'none';
            incoming.style.opacity = '1';
            [...container.querySelectorAll('.page-theme-layer')].forEach(layer => {
                if (layer !== incoming) {
                    layer.remove();
                }
            });
            active = incoming;
            rendered = background;
            transitioning = false;
            return;
        }

        requestAnimationFrame(() => { incoming.style.opacity = '1'; });
        cleanupTimer = setTimeout(() => {
            [...container.querySelectorAll('.page-theme-layer')].forEach(layer => {
                if (layer !== incoming) {
                    layer.remove();
                }
            });
            active = incoming;
            rendered = background;
            transitioning = false;
            const next = pending;
            pending = '';
            if (next && next !== rendered) {
                transitionTo(next);
            }
        }, duration);
    }

    return (background, weather) => {
        const weatherChanged = weather !== lastWeather;
        lastWeather = weather;
        if (!rendered) {
            active.style.background = background;
            rendered = background;
            target = background;
            return;
        }

        if (background === target) {
            pending = '';
            return;
        }

        if (weatherChanged) {
            pending = '';
            transitionTo(background);
            return;
        }

        pending = background;
        if (!transitioning) {
            pending = '';
            transitionTo(background);
        }
    };
}

function pageBackground(palette) {
    const weather = document.body.dataset.weather ?? 'clear';
    const weatherTone = {
        rain: ['#cadde5', 34],
        snow: ['#e4f2f8', 20],
        fog: ['#d9e5e8', 32],
        storm: ['#b9cedb', 48],
    }[weather];
    const nightWeight = Math.min(100, Math.round(palette.stars / .85 * 100));
    const colors = [
        ['#fff5d7', '#f2f1e7'],
        ['#ffdcc5', '#e8dfe1'],
        ['#f7d5db', '#e2ddeb'],
        ['#dcdaf5', '#d7e0f4'],
        ['#d2e8f5', '#d4eaf3'],
    ].map(([day, night]) => {
        const timeColor = `color-mix(in srgb, ${day} ${100 - nightWeight}%, ${night})`;
        const photoColor = `color-mix(in srgb, ${timeColor} 90%, ${palette.background})`;

        return weatherTone
            ? `color-mix(in srgb, ${photoColor} ${100 - weatherTone[1]}%, ${weatherTone[0]})`
            : photoColor;
    });

    return `linear-gradient(170deg, ${colors[0]} 0%, ${colors[1]} 28%, ${colors[2]} 49%, ${colors[3]} 72%, ${colors[4]} 100%)`;
}

function dayPeriod(hour) {
    return hour < 5 || hour >= 21 ? 'night' : hour < 6 ? 'dawn' : hour < 8 ? 'morning' : hour < 18 ? 'day' : hour < 20 ? 'sunset' : 'twilight';
}

function renderPageTheme(palette, hour) {
    const period = dayPeriod(hour);
    if (renderedPagePeriod !== period) {
        document.documentElement.dataset.dayPeriod = period;
        renderedPagePeriod = period;
    }

    Object.entries(palette).forEach(([name, value]) => {
        if (renderedPagePalette[name] !== value) {
            document.documentElement.style.setProperty(`--${name}`, value);
        }
    });
    if (renderedPagePalette.background !== palette.background) {
        document.querySelector('meta[name="theme-color"]').content = palette.background;
    }

    renderedPagePalette = palette;
    lastPageUpdate = performance.now();
}

function weatherColors(palette) {
    const weather = document.body.dataset.weather ?? 'clear';
    const tint = {
        rain: { sky: '74%, #647b92', background: '86%, #71889e' },
        snow: { sky: '84%, #bbd3e1', background: '91%, #c6d9e3' },
        fog: { sky: '70%, #859ca6', background: '83%, #98abb1' },
        storm: { sky: '55%, #26394f', background: '75%, #5c6d80' },
    }[weather];
    const sky = tint ? `color-mix(in srgb, ${palette.sky} ${tint.sky})` : palette.sky;
    const background = tint ? `color-mix(in srgb, ${palette.background} ${tint.background})` : palette.background;

    return { sky, background };
}

function paintHeroTheme() {
    heroUpdateTimer = undefined;
    const palette = pendingHeroPalette;
    const { sky, background } = weatherColors(palette);
    const pageColor = `color-mix(in srgb, ${background} 94%, var(--photo-tone))`;
    const gradient = `radial-gradient(ellipse 45% 32% at 50% 47%, ${palette.glow}, transparent 90%), linear-gradient(180deg, color-mix(in srgb, ${sky} 88%, var(--photo-tone)), ${pageColor} 80%)`;
    lastHeroUpdate = performance.now();
    paintHeroSurface(gradient);
    paintPageSurface(pageBackground(palette), document.body.dataset.weather ?? 'clear');

    const stars = Math.round(palette.stars * 20) / 20;
    if (stars !== renderedStars) {
        heroElement.style.setProperty('--stars', String(stars));
        renderedStars = stars;
    }
}

function renderHeroTheme(palette) {
    pendingHeroPalette = palette;
    if (lastHeroUpdate === 0 || performance.now() - lastHeroUpdate >= 200) {
        clearTimeout(heroUpdateTimer);
        paintHeroTheme();
        return;
    }

    if (!heroUpdateTimer) {
        heroUpdateTimer = setTimeout(paintHeroTheme, 200 - (performance.now() - lastHeroUpdate));
    }
}

function renderSkyTheme(hour, playback = false) {
    const palette = skyPalette(hour);
    renderHeroTheme(palette);

    const night = String(palette.stars > .15);
    if (document.body.dataset.night !== night) {
        document.body.dataset.night = night;
        heroElement.dataset.night = night;
    }

    if (playback) {
        if (performance.now() - lastPageUpdate >= 1000 || palette.ink !== renderedPagePalette.ink) {
            renderPageTheme(palette, hour);
        }
        return;
    }

    clearTimeout(pageUpdateTimer);
    pageUpdateTimer = setTimeout(() => renderPageTheme(skyPalette(themeHour), themeHour), 250);
}
function applySkyTheme(hour, playback = false) {
    themeHour = hour;
    renderSkyTheme(hour, playback);
}
renderPageTheme(skyPalette(themeHour), themeHour);
renderSkyTheme(themeHour, true);
const initialImage = document.getElementById('scene-image');
let extension = initialImage?.currentSrc.includes('.avif?') ? 'avif' : 'webp';

function loadPhoto(frame, width = 640) {
    const source = `${base}/${frame.file}-${width}.${extension}?v=${photoRevision}`;
    if (!imageCache.has(source)) {
        const photo = new Image();
        photo.src = source;
        const pending = photo.decode().catch(async (error) => {
            if (!source.includes('.avif?')) {
                throw error;
            }
            extension = 'webp';
            photo.src = `${base}/${frame.file}-${width}.webp?v=${photoRevision}`;
            await photo.decode();
        }).then(() => photo).catch((error) => {
            imageCache.delete(source);
            throw error;
        });
        imageCache.set(source, pending);
    }
    return imageCache.get(source);
}

function samplePhotoTone(photo) {
    if (new URL(photo.src, window.location.href).origin !== window.location.origin) {
        return null;
    }

    try {
        const canvas = document.createElement('canvas');
        canvas.width = 32;
        canvas.height = 20;
        const context = canvas.getContext('2d', { willReadFrequently: true });
        if (!context) {
            return null;
        }

        context.drawImage(photo, 0, 0, canvas.width, canvas.height);
        const pixels = context.getImageData(0, 0, canvas.width, 14).data;
        const channels = [0, 0, 0];
        let samples = 0;

        for (let index = 0; index < pixels.length; index += 4) {
            if (pixels[index + 3] < 240) {
                continue;
            }

            channels[0] += pixels[index];
            channels[1] += pixels[index + 1];
            channels[2] += pixels[index + 2];
            samples++;
        }

        if (samples === 0) {
            return null;
        }

        return `#${channels.map(channel => Math.round(channel / samples).toString(16).padStart(2, '0')).join('')}`;
    } catch {
        return null;
    }
}

let displayedToneFrame;
function applyPhotoTone(photo, frame) {
    if (displayedToneFrame === frame.file) {
        return;
    }

    displayedToneFrame = frame.file;
    if (!photoToneCache.has(frame.file)) {
        photoToneCache.set(frame.file, samplePhotoTone(photo));
    }

    const tone = photoToneCache.get(frame.file);
    if (tone) {
        document.body.style.setProperty('--photo-tone', tone);
        return;
    }

    document.body.style.removeProperty('--photo-tone');
}

function createRenderer(image) {
    const originalContainer = image.parentElement;
    const stage = document.createElement('div');
    stage.className = `${originalContainer.className} photo-stage`;
    stage.style.opacity = '0';
    const banks = [0, 1].map((index) => {
        const bank = document.createElement('div');
        bank.className = 'photo-bank';
        const lower = image.cloneNode(false);
        const upper = image.cloneNode(false);
        lower.removeAttribute('id');
        lower.removeAttribute('src');
        lower.alt = '';
        lower.setAttribute('aria-hidden', 'true');
        upper.removeAttribute('src');
        upper.removeAttribute('id');
        upper.alt = '';
        upper.setAttribute('aria-hidden', 'true');
        [lower, upper].forEach((photo) => {
            photo.removeAttribute('srcset');
            photo.removeAttribute('sizes');
            photo.fetchPriority = 'low';
            photo.loading = 'eager';
        });
        upper.style.opacity = '0';
        bank.style.opacity = index === 0 ? '1' : '0';
        bank.style.zIndex = index === 0 ? '1' : '0';
        bank.append(lower, upper);
        stage.append(bank);
        return { bank, lower, upper, availableAt: 0 };
    });
    originalContainer.after(stage);
    let active = 0;
    let pairKey = '';
    let renderedWidth = 0;
    let captionReadyAt = 0;
    let sequence = 0;
    let upgradeTimer;

    async function render(pair, description, upgrade = false) {
        const request = ++sequence;
        clearTimeout(upgradeTimer);
        const reducedMotion = motionPreference.matches;
        if (reducedMotion) {
            const nearest = pair.weight < .5 ? pair.lower : pair.upper;
            pair = { lower: nearest, upper: nearest, weight: 0 };
        }
        const key = `${pair.lower.file}/${pair.upper.file}`;
        const width = upgrade ? Math.min(1536, window.innerWidth <= 700 ? 960 : 1536) : previewWidth();
        const photos = await Promise.all([loadPhoto(pair.lower, width), loadPhoto(pair.upper, width)]);
        if (request !== sequence) {
            return false;
        }
        if (key === pairKey) {
            if (upgrade || width > renderedWidth) {
                banks[active].lower.src = photos[0].src;
                banks[active].upper.src = photos[1].src;
                renderedWidth = width;
            }
            banks[active].upper.style.opacity = pair.weight;
        } else {
            const next = 1 - active;
            const wait = banks[next].availableAt - performance.now();
            if (wait > 0 && !reducedMotion) {
                await new Promise((resolve) => setTimeout(resolve, wait));
            }
            if (request !== sequence) {
                return false;
            }
            banks[next].lower.src = photos[0].src;
            banks[next].upper.src = photos[1].src;
            banks[next].upper.style.opacity = pair.weight;
            await Promise.all([banks[next].lower.decode(), banks[next].upper.decode()]);
            if (request !== sequence) {
                return false;
            }
            const outgoing = active;
            banks[outgoing].bank.style.zIndex = '0';
            banks[next].bank.style.zIndex = '1';
            banks[outgoing].availableAt = performance.now() + (reducedMotion ? 0 : 400);
            banks[next].bank.style.opacity = '1';
            stage.style.opacity = '1';
            stage.parentElement.classList.add('is-ready');
            captionReadyAt = performance.now() + (reducedMotion ? 0 : 180);
            active = next;
            setTimeout(() => {
                if (active !== outgoing) {
                    banks[outgoing].bank.style.opacity = '0';
                }
            }, reducedMotion ? 0 : 400);
            pairKey = key;
            renderedWidth = width;
        }
        const captionWait = captionReadyAt - performance.now();
        if (captionWait > 0) {
            await new Promise((resolve) => setTimeout(resolve, captionWait));
        }
        if (request !== sequence) {
            return false;
        }
        image.alt = description;
        return photos[pair.weight < .5 ? 0 : 1];
    }

    function previewWidth() {
        if (saveData || window.innerWidth <= 700) {
            return 640;
        }
        const pixels = stage.clientWidth * Math.min(window.devicePixelRatio, 1.5);
        return [640, 960, 1280, 1536].find((width) => width >= pixels) ?? 1536;
    }

    return {
        render,
        previewWidth,
        setBlendWeight(pair) {
            if (motionPreference.matches) {
                return;
            }

            if (`${pair.lower.file}/${pair.upper.file}` === pairKey) {
                banks[active].upper.style.opacity = pair.weight;
            }
        },
        upgrade(pair, description) {
            clearTimeout(upgradeTimer);
            upgradeTimer = setTimeout(() => render(pair, description, true).catch(() => {}), 450);
        },
    };
}

function createDemo({ range, images, area, feedback, frameSelections, sceneNames, onDisplay, onTimeChange, onProgress, onPause }) {
    const renderers = images.map(createRenderer);
    const hero = area.closest('.hero');
    let playing = shouldAutoplay({ reducedMotion: motionPreference.matches, saveData });
    let visible = false;
    let ready = false;
    let displayedHour = Number(range.value);
    let lastTick = 0;
    let lastThemeUpdate = 0;
    let lastProgressUpdate = 0;
    let requestedPairKey = '';
    let request = 0;
    let prefetched = '';
    let renderingHolds = 0;

    function pause() {
        playing = false;
        area.dataset.playing = 'false';
        hero.dataset.playing = 'false';
        document.body.dataset.playing = 'false';
        onPause?.();
    }

    function prefetch(pairs) {
        if (saveData) {
            return;
        }
        const key = pairs.map(pair => pair.upper.file).join('/');
        if (prefetched === key) {
            return;
        }
        prefetched = key;
        idle(() => pairs.forEach((pair, selectionIndex) => {
            const frames = frameSelections[selectionIndex]();
            const index = frames.indexOf(pair.upper);
            [frames[index], frames[(index + 1) % frames.length]].forEach(frame => {
                loadPhoto(frame, renderers[selectionIndex].previewWidth()).catch(() => {});
            });
        }));
    }

    async function display(hour, { announce = false, user = false, weatherPreview = false, keepTime = false, pauseOnFailure = true } = {}) {
        const currentRequest = ++request;
        const pairs = frameSelections.map(selection => framePair(selection(), hour));
        requestedPairKey = pairs.map(pair => `${pair.lower.file}/${pair.upper.file}`).join('|');
        const nearest = pairs.map(pair => pair.weight < .5 ? pair.lower : pair.upper);
        const descriptions = nearest.map((frame, index) =>
            `${sceneNames[index]}, ${frame.weather ?? 'sky garden'} example at ${formatExampleTime(hour * 60)}, blended between example photographs`);
        try {
            const renderedPhotos = await Promise.all(pairs.map((pair, index) =>
                renderers[index].render(pair, descriptions[index])));
            if (renderedPhotos.some(photo => !photo) || currentRequest !== request) {
                return;
            }
            if (playing && !user && !keepTime) {
                renderers.forEach((renderer, index) => {
                    renderer.setBlendWeight(framePair(frameSelections[index](), Number(range.value)));
                });
            }
            ready = true;
            displayedHour = hour;
            if (!keepTime && (!playing || user)) {
                range.value = hour;
            }
            feedback.hidden = true;
            onDisplay(playing ? Number(range.value) : hour, nearest, announce, renderedPhotos[0], weatherPreview);
            if (!playing || user) {
                renderers.forEach((renderer, index) => renderer.upgrade(pairs[index], descriptions[index]));
            }
            if (visible) {
                prefetch(pairs);
            }
            return true;
        } catch {
            if (currentRequest !== request) {
                return;
            }
            if (pauseOnFailure) {
                pause();
                range.value = displayedHour;
                onTimeChange?.(displayedHour);
            }
            requestedPairKey = '';
            feedback.textContent = 'Couldn’t load that example. Try again.';
            feedback.hidden = false;
            return 'failed';
        }
    }

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            if (visible) {
                display(Number(range.value));
            }
        }, 200);
    });
    range.addEventListener('pointerdown', pause);
    range.addEventListener('focus', pause);
    range.addEventListener('input', () => {
        pause();
        onTimeChange?.(Number(range.value));
        display(Number(range.value), { user: true });
    });
    range.addEventListener('keydown', (event) => {
        const hour = keyboardHour(event.key, Number(range.value));
        if (hour !== null) {
            event.preventDefault();
            pause();
            range.value = hour;
            onTimeChange?.(hour);
            display(hour, { user: true });
        }
    });
    motionPreference.addEventListener('change', () => {
        if (motionPreference.matches) {
            pause();
            display(Number(range.value));
        }
    });
    const observer = new IntersectionObserver((entries) => {
        visible = entries[0].isIntersecting;
        hero.dataset.visible = String(visible);
        document.body.dataset.visible = String(visible);
        lastTick = 0;
        if (visible && !ready) {
            display(Number(range.value));
        }
    }, { threshold: .15 });
    observer.observe(area);
    area.dataset.playing = String(playing);
    hero.dataset.playing = String(playing);
    document.body.dataset.playing = String(playing);
    demos.push({
        tick(now) {
            if (!playing || !visible || document.hidden || !ready) {
                lastTick = 0;
                return;
            }
            const elapsed = lastTick ? Math.min(now - lastTick, 100) : 0;
            lastTick = now;
            const nextHour = Number(range.value) + elapsed / 1800;
            const wrapped = nextHour >= 24;
            const hour = wrapped ? nextHour - 24 : nextHour;
            range.value = hour;
            if (wrapped || now - lastThemeUpdate >= 200) {
                onTimeChange?.(hour, true);
                lastThemeUpdate = now;
            }
            if (wrapped || now - lastProgressUpdate >= 80) {
                onProgress?.(hour);
                lastProgressUpdate = now;
            }
            if (renderingHolds === 0) {
                const pairs = frameSelections.map(selection => framePair(selection(), hour));
                renderers.forEach((renderer, index) => renderer.setBlendWeight(pairs[index]));
                const pairKey = pairs.map(pair => `${pair.lower.file}/${pair.upper.file}`).join('|');
                if (pairKey !== requestedPairKey) {
                    display(hour);
                }
            }
        },
    });
    return {
        display,
        pause,
        holdRendering() {
            renderingHolds++;
        },
        releaseRendering() {
            renderingHolds--;
            if (renderingHolds === 0 && playing) {
                display(Number(range.value), { keepTime: true });
            }
        },
    };
}

const scene = document.getElementById('scene');
const bridgeScene = document.getElementById('bridge-scene');
const scrubber = document.getElementById('day-scrubber');
const frameData = document.getElementById('photo-frames');
if (scene && bridgeScene && scrubber && frameData) {
    const atmosphere = createWeatherAtmosphere(heroElement, [scene, bridgeScene]);
    const photoSets = JSON.parse(frameData.textContent);
    const weatherButtons = [...document.querySelectorAll('[data-weather-choice]')];
    let weather = 'clear';
    let displayedWeather = 'clear';

    function updateTime(hour) {
        const time = formatExampleTime(hour * 60);
        const period = hour < 5 || hour >= 21 ? 'night' : hour < 12 ? 'morning' : hour < 18 ? 'afternoon' : 'evening';
        const adjective = { clear: 'Clear', rain: 'Rainy', snow: 'Snowy', fog: 'Foggy', storm: 'Stormy' }[weather];
        const label = `${adjective} ${period} example`;
        document.getElementById('scene-time').textContent = time;
        scrubber.setAttribute('aria-valuetext', `${time}, ${label.toLowerCase()}`);

        return { time, label };
    }

    function presentWeather(nextWeather, { preview = false } = {}) {
        scene.dataset.weather = nextWeather;
        bridgeScene.dataset.weather = nextWeather;
        document.body.dataset.weather = nextWeather;
        weatherButtons.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.weatherChoice === nextWeather)));
        updateTime(Number(scrubber.value));
        paintPageSurface(pageBackground(skyPalette(themeHour)), nextWeather);
        renderHeroTheme(skyPalette(themeHour));
        atmosphere.setWeather(nextWeather, { preview });
    }

    const demo = createDemo({
        range: scrubber,
        images: [document.getElementById('scene-image'), document.getElementById('bridge-image')],
        area: document.getElementById('preview'),
        feedback: document.getElementById('scene-feedback'),
        frameSelections: [
            () => photoSets.yosemite.filter(frame => frame.weather === weather),
            () => photoSets.bridge.filter(frame => frame.weather === weather),
        ],
        sceneNames: ['Yosemite Valley', 'Golden Gate Bridge'],
        onTimeChange: applySkyTheme,
        onProgress: updateTime,
        onPause: atmosphere.stop,
        onDisplay(hour, frames, announce, photo, weatherPreview) {
            applyPhotoTone(photo, frames[0]);
            displayedWeather = weather;
            if (document.body.dataset.weather !== weather) {
                presentWeather(weather, { preview: weatherPreview });
            }
            const { time, label } = updateTime(hour);
            scene.dataset.frame = frames[0].key;
            bridgeScene.dataset.frame = frames[1].key;
            if (announce) {
                document.getElementById('scene-announcement').textContent = `Yosemite Valley and Golden Gate Bridge, ${label.toLowerCase()} at ${time}.`;
            }
        },
    });
    weatherButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            demo.holdRendering();
            weather = button.dataset.weatherChoice;
            presentWeather(weather, { preview: true });
            try {
                const result = await demo.display(Number(scrubber.value), { announce: true, keepTime: true, weatherPreview: true, pauseOnFailure: false });
                if (result === 'failed' && weather === button.dataset.weatherChoice) {
                    weather = displayedWeather;
                    presentWeather(weather);
                }
            } finally {
                demo.releaseRendering();
            }
        });
    });
}

function animate(now) {
    demos.forEach((demo) => demo.tick(now));
    requestAnimationFrame(animate);
}
requestAnimationFrame(animate);
