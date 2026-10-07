import { framePair, keyboardHour, shouldAutoplay } from './photo-demo';
import { prepareFaq } from './faq';
import { skyPalette } from './sky-theme';
import { pageColors } from './page-theme';
import { createWeatherAtmosphere } from './weather-atmosphere';
import { prepareDownloadCelebration } from './download-celebration';
import './site-header';

const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
const saveData = navigator.connection?.saveData === true;
if (saveData) {
    document.documentElement.classList.add('save-data');
}
const baseElement = document.getElementById('photo-base');
const { url: base, revision: photoRevision } = baseElement ? JSON.parse(baseElement.textContent) : { url: '', revision: '' };
const imageCache = new Map();
const photoToneCache = new Map();
const demos = [];
const heroElement = document.querySelector('.hero');
const heroTheme = heroElement.querySelector('.hero-theme');
const pageTheme = document.querySelector('.page-theme');
const idle = (callback) => window.requestIdleCallback
    ? window.requestIdleCallback(callback, { timeout: 250 }) : setTimeout(callback, 200);
let themeHour = Number(document.getElementById('day-scrubber')?.value ?? new Date().getHours());
let renderedPagePalette = {};
let renderedPagePeriod = '';

function paintPageSurface(colors) {
    colors.forEach((color, index) => {
        pageTheme.style.setProperty(`--page-stop-${index + 1}`, color);
    });
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
}

function weatherColors(palette) {
    const weather = document.body.dataset.weather ?? 'clear';
    const tint = {
        rain: { sky: ['#647b92', '#152945', 74], background: ['#71889e', '#1d304a', 86] },
        snow: { sky: ['#bbd3e1', '#3d5069', 84], background: ['#c6d9e3', '#344760', 91] },
        fog: { sky: ['#859ca6', '#263d52', 70], background: ['#98abb1', '#304459', 83] },
        storm: { sky: ['#26394f', '#0a172b', 55], background: ['#5c6d80', '#192b43', 75] },
    }[weather];
    if (!tint) {
        return { sky: palette.sky, background: palette.background };
    }

    const nightWeight = Math.min(100, palette.stars * 100);
    const mixWeatherColor = (color, [day, night, baseWeight]) =>
        `color-mix(in srgb, ${color} ${baseWeight}%, color-mix(in srgb, ${day} ${100 - nightWeight}%, ${night}))`;
    const sky = mixWeatherColor(palette.sky, tint.sky);
    const background = mixWeatherColor(palette.background, tint.background);

    return { sky, background };
}

function renderHeroTheme(palette) {
    const { sky, background } = weatherColors(palette);
    const pageColor = `color-mix(in srgb, ${background} 94%, var(--photo-tone))`;
    heroTheme.style.setProperty('--hero-glow', palette.glow);
    heroTheme.style.setProperty('--hero-sky', `color-mix(in srgb, ${sky} 88%, var(--photo-tone))`);
    heroTheme.style.setProperty('--hero-background', pageColor);
    paintPageSurface(pageColors(palette, document.body.dataset.weather));
    heroElement.style.setProperty('--stars', String(palette.stars));
}

