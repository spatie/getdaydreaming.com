const scene = document.getElementById('scene');
const scrubber = document.getElementById('day-scrubber');
const frameData = document.getElementById('photo-frames');
const weatherButtons = document.querySelectorAll('[data-weather-choice]');

if (scene && scrubber && frameData) {
    const frames = JSON.parse(frameData.textContent);
    const base = JSON.parse(document.getElementById('photo-base').textContent);
    const clearFrames = frames.filter((frame) => frame.weather === 'clear');
    const image = document.getElementById('scene-image');
    const feedback = document.getElementById('scene-feedback');
    const announcement = document.getElementById('scene-announcement');
    let requestNumber = 0;
    let displayedScrubberValue = scrubber.value;

    const srcset = (frame, extension) => {
        const widths = [640, 960, 1280, 1536];
        return widths.map((width) => `${base}/${frame.file}-${width}.${extension} ${width}w`).join(', ');
    };

    function displayFrame(frame, sourceImage = null) {
        if (sourceImage) {
            image.parentElement.querySelectorAll('source').forEach((source) => source.remove());
            image.srcset = sourceImage.srcset;
            image.sizes = sourceImage.sizes;
            image.src = sourceImage.currentSrc;
        }
        image.alt = frame.alt;
        scene.dataset.frame = frame.key;
        scene.dataset.weather = frame.weather;
        scene.removeAttribute('aria-busy');
        displayedScrubberValue = scrubber.value;
        feedback.hidden = true;
        feedback.textContent = '';

        const time = formatExampleTime(frame.minutes);
        document.getElementById('desktop-clock').textContent = time;
        document.getElementById('scene-time').textContent = time;
        document.getElementById('scene-weather').textContent = `${frame.label} example`;
        scrubber.setAttribute('aria-valuetext', `${time}, ${frame.label.toLowerCase()} example`);
        const hour = frame.minutes / 60;
        const period = hour < 5 || hour >= 21 ? 'night' : hour < 12 ? 'morning' : hour < 18 ? 'day' : 'golden';
        document.documentElement.dataset.dayPeriod = period;

        const iconKey = frame.weather === 'clear' && period === 'night' ? 'night' : frame.weather;
        const icon = document.querySelector(`[data-icon-template="${iconKey}"]`);
        if (icon) {
            document.getElementById('desktop-weather-icon').replaceChildren(icon.content.cloneNode(true));
        }
        weatherButtons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.weatherChoice === frame.weather)));
    }

    async function chooseFrame(frame, announce = '') {
        const currentRequest = ++requestNumber;
        scene.setAttribute('aria-busy', 'true');
        feedback.textContent = 'Loading example…';
        feedback.hidden = false;
        announcement.textContent = '';
        const extension = image.currentSrc.endsWith('.avif') ? 'avif' : 'webp';
        const nextImage = new Image();
        nextImage.srcset = srcset(frame, extension);
        nextImage.sizes = '100vw';
        nextImage.src = `${base}/${frame.file}-1280.${extension}`;

        try {
            await nextImage.decode();
            if (currentRequest !== requestNumber) {
                return;
            }
            displayFrame(frame, nextImage);
            announcement.textContent = announce;
        } catch {
            if (currentRequest === requestNumber) {
                scene.removeAttribute('aria-busy');
                scrubber.value = displayedScrubberValue;
                feedback.textContent = 'Couldn’t load that example. Try again.';
                feedback.hidden = false;
            }
        }
    }

    const initialIndex = Number(document.documentElement.dataset.initialFrame);
    scrubber.value = initialIndex;
    displayFrame(clearFrames[initialIndex]);
    scrubber.addEventListener('input', () => {
        const reset = scene.dataset.weather !== 'clear' ? 'Back to clear weather.' : '';
        chooseFrame(clearFrames[Number(scrubber.value)], reset);
    });
    scrubber.addEventListener('keydown', (event) => {
        if (event.key === 'PageUp' || event.key === 'PageDown') {
            event.preventDefault();
            scrubber.value = Math.max(0, Math.min(23, Number(scrubber.value) + (event.key === 'PageUp' ? 6 : -6)));
            scrubber.dispatchEvent(new Event('input'));
        }
    });
    let neighboursPrefetched = false;
    scrubber.addEventListener('input', () => {
        if (neighboursPrefetched) {
            return;
        }
        neighboursPrefetched = true;
        const hour = Number(scrubber.value);
        const prefetch = () => [-2, -1, 1, 2].forEach((offset) => {
            const neighbour = clearFrames[(hour + offset + 24) % 24];
            const preview = new Image();
            preview.src = `${base}/${neighbour.file}-640.avif`;
        });
        window.requestIdleCallback ? window.requestIdleCallback(prefetch) : setTimeout(prefetch, 200);
    });
    weatherButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const weather = button.dataset.weatherChoice;
            const frame = weather === 'clear' ? clearFrames[Number(scrubber.value)] : frames.find((option) => option.weather === weather);
            if (weather !== 'clear') {
                scrubber.value = frame.minutes / 60;
            }
            const time = formatExampleTime(frame.minutes);
            chooseFrame(frame, `${frame.label} example at ${time}.`);
        });
    });
}

