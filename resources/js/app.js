const scene = document.getElementById('scene');
const scrubber = document.getElementById('day-scrubber');
const weatherButtons = document.querySelectorAll('[data-weather-choice]');
const frames = document.querySelectorAll('[data-frame]');

const palettes = [
    { hour: 0, sky: [17, 35, 59], horizon: [53, 74, 99], ridge: [97, 120, 145], middle: [65, 91, 118], front: [29, 53, 75], water: [34, 61, 85] },
    { hour: 6, sky: [120, 137, 160], horizon: [236, 182, 159], ridge: [110, 126, 144], middle: [77, 106, 122], front: [38, 70, 82], water: [107, 139, 153] },
    { hour: 8, sky: [175, 177, 194], horizon: [255, 205, 160], ridge: [137, 142, 156], middle: [77, 106, 130], front: [35, 69, 87], water: [136, 158, 179] },
    { hour: 11, sky: [108, 171, 219], horizon: [218, 239, 247], ridge: [126, 154, 171], middle: [70, 123, 155], front: [30, 75, 105], water: [110, 165, 202] },
    { hour: 15, sky: [111, 168, 214], horizon: [223, 239, 246], ridge: [130, 150, 158], middle: [75, 117, 131], front: [37, 77, 88], water: [115, 153, 172] },
    { hour: 19, sky: [107, 115, 149], horizon: [240, 172, 131], ridge: [143, 119, 131], middle: [90, 100, 125], front: [41, 64, 79], water: [126, 120, 142] },
    { hour: 21, sky: [25, 43, 73], horizon: [98, 104, 129], ridge: [100, 118, 143], middle: [71, 88, 109], front: [37, 59, 71], water: [42, 60, 86] },
    { hour: 24, sky: [17, 35, 59], horizon: [53, 74, 99], ridge: [97, 120, 145], middle: [65, 91, 118], front: [29, 53, 75], water: [34, 61, 85] },
];

