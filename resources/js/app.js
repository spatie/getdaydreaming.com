import { framePair, keyboardHour, shouldAutoplay } from './photo-demo';
import { skyPalette } from './sky-theme';

const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
const saveData = navigator.connection?.saveData === true;
const baseElement = document.getElementById('photo-base');
const base = baseElement ? JSON.parse(baseElement.textContent) : '';
const imageCache = new Map();
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
let extension = initialImage?.currentSrc.endsWith('.avif') ? 'avif' : 'webp';

function loadPhoto(frame, width = 640) {
    const source = `${base}/${frame.file}-${width}.${extension}`;
    if (!imageCache.has(source)) {
        const photo = new Image();
        photo.src = source;
        const pending = photo.decode().catch(async (error) => {
            if (!source.endsWith('.avif')) {
                throw error;
            }
            extension = 'webp';
            photo.src = `${base}/${frame.file}-${width}.webp`;
            await photo.decode();
        }).then(() => photo).catch((error) => {
            imageCache.delete(source);
            throw error;
        });
        imageCache.set(source, pending);
    }
    return imageCache.get(source);
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
        return true;
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

function createDemo({ range, image, area, feedback, framesForSelection, sceneName, onDisplay, onTimeChange, onDayComplete }) {
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

    function pause() {
        playing = false;
        area.dataset.playing = 'false';
        hero.dataset.playing = 'false';
        document.body.dataset.playing = 'false';
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

    async function display(hour, { announce = false, user = false } = {}) {
        const currentRequest = ++request;
        const pair = framePair(framesForSelection(), hour);
        const nearest = pair.weight < .5 ? pair.lower : pair.upper;
        const description = `${sceneName()}, ${nearest.weather ?? 'sky garden'} example at ${formatExampleTime(hour * 60)}, blended between example photographs`;
        try {
            if (!await renderer.render(pair, description) || currentRequest !== request) {
                return;
            }
            ready = true;
            displayedHour = hour;
            range.value = hour;
            feedback.hidden = true;
            onDisplay(hour, nearest, announce);
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
            pause();
            range.value = displayedHour;
            onTimeChange?.(displayedHour);
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
                onDayComplete?.();
            }
            range.value = hour;
            onTimeChange?.(hour);
            if (now - lastRender > 80) {
                lastRender = now;
                display(hour);
            }
        },
    });
    return { display, pause };
}

const scene = document.getElementById('scene');
const scrubber = document.getElementById('day-scrubber');
const frameData = document.getElementById('photo-frames');
if (scene && scrubber && frameData) {
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
        onDayComplete() {
            const choices = weatherButtons.map((button) => button.dataset.weatherChoice);
            const nextWeather = (choices.indexOf(weather) + 1) % choices.length;
            weather = choices[nextWeather];
            if (nextWeather === 0) {
                picture = picture === 'bridge' ? 'yosemite' : 'bridge';
            }
        },
        onDisplay(hour, frame, announce) {
            const time = formatExampleTime(hour * 60);
            const period = hour < 5 || hour >= 21 ? 'night' : hour < 12 ? 'morning' : hour < 18 ? 'afternoon' : 'evening';
            const adjective = { clear: 'Clear', rain: 'Rainy', snow: 'Snowy', fog: 'Foggy', storm: 'Stormy' }[weather];
            const label = `${adjective} ${period} example`;
            displayedWeather = weather;
            displayedPicture = picture;
            document.getElementById('desktop-clock').textContent = time;
            document.getElementById('scene-time').textContent = time;
            document.getElementById('scene-weather').textContent = label;
            scrubber.setAttribute('aria-valuetext', `${time}, ${label.toLowerCase()}`);
            scene.dataset.weather = weather;
            document.body.dataset.weather = weather;
            scene.dataset.frame = frame.key;
            scene.dataset.picture = picture;
            pictureButtons.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.pictureChoice === picture)));
            const original = picture === 'bridge' ? 'bridge-day' : 'yosemite-original';
            const originalAvif = document.getElementById('original-avif');
            if (originalAvif.dataset.failed !== 'true') {
                originalAvif.srcset = `${base}/${original}-320.avif`;
            }
            document.getElementById('original-image').src = `${base}/${original}-320.webp`;
            document.getElementById('original-image').alt = `Original ${picture === 'bridge' ? 'Golden Gate Bridge' : 'Yosemite Valley'} photograph`;
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
        button.addEventListener('focus', demo.pause);
        button.addEventListener('click', async () => {
            if (button.dataset.pictureChoice === picture) {
                return;
            }
            demo.pause();
            picture = button.dataset.pictureChoice;
            const result = await demo.display(Number(scrubber.value), { announce: true, user: true });
            if (result === 'failed' && picture === button.dataset.pictureChoice) {
                picture = displayedPicture;
            }
        });
    });
    weatherButtons.forEach((button) => {
        button.addEventListener('focus', demo.pause);
        button.addEventListener('click', async () => {
            demo.pause();
            weather = button.dataset.weatherChoice;
            const result = await demo.display(Number(scrubber.value), { announce: true, user: true });
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
