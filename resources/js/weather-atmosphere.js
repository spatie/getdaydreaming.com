const wetWeather = new Set(['rain', 'storm']);

function randomGenerator(seed) {
    let value = seed >>> 0;

    return () => {
        value = (Math.imul(value, 1664525) + 1013904223) >>> 0;
        return value / 4294967296;
    };
}

function fitCanvas(canvas) {
    const { width, height } = canvas.getBoundingClientRect();
    const ratio = Math.min(window.devicePixelRatio || 1, canvas.id === 'weather-sky-canvas' ? 1.25 : 1.5);
    const pixelWidth = Math.max(1, Math.round(width * ratio));
    const pixelHeight = Math.max(1, Math.round(height * ratio));

    if (canvas.width !== pixelWidth || canvas.height !== pixelHeight) {
        canvas.width = pixelWidth;
        canvas.height = pixelHeight;
    }

    const context = canvas.getContext('2d');
    context.setTransform(ratio, 0, 0, ratio, 0, 0);

    return { context, width, height };
}

function createParticles(width, height, weather, random) {
    const count = wetWeather.has(weather)
        ? Math.min(250, Math.round(width * .2))
        : weather === 'snow' ? Math.min(260, Math.round(width * .28)) : 0;

    if (weather === 'snow') {
        const gusts = Array.from({ length: 5 }, () => ({ x: random() * width, y: random() * height }));

        return Array.from({ length: count }, () => {
            const distance = random();
            const depth = distance < .54 ? .08 + random() * .26
                : distance < .94 ? .38 + random() * .33 : .77 + random() * .23;
            const gust = gusts[Math.floor(random() * gusts.length)];
            const clustered = random() < .64;
            const x = clustered ? (gust.x + (random() - random()) * width * .24 + width) % width : random() * width;
            const y = clustered ? (gust.y + (random() - random()) * height * .18 + height) % height : random() * height;

            return {
                x,
                y,
                depth,
                phase: random() * Math.PI * 2,
                size: random(),
                crystal: depth > .55 && random() < .32,
            };
        });
    }

    return Array.from({ length: count }, () => ({
        x: random() * width,
        y: random() * height,
        depth: .18 + random() * .82,
        phase: random() * Math.PI * 2,
        size: random(),
    }));
}

function createDroplets(width, height, random) {
    const count = Math.min(16, Math.round(width / 68));

    return Array.from({ length: count }, (_, index) => {
        const rivulet = index < Math.max(2, Math.round(count * .24));

        return {
            x: random() * width,
            y: random() * height,
            radius: rivulet ? 2.6 + random() * 1.9 : 1 + random() * 1.8,
            trail: rivulet ? 32 + random() * 62 : 0,
            speed: rivulet ? 5 + random() * 10 : 2 + random() * 13,
            rivulet,
            bend: (random() - .5) * 9,
        };
    });
}

function createSnowGlow() {
    const canvas = document.createElement('canvas');
    canvas.width = 48;
    canvas.height = 48;
    const context = canvas.getContext('2d');
    const gradient = context.createRadialGradient(24, 24, 1, 24, 24, 24);
    gradient.addColorStop(0, 'rgba(255, 255, 255, .68)');
    gradient.addColorStop(.26, 'rgba(247, 252, 255, .42)');
    gradient.addColorStop(1, 'rgba(235, 248, 255, 0)');
    context.fillStyle = gradient;
    context.fillRect(0, 0, 48, 48);

    return canvas;
}

