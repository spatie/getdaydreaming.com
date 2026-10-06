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
const idle = (callback) => window.requestIdleCallback ? window.requestIdleCallback(callback) : setTimeout(callback, 200);
let themeHour = Number(document.getElementById('day-scrubber')?.value ?? new Date().getHours());
let themeAnimation;
let renderedPalette = {};
function renderSkyTheme(hour) {
    const palette = skyPalette(hour);
    Object.entries(palette).forEach(([name, value]) => {
        if (renderedPalette[name] !== value) {
            document.documentElement.style.setProperty(`--${name}`, value);
        }
    });
    if (renderedPalette.background !== palette.background) {
        document.querySelector('meta[name="theme-color"]').content = palette.background;
    }
    const night = String(palette.stars > .15);
    if (document.body.dataset.night !== night) {
        document.body.dataset.night = night;
        heroElement.dataset.night = night;
    }
    renderedPalette = palette;
}
function applySkyTheme(hour) {
    cancelAnimationFrame(themeAnimation);
    let distance = hour - themeHour;
    if (distance > 12) {
        distance -= 24;
    }
    if (distance < -12) {
        distance += 24;
    }
    if (Math.abs(distance) < .75 || motionPreference.matches) {
        themeHour = hour;
        renderSkyTheme(hour);
        return;
    }
    const startHour = themeHour;
    const started = performance.now();
    const duration = Math.min(700, 320 + Math.abs(distance) * 25);
    function animateTheme(now) {
        const progress = Math.min(1, (now - started) / duration);
        const eased = 1 - (1 - progress) ** 3;
        themeHour = startHour + distance * eased;
        renderSkyTheme(themeHour);
        if (progress < 1) {
            themeAnimation = requestAnimationFrame(animateTheme);
        } else {
            themeHour = hour;
        }
    }
    themeAnimation = requestAnimationFrame(animateTheme);
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
        upgrade(pair, description) {
            clearTimeout(upgradeTimer);
            upgradeTimer = setTimeout(() => render(pair, description, true).catch(() => {}), 450);
        },
    };
}