function renderSkyTheme(hour) {
    const palette = skyPalette(hour);
    renderPageTheme(palette, hour);
    renderHeroTheme(palette);

    const night = String(palette.stars > .15);
    if (document.body.dataset.night !== night) {
        document.body.dataset.night = night;
        heroElement.dataset.night = night;
    }
}
function applySkyTheme(hour) {
    themeHour = hour;
    renderSkyTheme(hour);
}
renderSkyTheme(themeHour);
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
        return { bank, lower, upper };
    });
    originalContainer.after(stage);
    let active = 0;
    let pairKey = '';
    let renderedWidth = 0;
    let sequence = 0;
    let transitionFinished;

    async function prepare(pair, description) {
        const request = ++sequence;
        const reducedMotion = motionPreference.matches;
        if (reducedMotion) {
            const nearest = pair.weight < .5 ? pair.lower : pair.upper;
            pair = { lower: nearest, upper: nearest, weight: 0 };
        }
        const key = `${pair.lower.file}/${pair.upper.file}`;
        const width = previewWidth();
        const photos = await Promise.all([loadPhoto(pair.lower, width), loadPhoto(pair.upper, width)]);
        if (request !== sequence) {
            return null;
        }
        const transition = key !== pairKey || width > renderedWidth;
        const next = transition ? 1 - active : active;
        if (transition) {
            if (transitionFinished) {
                await transitionFinished;
            }
            if (request !== sequence) {
                return null;
            }
            banks[next].lower.src = photos[0].src;
            banks[next].upper.src = photos[1].src;
            banks[next].upper.style.opacity = pair.weight;
            await Promise.all([banks[next].lower.decode(), banks[next].upper.decode()]);
            if (request !== sequence) {
                return null;
            }
        }

        return {
            transition,
            isCurrent: () => request === sequence,
            commit() {
                if (request !== sequence) {
                    return false;
                }

                if (transition) {
                    const outgoing = active;
                    banks[outgoing].bank.style.zIndex = '0';
                    banks[next].bank.style.zIndex = '1';
                    stage.style.opacity = '1';
                    stage.parentElement.classList.add('is-ready');
                    active = next;
                    if (reducedMotion) {
                        banks[next].bank.style.opacity = '1';
                        banks[outgoing].bank.style.opacity = '0';
                    } else {
                        const animation = banks[next].bank.animate([
                            { opacity: 0 },
                            { opacity: 1 },
                        ], { duration: 220, easing: 'ease-out', fill: 'forwards' });
                        const finish = () => {
                            banks[next].bank.style.opacity = '1';
                            animation.cancel();
                            banks[outgoing].bank.style.opacity = '0';
                            transitionFinished = undefined;
                        };
                        transitionFinished = animation.finished.then(finish, finish);
                    }
                    pairKey = key;
                    renderedWidth = width;
                } else {
                    banks[active].upper.style.opacity = pair.weight;
                }

                image.alt = description;
                return photos[pair.weight < .5 ? 0 : 1];
            },
        };
    }

    function previewWidth() {
        if (saveData || window.innerWidth <= 700) {
            return 640;
        }
        const pixels = stage.clientWidth * Math.min(window.devicePixelRatio, 2);
        return [640, 960, 1280, 1536].find((width) => width >= pixels) ?? 1536;
    }

    return {
        prepare,
        previewWidth,
        setBlendWeight(pair) {
            if (motionPreference.matches) {
                return;
            }

            if (`${pair.lower.file}/${pair.upper.file}` === pairKey) {
                banks[active].upper.style.opacity = pair.weight;
            }
        },
    };
}