function drawCloudBank(context, width, height, weather, elapsed, random) {
    if (weather === 'clear') {
        return;
    }

    const storm = weather === 'storm';
    const fog = weather === 'fog';
    const layers = fog ? 4 : 3;
    const alpha = storm ? .125 : fog ? .105 : .075;
    const drift = elapsed * (storm ? 1.5 : .55);

    for (let layer = 0; layer < layers; layer++) {
        const bankY = height * (fog ? .25 + layer * .2 : .12 + layer * .18);
        const bankHeight = height * (fog ? .14 : .15) * (1 + layer * .3);

        for (let puff = 0; puff < 17; puff++) {
            const x = (puff / 15) * width - width * .1
                + (random() - .5) * width * .13
                + Math.sin(drift * .08 + layer) * 28;
            const y = bankY + (random() - .5) * bankHeight;
            const radius = width * (.07 + random() * .12);
            const gradient = context.createRadialGradient(x, y, 0, x, y, radius);
            const color = storm ? '16, 34, 57' : fog ? '222, 238, 240' : '211, 228, 238';
            gradient.addColorStop(0, `rgba(${color}, ${alpha * (1 + random() * .5)})`);
            gradient.addColorStop(.48, `rgba(${color}, ${alpha * .58})`);
            gradient.addColorStop(1, `rgba(${color}, 0)`);
            context.fillStyle = gradient;
            context.save();
            context.translate(x, y);
            context.scale(1, .3 + random() * .22);
            context.translate(-x, -y);
            context.fillRect(x - radius, y - radius, radius * 2, radius * 2);
            context.restore();
        }
    }
}

function createCloudTexture(width, height, weather, seed) {
    const canvas = document.createElement('canvas');
    canvas.width = Math.ceil(width + 100);
    canvas.height = Math.ceil(height);
    drawCloudBank(canvas.getContext('2d'), canvas.width, canvas.height, weather, 0, randomGenerator(seed));

    return canvas;
}

function drawRain(context, particles, width, height, elapsed, storm, sectionAt = () => 0) {
    context.lineCap = 'round';
    const wind = storm ? .43 : .2;

    particles.forEach((drop) => {
        const speed = (storm ? 440 : 305) * (.45 + drop.depth);
        const y = ((drop.y + elapsed * speed) % (height + 90)) - 45;
        const x = (drop.x + elapsed * speed * wind) % (width + 45) - 22;
        const length = 8 + drop.depth * (storm ? 34 : 23);
        const section = sectionAt(y);
        if (drop.size < section * .88) {
            return;
        }

        const opacity = .1 + drop.depth * .35;
        const red = Math.round(226 - section * 171);
        const green = Math.round(244 - section * 143);
        const blue = Math.round(255 - section * 124);
        context.strokeStyle = `rgba(${red}, ${green}, ${blue}, ${opacity * (1 - section * .64)})`;
        context.lineWidth = .55 + drop.depth * 1.25;
        context.beginPath();
        context.moveTo(x, y);
        context.lineTo(x - length * wind, y + length);
        context.stroke();
    });
}

function drawCrystal(context, x, y, radius, opacity, rotation) {
    context.save();
    context.translate(x, y);
    context.rotate(rotation);
    context.strokeStyle = `rgba(238, 250, 255, ${opacity})`;
    context.lineWidth = .7;
    context.lineCap = 'round';

    for (let arm = 0; arm < 6; arm++) {
        context.rotate(Math.PI / 3);
        const reach = radius * (arm % 2 === 0 ? 1 : .78);
        context.beginPath();
        context.moveTo(0, 0);
        context.lineTo(0, -reach);
        context.moveTo(0, -reach * .55);
        context.lineTo(-reach * .23, -reach * .76);
        context.moveTo(0, -reach * .55);
        context.lineTo(reach * .23, -reach * .76);
        context.stroke();
    }

    context.restore();
}

function drawSnow(context, particles, width, height, elapsed, glow, sectionAt = () => 0) {
    particles.forEach((flake) => {
        const depth = flake.depth;
        const y = (flake.y + elapsed * (10 + depth * 53)) % (height + 28) - 14;
        const flutter = Math.sin(elapsed * (.65 + depth * .7) + flake.phase) * (3 + depth * 12);
        const gust = Math.sin(elapsed * .42 + flake.y / height * 5) * (2 + depth * 10);
        const x = (flake.x + elapsed * (3 + depth * 11) + flutter + gust + width + 28) % (width + 28) - 14;
        const section = sectionAt(y);
        const radius = depth < .35 ? .25 + flake.size * .45
            : depth < .75 ? .75 + flake.size * 1.3 : 2.2 + flake.size * 2.6;

        if (depth > .76 && !flake.crystal) {
            const diameter = radius * 5;
            context.globalAlpha = .5 + flake.size * .28;
            context.drawImage(glow, x - diameter / 2, y - diameter / 2, diameter, diameter);
            context.globalAlpha = 1;
        }

        if (flake.crystal) {
            drawCrystal(context, x, y, radius * 1.8, .35 + depth * .4, flake.phase + elapsed * .18);
        } else {
            const red = Math.round(249 - section * 112);
            const green = Math.round(252 - section * 83);
            const blue = Math.round(255 - section * 66);
            context.fillStyle = `rgba(${red}, ${green}, ${blue}, ${.22 + depth * .55})`;
            context.beginPath();
            context.arc(x, y, radius, 0, Math.PI * 2);
            context.fill();
        }

        if (depth > .72) {
            context.strokeStyle = `rgba(228, 244, 255, ${.08 + depth * .09})`;
            context.lineWidth = Math.max(.6, radius * .35);
            context.beginPath();
            context.moveTo(x - 1, y - radius - 3);
            context.lineTo(x, y - radius * .3);
            context.stroke();
        }
    });
}