function setTime(minutes) {
    if (!scene || !scrubber || !weatherButtons.length) {
        return;
    }

    const hour = minutes / 60;
    const paletteIndex = palettes.findIndex((palette) => palette.hour > hour);
    const start = palettes[paletteIndex - 1];
    const end = palettes[paletteIndex];
    const progress = (hour - start.hour) / (end.hour - start.hour);
    const weather = scene.dataset.weather;
    const daylightTint = start.sky.map((channel, index) => Math.round(248 * .94 + (channel + (end.sky[index] - channel) * progress) * .06));
    document.documentElement.style.setProperty('--day-tint', `rgb(${daylightTint.join(',')})`);

    Object.keys(start).filter((key) => key !== 'hour').forEach((key) => {
        const color = start[key].map((channel, index) => Math.round(channel + (end[key][index] - channel) * progress));
        document.documentElement.style.setProperty(`--${key}`, `rgb(${color.join(',')})`);
    });

    const night = hour < 6 || hour >= 21;
    const period = night ? 'night' : hour < 12 ? 'morning' : hour < 17 ? 'afternoon' : 'evening';
    const clock = new Date();
    clock.setHours(Math.floor(hour), minutes % 60, 0, 0);
    const time = clock.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    const condition = { clear: 'Clear', rain: 'Rainy', snow: 'Snowy', fog: 'Foggy' }[weather];
    const frameKey = weather === 'rain' ? 'rain' : night ? 'night' : hour < 12 ? 'morning' : 'golden';

    document.documentElement.dataset.heroTone = hour >= 7 && hour < 17 && weather !== 'rain' ? 'light' : 'dark';
    document.documentElement.dataset.dayPeriod = night ? 'night' : hour < 11 ? 'morning' : hour < 17 ? 'day' : 'golden';
    scene.dataset.night = String(night);
    scene.style.setProperty('--stars', night ? 1 : Math.max(0, (hour - 19) / 2));
    scene.style.setProperty('--cabin-light', night || hour >= 18 ? 1 : 0);
    const landscape = scene.querySelector('svg');
    const isMobile = window.matchMedia('(max-width: 700px)').matches;
    landscape.setAttribute('viewBox', isMobile ? '0 0 1440 1000' : '0 420 1440 480');
    landscape.setAttribute('preserveAspectRatio', 'xMidYMax slice');
    const bounds = landscape.getBoundingClientRect();
    const viewBox = landscape.viewBox.baseVal;
    const scale = Math.max(bounds.width / viewBox.width, bounds.height / viewBox.height);
    const sourceTop = viewBox.y + viewBox.height - bounds.height / scale;
    const sunPosition = (575 - Math.sin((hour - 6) / 12 * Math.PI) * 270 - sourceTop) * scale;
    const visibleSunPosition = Math.max(60, Math.min(bounds.height * .35, sunPosition));
    const heroTop = document.querySelector('.hero').getBoundingClientRect().top;
    document.documentElement.style.setProperty('--light-x', `${bounds.width * .78}px`);
    document.documentElement.style.setProperty('--sun-top', `${bounds.top - heroTop + visibleSunPosition}px`);
    document.documentElement.style.setProperty('--moon-top', `${bounds.top - heroTop + Math.max(60, bounds.height * .2)}px`);
    document.documentElement.dataset.sceneReady = 'true';
    document.documentElement.style.setProperty('--hero-stars', night && weather === 'clear' ? .7 : 0);
    document.documentElement.style.setProperty('--cloud-opacity', weather === 'clear' ? Math.max(0, Math.min(1, (hour - 7) / 3, (18 - hour) / 3)) : 0);
    scene.style.setProperty('--peak-light', hour >= 7 && hour < 18 ? '#fff0d4' : '#e3ebe7');
    document.documentElement.dataset.weather = weather;
    scene.style.setProperty('--sun-opacity', night ? 0 : 1);
    scene.style.setProperty('--sun-color', hour >= 10 && hour < 17 ? '#fff7e8' : '#ffd49a');
    scene.style.setProperty('--reflection-opacity', night ? 1 : Math.max(.3, Math.min(.8, Math.abs(hour - 13) / 7)));
    document.documentElement.style.setProperty('--sun-size', hour >= 10 && hour < 17 ? '60px' : '100px');
    document.documentElement.style.setProperty('--sun-color', hour >= 10 && hour < 17 ? '#fff7e8' : '#ffd49a');
    document.documentElement.style.setProperty('--sun-opacity', night ? 0 : 1);
    document.documentElement.style.setProperty('--moon-opacity', night ? 1 : 0);
    scene.style.setProperty('--moon-opacity', night ? 1 : 0);

    document.getElementById('desktop-clock').textContent = time;
    const iconKey = weather === 'clear' && night ? 'night' : weather;
    const icon = document.querySelector(`[data-icon-template="${iconKey}"]`);
    if (icon) {
        document.getElementById('desktop-weather-icon').replaceChildren(icon.content.cloneNode(true));
    }
    document.getElementById('scene-time').textContent = time;
    document.getElementById('scene-weather').textContent = `${condition} ${period}`;
    document.getElementById('scene-title').textContent = `Illustration of a mountain lake and cabin on a ${condition.toLowerCase()} ${period}`;
    scrubber.setAttribute('aria-valuetext', `${time}, ${condition.toLowerCase()} ${period}`);

    frames.forEach((frame) => {
        frame.hidden = frame.dataset.frame !== frameKey;
    });
}

if (scrubber) {
    const localMinutes = Number(document.documentElement.dataset.localMinutes);
    scrubber.value = Math.min(1439, localMinutes);
    setTime(Number(scrubber.value));
    scrubber.addEventListener('input', () => setTime(Number(scrubber.value)));
    window.addEventListener('resize', () => setTime(Number(scrubber.value)));
    weatherButtons.forEach((button) => {
        button.addEventListener('click', () => {
            scene.dataset.weather = button.dataset.weatherChoice;
            weatherButtons.forEach((option) => option.setAttribute('aria-pressed', String(option === button)));
            setTime(Number(scrubber.value));
        });
    });
}
