const scene = document.getElementById('scene');
const scrubber = document.getElementById('day-scrubber');
const frameData = document.getElementById('photo-frames');
const weatherButtons = document.querySelectorAll('[data-weather-choice]');

if (scene && scrubber && frameData) {
    const frames = JSON.parse(frameData.textContent);
    const base = JSON.parse(document.getElementById('photo-base').textContent);
    const clearFrames = frames.filter((frame) => frame.weather === 'clear');
    const image = document.getElementById('scene-image');
    let requestNumber = 0;

    const srcset = (frame, extension) => {
        const widths = frame.key === 'day' ? [640, 960, 1280, 1920, 2560] : [640, 960, 1280, 1536];
        return widths.map((width) => `${base}/${frame.file}-${width}.${extension} ${width}w`).join(', ');
    };

    function displayFrame(frame, sourceImage = null) {
        document.getElementById('scene-avif').srcset = srcset(frame, 'avif');
        document.getElementById('scene-webp').srcset = srcset(frame, 'webp');
        image.src = sourceImage?.currentSrc || `${base}/${frame.file}-1280.webp`;
        image.alt = frame.alt;
        scene.dataset.frame = frame.key;
        scene.dataset.weather = frame.weather;
        scene.removeAttribute('aria-busy');

        const clock = new Date();
        clock.setHours(Math.floor(frame.minutes / 60), frame.minutes % 60, 0, 0);
        const time = clock.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        document.getElementById('desktop-clock').textContent = time;
        document.getElementById('scene-time').textContent = time;
        document.getElementById('scene-weather').textContent = frame.label;
        scrubber.setAttribute('aria-valuetext', `${time}, ${frame.label.toLowerCase()} example`);
        const period = frame.key === 'night' ? 'night' : frame.key === 'evening' ? 'golden' : frame.key === 'morning' || frame.key === 'fog' ? 'morning' : 'day';
        document.documentElement.dataset.dayPeriod = period;

        const iconKey = frame.key === 'night' ? 'night' : frame.weather;
        const icon = document.querySelector(`[data-icon-template="${iconKey}"]`);
        if (icon) {
            document.getElementById('desktop-weather-icon').replaceChildren(icon.content.cloneNode(true));
        }
        weatherButtons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.weatherChoice === frame.weather)));
    }

    async function chooseFrame(frame) {
        const currentRequest = ++requestNumber;
        scene.setAttribute('aria-busy', 'true');
        const preview = document.createElement('picture');
        const avif = document.createElement('source');
        avif.type = 'image/avif';
        avif.srcset = srcset(frame, 'avif');
        avif.sizes = '100vw';
        const webp = document.createElement('source');
        webp.type = 'image/webp';
        webp.srcset = srcset(frame, 'webp');
        webp.sizes = '100vw';
        const nextImage = new Image();
        preview.append(avif, webp, nextImage);
        nextImage.src = `${base}/${frame.file}-1280.webp`;

        try {
            await nextImage.decode();
            if (currentRequest !== requestNumber) {
                return;
            }
            displayFrame(frame, nextImage);
        } catch {
            if (currentRequest === requestNumber) {
                scene.removeAttribute('aria-busy');
            }
        }
    }

    const initialIndex = Number(document.documentElement.dataset.initialFrame);
    scrubber.value = initialIndex;
    displayFrame(clearFrames[initialIndex]);
    scrubber.addEventListener('input', () => chooseFrame(clearFrames[Number(scrubber.value)]));
    weatherButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const weather = button.dataset.weatherChoice;
            const frame = weather === 'clear' ? clearFrames[Number(scrubber.value)] : frames.find((option) => option.weather === weather);
            if (weather !== 'clear') {
                scrubber.value = weather === 'fog' ? 0 : 1;
            }
            chooseFrame(frame);
        });
    });
}