function drawGlassDroplets(context, droplets, width, height, elapsed, storm) {
    droplets.forEach((drop) => {
        const x = drop.x + Math.sin(elapsed * .23 + drop.x) * 1.2;
        const y = (drop.y + elapsed * drop.speed * (storm ? 1.7 : 1)) % (height + 45) - 20;
        const radius = drop.radius;
        if (drop.rivulet) {
            const top = y - drop.trail;
            const middle = y - drop.trail * .45;
            const gradient = context.createLinearGradient(x - radius, y, x + radius, y);
            gradient.addColorStop(0, 'rgba(237, 249, 255, .25)');
            gradient.addColorStop(.38, 'rgba(211, 234, 249, .06)');
            gradient.addColorStop(1, 'rgba(13, 36, 57, .2)');
            context.fillStyle = gradient;
            context.beginPath();
            context.moveTo(x + drop.bend, top);
            context.bezierCurveTo(
                x - drop.bend, middle - 10,
                x + drop.bend - radius * .45, middle + 10,
                x - radius * .5, y - radius,
            );
            context.bezierCurveTo(x - radius, y + radius * .4, x - radius * .35, y + radius, x, y + radius * .9);
            context.bezierCurveTo(
                x + radius * .8, y + radius * .4,
                x + radius * .6, y - radius * .4,
                x + radius * .35, y - radius,
            );
            context.bezierCurveTo(
                x + drop.bend + 1, middle + 10,
                x - drop.bend + 1, middle - 10,
                x + drop.bend + 1, top,
            );
            context.closePath();
            context.fill();
            context.strokeStyle = 'rgba(241, 252, 255, .31)';
            context.lineWidth = .6;
            context.beginPath();
            context.moveTo(x + drop.bend - .5, top + drop.trail * .18);
            context.bezierCurveTo(
                x - drop.bend - .5, middle - 8,
                x + drop.bend - radius * .5, middle + 8,
                x - radius * .5, y - radius * .45,
            );
            context.stroke();
        } else {
            const gradient = context.createLinearGradient(x - radius, y, x + radius, y);
            gradient.addColorStop(0, 'rgba(240, 251, 255, .32)');
            gradient.addColorStop(.45, 'rgba(214, 237, 249, .04)');
            gradient.addColorStop(1, 'rgba(19, 42, 61, .17)');
            context.fillStyle = gradient;
            context.beginPath();
            context.ellipse(x, y, radius * .75, radius * 1.15, -.12, 0, Math.PI * 2);
            context.fill();
        }
    });
}

function drawLightning(context, width, height, seed, opacity, scene) {
    if (opacity <= 0) {
        return;
    }

    const random = randomGenerator(seed);
    const points = [{ x: width * (scene ? .72 : .82), y: scene ? -10 : 30 }];
    const reach = height * (scene ? .53 : .48);

    while (points.at(-1).y < reach) {
        const previous = points.at(-1);
        points.push({ x: previous.x + (random() - .5) * (scene ? 46 : 70), y: previous.y + 17 + random() * 25 });
    }

    context.save();
    context.globalAlpha = opacity;
    context.lineJoin = 'round';
    context.lineCap = 'round';
    context.shadowColor = '#9bd0ff';
    context.shadowBlur = 24;
    context.strokeStyle = 'rgba(154, 205, 255, .6)';
    context.lineWidth = scene ? 8 : 10;
    context.beginPath();
    points.forEach((point, index) => index ? context.lineTo(point.x, point.y) : context.moveTo(point.x, point.y));
    context.stroke();
    context.shadowBlur = 9;
    context.strokeStyle = '#f6fbff';
    context.lineWidth = scene ? 1.8 : 2.2;
    context.stroke();

    for (let branch = 3; branch < points.length - 2; branch += 3) {
        const from = points[branch];
        const side = random() < .5 ? -1 : 1;
        context.beginPath();
        context.moveTo(from.x, from.y);
        context.lineTo(from.x + side * (20 + random() * 28), from.y + 24);
        context.lineTo(from.x + side * (40 + random() * 42), from.y + 49);
        context.stroke();
    }

    context.restore();
}