function createDemo({ range, images, area, feedback, frameSelections, sceneNames, onDisplay, onTimeChange, onProgress, onClockChange, onPause }) {
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
            const prepared = await Promise.all(pairs.map((pair, index) =>
                renderers[index].prepare(pair, descriptions[index])));
            if (prepared.some(result => !result?.isCurrent()) || currentRequest !== request) {
                return;
            }
            await new Promise(resolve => requestAnimationFrame(resolve));
            if (prepared.some(result => !result.isCurrent()) || currentRequest !== request) {
                return;
            }
            const renderedPhotos = prepared.map(result => result.commit());
            if (renderedPhotos.some(photo => !photo)) {
                return;
            }
            if (currentRequest !== request) {
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
            onDisplay(playing ? Number(range.value) : hour, nearest, announce, renderedPhotos, weatherPreview);
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
            feedback.textContent = 'That preview didn’t load. Try another time or weather.';
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
        onClockChange?.(Number(range.value));
        onProgress?.(Number(range.value));
        display(Number(range.value), { user: true });
    });
    range.addEventListener('keydown', (event) => {
        const hour = keyboardHour(event.key, Number(range.value));
        if (hour !== null) {
            event.preventDefault();
            pause();
            range.value = hour;
            onTimeChange?.(hour);
            onClockChange?.(hour);
            onProgress?.(hour);
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
        if (visible) {
            display(Number(range.value));
        }
    }, { threshold: .15 });
    observer.observe(area);
    area.dataset.playing = String(playing);
    hero.dataset.playing = String(playing);
    document.body.dataset.playing = String(playing);
    demos.push({
        tick(now) {
            if (!playing || document.hidden || !ready || !visible) {
                lastTick = 0;
                return;
            }
            const elapsed = lastTick ? Math.min(now - lastTick, 100) : 0;
            lastTick = now;
            const nextHour = Number(range.value) + elapsed / 1800;
            const wrapped = nextHour >= 24;
            const hour = wrapped ? nextHour - 24 : nextHour;
            range.value = hour;
            onClockChange?.(hour);
            if (wrapped || now - lastThemeUpdate >= 40) {
                onTimeChange?.(hour);
                lastThemeUpdate = now;
            }
            if (wrapped || now - lastProgressUpdate >= 80) {
                onProgress?.(hour);
                lastProgressUpdate = now;
            }
            if (visible && renderingHolds === 0) {
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
    const sceneClock = document.getElementById('scene-time');
    let weather = 'clear';
    let displayedWeather = 'clear';

    function updateClockHour(hour) {
        sceneClock.style.setProperty('--clock-hour-angle', `${hour * 30}deg`);
    }

    function updateTime(hour) {
        const time = formatExampleTime(hour * 60);
        const period = hour < 5 || hour >= 21 ? 'night' : hour < 12 ? 'morning' : hour < 18 ? 'afternoon' : 'evening';
        const adjective = { clear: 'Clear', rain: 'Rainy', snow: 'Snowy', fog: 'Foggy', storm: 'Stormy' }[weather];
        const label = `${adjective} ${period} example`;
        sceneClock.setAttribute('aria-label', `${time} in the wallpaper preview`);
        scrubber.setAttribute('aria-valuetext', `${time}, ${label.toLowerCase()}`);

        return { time, label };
    }

    function presentWeather(nextWeather, { preview = false } = {}) {
        scene.dataset.weather = nextWeather;
        bridgeScene.dataset.weather = nextWeather;
        document.body.dataset.weather = nextWeather;
        weatherButtons.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.weatherChoice === nextWeather)));
        updateTime(Number(scrubber.value));
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
        onClockChange: updateClockHour,
        onPause: atmosphere.stop,
        onDisplay(hour, frames, announce, photos, weatherPreview) {
            applyPhotoTone(photos[0], frames[0]);
            scene.style.backgroundImage = `url("${photos[0].src}")`;
            bridgeScene.style.backgroundImage = `url("${photos[1].src}")`;
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

function prepareScrollReveals() {
    if (motionPreference.matches || saveData || !('IntersectionObserver' in window)
        || !Element.prototype.animate) {
        return;
    }

    const selectors = [
        '.section-heading > *',
        '.setup-steps > li',
        '.prompt-card',
        '.appearance-example',
        '.makers-copy > *',
        '.maker-card',
        '.maker-projects > h3',
        '.maker-project-list > li',
        '.closing-cta-inner',
        '.footer-main > *',
        '.footer-page-links > a',
    ];
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.animate([
                    { opacity: .88, transform: 'translate3d(0, 8px, 0)' },
                    { opacity: 1, transform: 'translate3d(0, 0, 0)' },
                ], {
                    duration: 380,
                    easing: 'cubic-bezier(.2, 0, 0, 1)',
                    fill: 'backwards',
                });
                observer.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -48px 0px', threshold: .08 });

    document.querySelectorAll(selectors.join(',')).forEach(element => {
        const bounds = element.getBoundingClientRect();
        if (bounds.top < window.innerHeight - 32 || bounds.bottom < 0) {
            return;
        }

        observer.observe(element);
    });
}

prepareScrollReveals();
prepareFaq(motionPreference);
prepareDownloadCelebration(motionPreference, saveData);

function animate(now) {
    demos.forEach((demo) => demo.tick(now));
    requestAnimationFrame(animate);
}
requestAnimationFrame(animate);
