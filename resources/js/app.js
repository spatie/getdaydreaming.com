import { framePair, keyboardHour, shouldAutoplay } from './photo-demo';
import { skyPalette } from './sky-theme';

const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
const saveData = navigator.connection?.saveData === true;
const baseElement = document.getElementById('photo-base');
const base = baseElement ? JSON.parse(baseElement.textContent) : '';
const imageCache = new Map();
const demos = [];
const idle = (callback) => window.requestIdleCallback ? window.requestIdleCallback(callback) : setTimeout(callback, 200);
let themeHour = Number(document.getElementById('day-scrubber')?.value ?? new Date().getHours());
let themeAnimation;
function renderSkyTheme(hour) {
    const palette = skyPalette(hour);
    Object.entries(palette).forEach(([name, value]) => {
        document.documentElement.style.setProperty(`--${name}`, value);
    });
    document.querySelector('meta[name="theme-color"]').content = palette.background;
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
if (initialImage) {
    await initialImage.decode().catch(() => {});
}
const extension = initialImage?.currentSrc.endsWith('.avif') ? 'avif' : 'webp';

function loadPhoto(frame, width = 640) {
    const source = `${base}/${frame.file}-${width}.${extension}`;
    if (!imageCache.has(source)) {
        const photo = new Image();
        photo.src = source;
        const pending = photo.decode().then(() => photo).catch((error) => {
            imageCache.delete(source);
            throw error;
        });
        imageCache.set(source, pending);
    }
    return imageCache.get(source);
}

function createRenderer(image) {
    const originalContainer = image.parentElement;
    const originalSource = image.currentSrc || image.src;
    const stage = document.createElement('div');
    stage.className = `${originalContainer.className} photo-stage`;
    const banks = [0, 1].map((index) => {
        const bank = document.createElement('div');
        bank.className = 'photo-bank';
        const lower = index === 0 ? image : image.cloneNode(false);
        const upper = image.cloneNode(false);
        if (index !== 0) {
            lower.removeAttribute('id');
            lower.alt = '';
            lower.setAttribute('aria-hidden', 'true');
        }
        upper.removeAttribute('src');
        if (index !== 0) {
            lower.removeAttribute('src');
        }
        upper.removeAttribute('id');
        upper.alt = '';
        upper.setAttribute('aria-hidden', 'true');
        [lower, upper].forEach((photo) => {
            photo.removeAttribute('srcset');
            photo.removeAttribute('sizes');
            photo.fetchPriority = 'low';
            photo.loading = 'eager';
        });
        if (index === 0 && originalSource) {
            lower.src = originalSource;
        }
        upper.style.opacity = '0';
        bank.style.opacity = index === 0 ? '1' : '0';
        bank.style.zIndex = index === 0 ? '1' : '0';
        bank.append(lower, upper);
        stage.append(bank);
        return { bank, lower, upper, availableAt: 0 };
    });
    originalContainer.replaceWith(stage);
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

function createDemo({ range, image, area, playButton, feedback, announcement, framesForSelection, sceneName, onDisplay, onTimeChange, onDayComplete }) {
    const renderer = createRenderer(image);
    let playing = shouldAutoplay({ reducedMotion: motionPreference.matches, saveData });
    let visible = false;
    let ready = false;
    let displayedHour = Number(range.value);
    let lastTick = 0;
    let lastRender = 0;
    let lastThemeUpdate = 0;
    let request = 0;
    let prefetched = '';

    function updatePlayButton() {
        playButton.querySelector('[data-play-label]').textContent = playing ? 'Pause' : 'Play';
        playButton.querySelector('[data-play-icon]').setAttribute('d', playing ? 'M7 5v14M17 5v14' : 'M8 5l11 7-11 7Z');
        playButton.setAttribute('aria-label', `${playing ? 'Pause' : 'Play'} ${playButton.dataset.play === 'hero' ? 'the day' : 'the sky garden'}`);
        area.dataset.playing = String(playing);
    }

    function pause() {
        playing = false;
        updatePlayButton();
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
    playButton.addEventListener('click', () => {
        playing = !playing;
        updatePlayButton();
        announcement.textContent = playing ? 'Playing the examples.' : 'Examples paused.';
        lastTick = 0;
        if (playing) {
            prefetch(framePair(framesForSelection(), Number(range.value)));
        } else {
            display(Number(range.value), { user: true });
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
        lastTick = 0;
        if (visible && !ready) {
            display(Number(range.value));
        }
    }, { threshold: .15 });
    observer.observe(area);
    updatePlayButton();
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
            if (now - lastThemeUpdate > 250) {
                lastThemeUpdate = now;
                onTimeChange?.(hour);
            }
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
        playButton: document.querySelector('[data-play="hero"]'),
        feedback: document.getElementById('scene-feedback'),
        announcement: document.getElementById('scene-announcement'),
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
            scene.dataset.frame = frame.key;
            scene.dataset.picture = picture;
            pictureButtons.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.pictureChoice === picture)));
            const original = picture === 'bridge' ? 'bridge-day' : 'yosemite-original';
            document.getElementById('original-avif').srcset = `${base}/${original}-320.avif`;
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

const wildFrameData = document.getElementById('wild-frames');
if (wildFrameData) {
    const frames = JSON.parse(wildFrameData.textContent).map((frame) => ({ ...frame, minutes: frame.hour * 60 }));
    const range = document.getElementById('wild-scrubber');
    const buttons = [...document.querySelectorAll('[data-wild-hour]')];
    const strip = document.querySelector('.wild-grid');
    const mobile = () => window.matchMedia('(max-width: 700px)').matches;
    let selectedHour = new Date().getHours();
    range.value = selectedHour;
    const initialCaption = `${formatExampleTime(selectedHour * 60)}, sky garden example`;
    range.setAttribute('aria-valuetext', initialCaption);
    document.getElementById('wild-caption').textContent = initialCaption;
    function updatePartialLabels() {
        const bounds = strip.getBoundingClientRect();
        buttons.forEach((button) => {
            const thumbnail = button.getBoundingClientRect();
            button.dataset.partial = String(mobile() && (thumbnail.left < bounds.left || thumbnail.right > bounds.right));
        });
    }
    function centerSelectedThumbnail(hour) {
        if (mobile()) {
            const button = buttons[hour];
            strip.scrollLeft = button.offsetLeft - strip.offsetLeft - (strip.clientWidth - button.clientWidth) / 2;
            updatePartialLabels();
        }
    }
    strip.addEventListener('scroll', updatePartialLabels, { passive: true });
    window.addEventListener('resize', () => {
        centerSelectedThumbnail(selectedHour);
        updatePartialLabels();
    });
    buttons.forEach((button) => {
        const hour = Number(button.dataset.wildHour);
        button.querySelector('[data-hour-label]').textContent = formatExampleTime(hour * 60);
        button.setAttribute('aria-label', `${formatExampleTime(hour * 60)}, sky garden example`);
        button.tabIndex = hour === selectedHour ? 0 : -1;
        button.setAttribute('aria-pressed', String(hour === selectedHour));
    });
    centerSelectedThumbnail(selectedHour);
    const demo = createDemo({
        range,
        image: document.getElementById('wild-image'),
        area: document.querySelector('.wild-preview'),
        playButton: document.querySelector('[data-play="wild"]'),
        feedback: document.getElementById('wild-feedback'),
        announcement: document.getElementById('wild-status'),
        framesForSelection: () => frames,
        sceneName: () => 'Golden Gate Bridge',
        onDisplay(hour, frame, announce) {
            const previousHour = selectedHour;
            selectedHour = frame.hour;
            const caption = `${formatExampleTime(hour * 60)}, sky garden example`;
            document.getElementById('wild-caption').textContent = caption;
            range.setAttribute('aria-valuetext', caption);
            buttons.forEach((button) => {
                const selected = Number(button.dataset.wildHour) === selectedHour;
                button.tabIndex = selected ? 0 : -1;
                button.setAttribute('aria-pressed', String(selected));
            });
            if (previousHour !== selectedHour) {
                centerSelectedThumbnail(selectedHour);
            }
            if (announce) {
                document.getElementById('wild-status').textContent = caption;
            }
        },
    });
    strip.addEventListener('pointerdown', demo.pause);
    const thumbnailObserver = new IntersectionObserver((entries) => {
        entries.filter((entry) => entry.isIntersecting).forEach(({ target: thumbnail }) => {
            thumbnail.parentElement.querySelector('source').srcset = thumbnail.parentElement.querySelector('source').dataset.srcset;
            thumbnail.src = thumbnail.dataset.src;
            thumbnailObserver.unobserve(thumbnail);
        });
    }, { rootMargin: '100px' });
    buttons.forEach((button) => {
        thumbnailObserver.observe(button.querySelector('img'));
        button.addEventListener('click', () => {
            demo.pause();
            demo.display(Number(button.dataset.wildHour), { announce: true, user: true });
        });
        button.addEventListener('keydown', (event) => {
            const columns = mobile() ? 1 : 4;
            const offset = { ArrowRight: 1, ArrowLeft: -1, ArrowDown: columns, ArrowUp: -columns }[event.key];
            if (offset === undefined && event.key !== 'Home' && event.key !== 'End') {
                return;
            }
            event.preventDefault();
            demo.pause();
            const hour = event.key === 'Home' ? 0 : event.key === 'End' ? 23 : (Number(button.dataset.wildHour) + offset + 24) % 24;
            buttons[hour].focus();
            centerSelectedThumbnail(hour);
            demo.display(hour, { announce: true, user: true });
        });
    });
}

function animate(now) {
    demos.forEach((demo) => demo.tick(now));
    requestAnimationFrame(animate);
}
requestAnimationFrame(animate);