function createDemo({ range, image, area, feedback, framesForSelection, sceneName, onDisplay, onTimeChange, onDayComplete, onPause }) {
    const renderer = createRenderer(image);
    const hero = area.closest('.hero');
    let playing = shouldAutoplay({ reducedMotion: motionPreference.matches, saveData });
    let visible = false;
    let ready = false;
    let displayedHour = Number(range.value);
    let lastTick = 0;
    let lastRender = 0;
    let request = 0;
    let prefetched = '';
    let renderingHolds = 0;
    let pendingDayCompletions = 0;

    function pause() {
        playing = false;
        area.dataset.playing = 'false';
        hero.dataset.playing = 'false';
        document.body.dataset.playing = 'false';
        onPause?.();
    }

    function prefetch(pair) {
        if (saveData) {
            return;
        }
        const frames = framesForSelection();
        const key = pair.upper.file;
        if (prefetched === key) {
            return;
        }
        prefetched = key;
        const index = frames.indexOf(pair.upper);
        idle(() => [frames[index], frames[(index + 1) % frames.length]].forEach((frame) => loadPhoto(frame, renderer.previewWidth()).catch(() => {})));
    }

    async function display(hour, { announce = false, user = false, weatherPreview = false, keepTime = false, pauseOnFailure = true } = {}) {
        const currentRequest = ++request;
        const pair = framePair(framesForSelection(), hour);
        const nearest = pair.weight < .5 ? pair.lower : pair.upper;
        const description = `${sceneName()}, ${nearest.weather ?? 'sky garden'} example at ${formatExampleTime(hour * 60)}, blended between example photographs`;
        try {
            const renderedPhoto = await renderer.render(pair, description);
            if (!renderedPhoto || currentRequest !== request) {
                return;
            }
            ready = true;
            displayedHour = hour;
            if (!keepTime) {
                range.value = hour;
            }
            feedback.hidden = true;
            onDisplay(hour, nearest, announce, renderedPhoto, weatherPreview);
            if (!playing || user) {
                renderer.upgrade(pair, description);
            }
            if (visible) {
                prefetch(pair);
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
            let hour = Number(range.value) + elapsed / 1250;
            if (hour > 24) {
                hour = 0;
                if (renderingHolds > 0) {
                    pendingDayCompletions++;
                } else {
                    onDayComplete?.();
                }
            }
            range.value = hour;
            onTimeChange?.(hour);
            if (renderingHolds === 0 && now - lastRender > 80) {
                lastRender = now;
                display(hour);
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
            if (renderingHolds === 0 && pendingDayCompletions > 0) {
                for (let completion = 0; completion < pendingDayCompletions; completion++) {
                    onDayComplete?.();
                }
                pendingDayCompletions = 0;
                display(Number(range.value));
            }
        },
    };
}

const scene = document.getElementById('scene');
const scrubber = document.getElementById('day-scrubber');
const frameData = document.getElementById('photo-frames');
if (scene && scrubber && frameData) {
    const atmosphere = createWeatherAtmosphere(heroElement, scene);
    const photoSets = JSON.parse(frameData.textContent);
    const pictureButtons = [...document.querySelectorAll('[data-picture-choice]')];
    const weatherButtons = [...document.querySelectorAll('[data-weather-choice]')];
    let picture = 'bridge';
    let displayedPicture = 'bridge';
    let weather = 'clear';
    let displayedWeather = 'clear';
    const demo = createDemo({
        range: scrubber,
        image: document.getElementById('scene-image'),
        area: scene,
        feedback: document.getElementById('scene-feedback'),
        framesForSelection: () => photoSets[picture].filter(frame => frame.weather === weather),
        sceneName: () => picture === 'bridge' ? 'Golden Gate Bridge' : 'Yosemite Valley',
        onTimeChange: applySkyTheme,
        onPause: atmosphere.stop,
        onDayComplete() {
            const choices = weatherButtons.map((button) => button.dataset.weatherChoice);
            const nextWeather = (choices.indexOf(weather) + 1) % choices.length;
            weather = choices[nextWeather];
            if (nextWeather === 0) {
                picture = picture === 'bridge' ? 'yosemite' : 'bridge';
            }
        },
        onDisplay(hour, frame, announce, photo, weatherPreview) {
            applyPhotoTone(photo, frame);
            const time = formatExampleTime(hour * 60);
            const period = hour < 5 || hour >= 21 ? 'night' : hour < 12 ? 'morning' : hour < 18 ? 'afternoon' : 'evening';
            const adjective = { clear: 'Clear', rain: 'Rainy', snow: 'Snowy', fog: 'Foggy', storm: 'Stormy' }[weather];
            const label = `${adjective} ${period} example`;
            displayedWeather = weather;
            displayedPicture = picture;
            document.getElementById('desktop-clock').textContent = time;
            document.getElementById('scene-time').textContent = time;
            scrubber.setAttribute('aria-valuetext', `${time}, ${label.toLowerCase()}`);
            scene.dataset.weather = weather;
            document.body.dataset.weather = weather;
            atmosphere.setWeather(weather, { preview: weatherPreview });
            scene.dataset.frame = frame.key;
            scene.dataset.picture = picture;
            pictureButtons.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.pictureChoice === picture)));
            const icon = document.querySelector(`[data-icon-template="${weather === 'clear' && (hour < 6 || hour >= 21) ? 'night' : weather}"]`);
            document.getElementById('desktop-weather-icon').replaceChildren(icon.content.cloneNode(true));
            weatherButtons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.weatherChoice === weather)));
            if (announce) {
                const subject = picture === 'bridge' ? 'Golden Gate Bridge' : 'Yosemite Valley';
                document.getElementById('scene-announcement').textContent = `${subject}, ${label.toLowerCase()} at ${time}.`;
            }
        },
    });
    pictureButtons.forEach(button => {
        button.addEventListener('click', async () => {
            if (button.dataset.pictureChoice === picture) {
                return;
            }
            demo.holdRendering();
            picture = button.dataset.pictureChoice;
            try {
                const result = await demo.display(Number(scrubber.value), { announce: true, keepTime: true, pauseOnFailure: false });
                if (result === 'failed' && picture === button.dataset.pictureChoice) {
                    picture = displayedPicture;
                }
            } finally {
                demo.releaseRendering();
            }
        });
    });
    weatherButtons.forEach((button) => {
        button.addEventListener('focus', demo.pause);
        button.addEventListener('click', async () => {
            demo.pause();
            weather = button.dataset.weatherChoice;
            const result = await demo.display(Number(scrubber.value), { announce: true, user: true, weatherPreview: true });
            if (result === 'failed' && weather === button.dataset.weatherChoice) {
                weather = displayedWeather;
            }
        });
    });
}

function animate(now) {
    demos.forEach((demo) => demo.tick(now));
    requestAnimationFrame(animate);
}
requestAnimationFrame(animate);