const wildFrameData = document.getElementById('wild-frames');
if (wildFrameData) {
    const frames = JSON.parse(wildFrameData.textContent);
    const base = JSON.parse(document.getElementById('photo-base').textContent);
    const image = document.getElementById('wild-image');
    const caption = document.getElementById('wild-caption');
    const status = document.getElementById('wild-status');
    const feedback = document.getElementById('wild-feedback');
    const buttons = [...document.querySelectorAll('[data-wild-hour]')];
    const strip = document.querySelector('.wild-grid');
    function updatePartialLabels() {
        const mobile = window.matchMedia('(max-width: 700px)').matches;
        const bounds = strip.getBoundingClientRect();
        buttons.forEach((button) => {
            const thumbnail = button.getBoundingClientRect();
            button.dataset.partial = String(mobile && (thumbnail.left < bounds.left || thumbnail.right > bounds.right));
        });
    }
    strip.addEventListener('scroll', updatePartialLabels, { passive: true });
    window.addEventListener('resize', updatePartialLabels);
    function centerSelectedThumbnail(hour) {
        if (window.matchMedia('(max-width: 700px)').matches) {
            const button = buttons[hour];
            strip.scrollLeft = button.offsetLeft - strip.offsetLeft - (strip.clientWidth - button.clientWidth) / 2;
            updatePartialLabels();
        }
    }
    let requestNumber = 0;
    let selectedHour = new Date().getHours();

    buttons.forEach((button) => {
        const hour = Number(button.dataset.wildHour);
        const time = formatExampleTime(hour * 60);
        button.querySelector('[data-hour-label]').textContent = time;
        button.setAttribute('aria-label', `${time}, sky garden example`);
        button.tabIndex = hour === selectedHour ? 0 : -1;
        button.setAttribute('aria-pressed', String(hour === selectedHour));
    });

    async function selectWildHour(hour, announce = false) {
        const currentRequest = ++requestNumber;
        const frame = frames[hour];
        const nextImage = new Image();
        const extension = document.getElementById('scene-image').currentSrc.endsWith('.avif') ? 'avif' : 'webp';
        nextImage.sizes = '(max-width: 700px) calc(100vw - 40px), 1140px';
        nextImage.srcset = [640, 960, 1280, 1536].map((width) => `${base}/${frame.file}-${width}.${extension} ${width}w`).join(', ');
        nextImage.src = `${base}/${frame.file}-1280.${extension}`;
        status.textContent = '';
        feedback.hidden = true;
        try {
            await nextImage.decode();
            if (currentRequest !== requestNumber) {
                return;
            }
            image.parentElement.querySelectorAll('source').forEach((source) => source.remove());
            image.srcset = nextImage.srcset;
            image.sizes = nextImage.sizes;
            image.src = nextImage.currentSrc;
            image.alt = frame.alt;
            selectedHour = hour;
            centerSelectedThumbnail(hour);
            const time = formatExampleTime(hour * 60);
            caption.textContent = `${time}, sky garden example`;
            buttons.forEach((button) => {
                const selected = Number(button.dataset.wildHour) === hour;
                button.tabIndex = selected ? 0 : -1;
                button.setAttribute('aria-pressed', String(selected));
            });
            if (announce) {
                status.textContent = caption.textContent;
            }
        } catch {
            if (currentRequest === requestNumber) {
                feedback.textContent = 'Couldn’t load that example. Try again.';
                feedback.hidden = false;
            }
        }
    }

    const observer = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
            selectWildHour(selectedHour);
            observer.disconnect();
        }
    }, { rootMargin: '150px' });
    observer.observe(image);
    centerSelectedThumbnail(selectedHour);
    const thumbnailObserver = new IntersectionObserver((entries) => {
        entries.filter((entry) => entry.isIntersecting).forEach((entry) => {
            const thumbnail = entry.target;
            thumbnail.parentElement.querySelector('source').srcset = thumbnail.parentElement.querySelector('source').dataset.srcset;
            thumbnail.src = thumbnail.dataset.src;
            thumbnailObserver.unobserve(thumbnail);
        });
    }, { rootMargin: '150px' });
    buttons.forEach((button) => thumbnailObserver.observe(button.querySelector('img')));
    buttons.forEach((button) => {
        button.addEventListener('click', () => selectWildHour(Number(button.dataset.wildHour), true));
        button.addEventListener('keydown', (event) => {
            const columns = window.matchMedia('(max-width: 700px)').matches ? 1 : 4;
            const offset = { ArrowRight: 1, ArrowLeft: -1, ArrowDown: columns, ArrowUp: -columns }[event.key];
            if (offset === undefined && event.key !== 'Home' && event.key !== 'End') {
                return;
            }
            event.preventDefault();
            const currentHour = Number(button.dataset.wildHour);
            const hour = event.key === 'Home' ? 0 : event.key === 'End' ? 23 : (currentHour + offset + 24) % 24;
            buttons[hour].focus();
            centerSelectedThumbnail(hour);
            selectWildHour(hour, true);
        });
    });
}
