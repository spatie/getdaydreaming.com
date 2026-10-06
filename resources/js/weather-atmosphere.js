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
    const ratio = Math.min(window.devicePixelRatio || 1, canvas.id === 'weather-scene-canvas' ? 1.25 : 1);
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
        ? weather === 'storm' ? Math.min(400, Math.round(width * .3))
            : Math.min(290, Math.round(width * .23))
        : weather === 'snow' ? Math.min(340, Math.round(width * .37)) : 0;

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

    return Array.from({ length: count }, () => {
        const distance = random();
        const depth = distance < .68 ? .08 + random() * .3
            : distance < .94 ? .4 + random() * .32 : .78 + random() * .22;

        return {
            x: random() * width,
            y: random() * height,
            depth,
            phase: random() * Math.PI * 2,
            secondPhase: random() * Math.PI * 2,
            speed: .72 + random() * .6,
            size: random(),
        };
    });
}

function createDroplets(width, height, random) {
    const count = Math.min(10, Math.round(width / 105));

    return Array.from({ length: count }, (_, index) => {
        const rivulet = index < Math.max(1, Math.round(count * .15));

        return {
            x: random() * width,
            y: random() * height,
            radius: rivulet ? 2.6 + random() * 1.9 : 1 + random() * 1.8,
            trail: rivulet ? 18 + random() * 32 : 0,
            speed: rivulet ? 4 + random() * 8 : 2 + random() * 13,
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

function createCloudField(width, height, weather, seed) {
    const storm = weather === 'storm';
    const fog = weather === 'fog';
    const layers = fog ? 4 : 3;
    const alpha = storm ? .125 : fog ? .105 : .075;
    const random = randomGenerator(seed);
    const sprite = document.createElement('canvas');
    sprite.width = 96;
    sprite.height = 96;
    const context = sprite.getContext('2d');
    const gradient = context.createRadialGradient(48, 48, 0, 48, 48, 48);
    const color = storm ? '16, 34, 57' : fog ? '222, 238, 240' : '211, 228, 238';
    gradient.addColorStop(0, `rgba(${color}, ${alpha})`);
    gradient.addColorStop(.48, `rgba(${color}, ${alpha * .58})`);
    gradient.addColorStop(1, `rgba(${color}, 0)`);
    context.fillStyle = gradient;
    context.fillRect(0, 0, 96, 96);
    const puffs = [];

    for (let layer = 0; layer < layers; layer++) {
        const bankY = height * (fog ? .25 + layer * .2 : .12 + layer * .18);
        const bankHeight = height * (fog ? .14 : .15) * (1 + layer * .3);

        for (let puff = 0; puff < 9; puff++) {
            puffs.push({
                x: (puff / 7) * width - width * .1 + (random() - .5) * width * .13,
                y: bankY + (random() - .5) * bankHeight,
                radius: width * (.07 + random() * .12),
                stretch: .3 + random() * .22,
                phase: random() * Math.PI * 2,
                secondPhase: random() * Math.PI * 2,
                speed: (storm ? .21 : .12) * (.65 + random() * .9),
                opacity: .7 + random() * .4,
            });
        }
    }

    return { sprite, puffs };
}

function drawCloudField(context, field, elapsed, opacity = 1) {
    field.puffs.forEach((puff) => {
        const phase = elapsed * puff.speed + puff.phase;
        const eddy = Math.sin(phase) * puff.radius * .14
            + Math.sin(phase * 2.3 + puff.secondPhase) * puff.radius * .04;
        const x = puff.x + eddy;
        const y = puff.y + Math.cos(phase * .7 + puff.secondPhase) * puff.radius * .035;
        const radius = puff.radius * (1 + Math.sin(phase * .83) * .085);
        const stretch = puff.stretch * (1 + Math.sin(phase * 1.31) * .1);
        context.globalAlpha = opacity * puff.opacity * (1 + Math.sin(phase * 1.17) * .13);
        context.drawImage(field.sprite, x - radius, y - radius * stretch,
            radius * 2, radius * stretch * 2);
    });
    context.globalAlpha = 1;
}

function rainStreaks(particles, width, height, elapsed, storm, gustStrength) {
    const span = height + 90;
    const wind = elapsed * (storm ? 92 : 41)
        + Math.sin(elapsed * .37) * (storm ? 39 : 18)
        + Math.sin(elapsed * 1.13) * (storm ? 12 : 5)
        + gustStrength * (storm ? 22 : 0);

    return particles.map((drop) => {
        const speed = (storm ? 440 : 305) * (.45 + drop.depth) * drop.speed;
        const travel = drop.y + elapsed * speed;
        const cycle = Math.floor(travel / span);
        const y = travel - cycle * span - 45;
        const gust = Math.sin(elapsed * 1.6 + drop.phase) * (3 + drop.depth * 10)
            + Math.sin(elapsed * .54 + drop.secondPhase) * (4 + drop.depth * 7);
        const rebirth = Math.sin(cycle * 2.38 + drop.phase) * width * .42;
        const x = ((drop.x + wind * (.45 + drop.depth * .9) + gust + rebirth) % (width + 45)
            + width + 45) % (width + 45) - 22;
        const length = (3 + drop.depth * (storm ? 26 : 20))
            * (1 + Math.sin(elapsed * .9 + drop.phase) * .15);
        const slant = (storm ? .3 : .15) + Math.sin(elapsed * .67 + drop.secondPhase) * .08
            + gustStrength * (storm ? .13 : 0);

        return { x, y, length, slant, depth: drop.depth, size: drop.size };
    });
}

function drawRain(context, streaks, sectionAt = () => 0, bounds, scene = false) {
    context.lineCap = 'butt';

    streaks.forEach((streak) => {
        const { x, y, length, slant, depth, size } = streak;
        if (bounds && (y > bounds.bottom || y + length < bounds.top
            || x < bounds.left - length * slant || x > bounds.right + length * slant)) {
            return;
        }
        const section = sectionAt(y);
        if (size < section * .88) {
            return;
        }

        const opacity = (.055 + depth * .22) * (scene ? 1 : .65) * (1 - section * .75);
        const red = Math.round(177 - section * 57);
        const green = Math.round(199 - section * 55);
        const blue = Math.round(218 - section * 57);
        context.strokeStyle = `rgba(${red}, ${green}, ${blue}, ${opacity})`;
        context.lineWidth = .35 + depth * (scene ? .88 : .65);
        if (scene && depth > .78) {
            context.shadowColor = 'rgba(182, 205, 224, .22)';
            context.shadowBlur = 2;
        }
        context.beginPath();
        context.moveTo(x, y);
        context.lineTo(x - length * slant, y + length);
        context.stroke();
        context.shadowBlur = 0;
    });
}

function createWindTrails(width, height, random, storm) {
    const count = Math.min(storm ? 7 : 6, Math.max(3, Math.round(width / 260)));

    return Array.from({ length: count }, (_, index) => ({
        x: random() * width,
        y: random() * height,
        length: 78 + random() * 72,
        bend: 6 + random() * 10,
        speed: 9 + random() * 12,
        phase: random() * Math.PI * 2,
        secondPhase: random() * Math.PI * 2,
        accent: index === 0,
    }));
}

function drawWindTrails(context, trails, width, elapsed, storm, sectionAt, gustStrength, dayPeriod) {
    const warmPeriod = dayPeriod === 'dawn' || dayPeriod === 'sunset' || dayPeriod === 'twilight';

    trails.forEach((trail) => {
        const cycle = width + trail.length * 2;
        const x = (trail.x + elapsed * trail.speed) % cycle - trail.length;
        const y = trail.y + Math.sin(elapsed * .34 + trail.phase) * 10
            + Math.sin(elapsed * .83 + trail.secondPhase) * 4;
        const fade = Math.max(0, Math.sin(elapsed * .46 + trail.phase)) ** 2;
        if (fade < .08) {
            return;
        }

        const section = sectionAt(y);
        const opacity = (storm ? .16 : .12) * fade * (1 - section * .42);
        const warm = trail.accent && warmPeriod;
        const violet = trail.accent && !warm;
        const red = warm ? Math.round(203 - section * 32)
            : violet ? Math.round(194 - section * 49) : Math.round(192 - section * 83);
        const green = warm ? Math.round(158 - section * 36)
            : violet ? Math.round(196 - section * 64) : Math.round(213 - section * 79);
        const blue = warm ? Math.round(171 - section * 35)
            : violet ? Math.round(222 - section * 32) : Math.round(231 - section * 69);
        const curve = trail.bend * (1 + Math.sin(elapsed * .56 + trail.secondPhase) * .4)
            + gustStrength * 5;
        const gradient = context.createLinearGradient(x, y, x + trail.length, y);
        gradient.addColorStop(0, `rgba(${red}, ${green}, ${blue}, 0)`);
        gradient.addColorStop(.32, `rgba(${red}, ${green}, ${blue}, ${opacity})`);
        gradient.addColorStop(.68, `rgba(${red}, ${green}, ${blue}, ${opacity * .75})`);
        gradient.addColorStop(1, `rgba(${red}, ${green}, ${blue}, 0)`);
        context.strokeStyle = gradient;
        context.lineWidth = storm ? 1.25 : .95;
        context.lineCap = 'round';
        context.beginPath();
        context.moveTo(x, y);
        context.bezierCurveTo(x + trail.length * .24, y - curve,
            x + trail.length * .32, y - curve, x + trail.length * .5, y);
        context.bezierCurveTo(x + trail.length * .7, y + curve,
            x + trail.length * .82, y + curve * .75,
            x + trail.length, y - curve * .25);
        context.stroke();
    });
}

function stormWind(elapsed, strikeAt) {
    const current = Math.sin(elapsed * .7) * .24 + Math.sin(elapsed * 1.6 + 1.1) * .16;
    const age = elapsed - strikeAt;
    const recoil = strikeAt >= 0 && age < 2.5
        ? Math.sin(age * 8) * Math.exp(-age * 1.3) * .56 : 0;

    return Math.max(-1, Math.min(1, current + recoil));
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
            gradient.addColorStop(0, 'rgba(237, 249, 255, .13)');
            gradient.addColorStop(.38, 'rgba(211, 234, 249, .06)');
            gradient.addColorStop(1, 'rgba(13, 36, 57, .13)');
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
            context.strokeStyle = 'rgba(241, 252, 255, .13)';
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
            gradient.addColorStop(0, 'rgba(240, 251, 255, .18)');
            gradient.addColorStop(.45, 'rgba(214, 237, 249, .04)');
            gradient.addColorStop(1, 'rgba(19, 42, 61, .12)');
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
    let pageWindTrails = [];
    let droplets = [];
    let frame;
    let lastFrame = 0;
    let elapsed = 0;
    let nextStrike = 9;
    let strikeAt = -1;
    let strikeSeed = 35;
    let renderedFlash = null;
    let renderedWind = null;
    let lastWindUpdate = 0;
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

    function setWind(value) {
        if (value !== 0) {
            const now = performance.now();
            if (now - lastWindUpdate < 32
                || (renderedWind !== null && Math.abs(value - renderedWind) < .03)) {
                return;
            }
            lastWindUpdate = now;
        }

        if (value !== renderedWind) {
            renderedWind = value;
            hero.style.setProperty('--weather-wind', value.toFixed(3));
        }
    }

    function resize() {
        measureGeometry();
        sky = fitCanvas(skyCanvas);
        photo = fitCanvas(sceneCanvas);
        page = fitCanvas(pageCanvas);
        const random = randomGenerator(92673 + weather.length * 413);
        pageParticles = createParticles(page.width, page.height, weather, random);
        pageWindTrails = wetWeather.has(weather)
            ? createWindTrails(page.width, page.height, random, weather === 'storm') : [];
        droplets = createDroplets(photo.width, photo.height, random);
        skyClouds = weather !== 'clear' ? createCloudField(sky.width, sky.height, weather, 413) : null;
        sceneFog = weather === 'fog' ? createCloudField(photo.width, photo.height, 'fog', 714) : null;
        pageFog = weather === 'fog' ? createCloudField(page.width, page.height, 'fog', 817) : null;
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
            setWind(0);
            return;
        }

        const sectionAt = (y) => Math.max(0, Math.min(1, (y - heroBottom + 120) / 240));

        const storm = weather === 'storm';
        const windStrength = storm && shouldAnimate() ? stormWind(elapsed, strikeAt) : 0;
        setWind(windStrength);
        const streaks = wetWeather.has(weather)
            ? rainStreaks(pageParticles, pageWidth, pageHeight, elapsed, storm, windStrength) : null;

        if (weather === 'fog') {
            drawCloudField(pageContext, pageFog, elapsed, .3);
        } else if (weather === 'snow') {
            drawSnow(pageContext, pageParticles, pageWidth, pageHeight, elapsed, snowGlow, sectionAt);
        } else if (streaks) {
            drawRain(pageContext, streaks, sectionAt);
            drawWindTrails(pageContext, pageWindTrails, pageWidth, elapsed,
                storm, sectionAt, windStrength, document.documentElement.dataset.dayPeriod);
        }

        if (!showHero) {
            setFlash(0);
            return;
        }

        drawCloudField(skyContext, skyClouds, elapsed);

        if (weather === 'fog') {
            drawCloudField(sceneContext, sceneFog, elapsed);
        } else if (weather === 'snow') {
            sceneContext.save();
            sceneContext.translate(-sceneLeft, -sceneTop);
            drawSnow(sceneContext, pageParticles, pageWidth, pageHeight, elapsed, snowGlow);
            sceneContext.restore();
        } else if (streaks) {
            sceneContext.save();
            sceneContext.translate(-sceneLeft, -sceneTop);
            drawRain(sceneContext, streaks, () => 0, {
                    left: sceneLeft,
                    top: sceneTop,
                    right: sceneLeft + sceneWidth,
                    bottom: sceneTop + sceneHeight,
                }, true);
            sceneContext.restore();
            drawGlassDroplets(sceneContext, droplets, sceneWidth, sceneHeight, elapsed, storm);
        }

        let flash = 0;
        if (weather === 'storm' && strikeAt >= 0) {
            const age = elapsed - strikeAt;
            flash = age < .09 ? 1 : age > .17 && age < .25 ? .67 : 0;
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

        if (lastFrame && now - lastFrame < 10) {
            frame = requestAnimationFrame(tick);
            return;
        }

        elapsed += lastFrame ? Math.min((now - lastFrame) / 1000, .12) : 0;
        lastFrame = now;
        if (weather === 'storm' && elapsed >= nextStrike) {
            strikeAt = elapsed;
            strikeSeed += 137;
            nextStrike = elapsed + 9 + randomGenerator(strikeSeed)() * 7;
        }
        draw();

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
        setWind(0);
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