export function createWeatherAtmosphere(hero, scene) {
    const skyCanvas = document.getElementById('weather-sky-canvas');
    const sceneCanvas = document.getElementById('weather-scene-canvas');
    const pageCanvas = document.getElementById('weather-page-canvas');

    if (!skyCanvas || !sceneCanvas || !pageCanvas) {
        return { setWeather() {} };
    }

    let weather = 'clear';
    let sky;
    let photo;
    let page;
    let skyClouds;
    let sceneFog;
    let pageFog;
    const snowGlow = createSnowGlow();
    let pageParticles = [];
    let droplets = [];
    let frame;
    let lastFrame = 0;
    let elapsed = 0;
    let nextStrike = 9;
    let strikeAt = -1;
    let strikeSeed = 35;
    let renderedFlash = null;
    let previewUntil = 0;
    let heroTop = 0;
    let heroBottom = 0;
    let sceneLeft = 0;
    let sceneTop = 0;
    const staticMode = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches
        || navigator.connection?.saveData === true;
    const heroVisible = () => heroBottom > 0 && heroTop < window.innerHeight
        && hero.dataset.visible !== 'false';
    const shouldAnimate = () => !staticMode() && !document.hidden && weather !== 'clear'
        && heroVisible() && (hero.dataset.playing !== 'false' || performance.now() < previewUntil);

    function measureGeometry() {
        const heroBounds = hero.getBoundingClientRect();
        const sceneBounds = scene.getBoundingClientRect();
        heroTop = heroBounds.top;
        heroBottom = heroBounds.bottom;
        sceneLeft = sceneBounds.left;
        sceneTop = sceneBounds.top;
    }

    function setFlash(value) {
        if (renderedFlash === value) {
            return;
        }

        renderedFlash = value;
        document.body.style.setProperty('--weather-flash', String(value));
    }

    function resize() {
        measureGeometry();
        sky = fitCanvas(skyCanvas);
        photo = fitCanvas(sceneCanvas);
        page = fitCanvas(pageCanvas);
        const random = randomGenerator(92673 + weather.length * 413);
        pageParticles = createParticles(page.width, page.height, weather, random);
        droplets = createDroplets(photo.width, photo.height, random);
        skyClouds = createCloudTexture(sky.width, sky.height, weather, 413);
        sceneFog = weather === 'fog' ? createCloudTexture(photo.width, photo.height, 'fog', 714) : null;
        pageFog = weather === 'fog' ? createCloudTexture(page.width, page.height, 'fog', 817) : null;
        draw();
    }

    function draw() {
        if (!sky || !photo || !page) {
            return;
        }

        const { context: skyContext, width: skyWidth, height: skyHeight } = sky;
        const { context: sceneContext, width: sceneWidth, height: sceneHeight } = photo;
        const { context: pageContext, width: pageWidth, height: pageHeight } = page;
        const showHero = heroVisible();
        if (showHero || weather === 'clear') {
            skyContext.clearRect(0, 0, skyWidth, skyHeight);
            sceneContext.clearRect(0, 0, sceneWidth, sceneHeight);
        }
        pageContext.clearRect(0, 0, pageWidth, pageHeight);

        if (weather === 'clear') {
            setFlash(0);
            return;
        }

        const sectionAt = (y) => Math.max(0, Math.min(1, (y - heroBottom + 120) / 240));

        if (weather === 'fog') {
            pageContext.globalAlpha = .3;
            pageContext.drawImage(pageFog, Math.sin(elapsed * .045) * 15 - 50, 0);
            pageContext.globalAlpha = 1;
        } else if (weather === 'snow') {
            drawSnow(pageContext, pageParticles, pageWidth, pageHeight, elapsed, snowGlow, sectionAt);
        } else if (wetWeather.has(weather)) {
            const storm = weather === 'storm';
            drawRain(pageContext, pageParticles, pageWidth, pageHeight, elapsed, storm, sectionAt);
        }

        if (!showHero) {
            setFlash(0);
            return;
        }

        skyContext.drawImage(skyClouds, Math.sin(elapsed * .08) * 22 - 50, 0);

        if (weather === 'fog') {
            sceneContext.drawImage(sceneFog, Math.sin(elapsed * .065) * 16 - 50, 0);
        } else if (weather === 'snow') {
            sceneContext.save();
            sceneContext.translate(-sceneLeft, -sceneTop);
            drawSnow(sceneContext, pageParticles, pageWidth, pageHeight, elapsed, snowGlow);
            sceneContext.restore();
        } else if (wetWeather.has(weather)) {
            const storm = weather === 'storm';
            sceneContext.save();
            sceneContext.translate(-sceneLeft, -sceneTop);
            drawRain(sceneContext, pageParticles, pageWidth, pageHeight, elapsed, storm);
            sceneContext.restore();
            drawGlassDroplets(sceneContext, droplets, sceneWidth, sceneHeight, elapsed, storm);
        }

        let flash = 0;
        if (weather === 'storm' && strikeAt >= 0) {
            const age = elapsed - strikeAt;
            flash = age < .085 ? .53 : age > .14 && age < .245 ? .36 : 0;
            if (flash > 0) {
                drawLightning(skyContext, skyWidth, skyHeight, strikeSeed, flash, false);
                drawLightning(sceneContext, sceneWidth, sceneHeight, strikeSeed, flash * .65, true);
            }
        }
        setFlash(flash);
    }

    function tick(now) {
        frame = undefined;
        if (!shouldAnimate()) {
            freeze();
            return;
        }

        if (now - lastFrame >= 30 || !lastFrame) {
            elapsed += lastFrame ? Math.min((now - lastFrame) / 1000, .06) : 0;
            lastFrame = now;
            if (weather === 'storm' && elapsed >= nextStrike) {
                strikeAt = elapsed;
                strikeSeed += 137;
                nextStrike = elapsed + 9 + randomGenerator(strikeSeed)() * 7;
            }
            draw();
        }

        frame = requestAnimationFrame(tick);
    }

    function freeze() {
        if (frame) {
            cancelAnimationFrame(frame);
            frame = undefined;
        }
        lastFrame = 0;
        strikeAt = -1;
        setFlash(0);
        draw();
    }

    function sync() {
        if (shouldAnimate()) {
            if (!frame) {
                lastFrame = 0;
                frame = requestAnimationFrame(tick);
            }
        } else if (frame || renderedFlash !== 0) {
            freeze();
        }
    }

    document.addEventListener('visibilitychange', sync);
    window.addEventListener('scroll', () => {
        measureGeometry();
        if (!shouldAnimate()) {
            draw();
        }
    }, { passive: true });
    window.addEventListener('resize', measureGeometry, { passive: true });
    const observer = new MutationObserver(sync);
    observer.observe(hero, { attributes: true, attributeFilter: ['data-playing', 'data-visible'] });
    function updateMotionPreference() {
        strikeAt = -1;
        draw();
        sync();
    }
    window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', updateMotionPreference);
    navigator.connection?.addEventListener?.('change', updateMotionPreference);
    new ResizeObserver(resize).observe(hero);
    new ResizeObserver(resize).observe(scene);
    resize();

    return {
        setWeather(nextWeather, { preview = false } = {}) {
            if (weather !== nextWeather) {
                weather = nextWeather;
                elapsed = 0;
                strikeAt = -1;
                nextStrike = preview && nextWeather === 'storm' ? 2.8 : 6;
                resize();
            }

            previewUntil = preview ? performance.now() + 6500 : 0;
            sync();
        },
        stop() {
            previewUntil = 0;
            strikeAt = -1;
            sync();
        },
    };
}
