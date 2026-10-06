import test from 'node:test';
import assert from 'node:assert/strict';
import { skyPalette } from '../resources/js/sky-theme.js';

function luminance(color) {
    const channels = color.match(/[a-f\d]{2}/gi).map(channel => parseInt(channel, 16) / 255);
    const linear = channels.map(channel => channel <= .04045
        ? channel / 12.92
        : ((channel + .055) / 1.055) ** 2.4);

    return linear[0] * .2126 + linear[1] * .7152 + linear[2] * .0722;
}

function contrast(first, second) {
    const brighter = Math.max(luminance(first), luminance(second));
    const darker = Math.min(luminance(first), luminance(second));

    return (brighter + .05) / (darker + .05);
}

test('the sky moves gradually from daylight through dusk into night', () => {
    assert.equal(skyPalette(12).stars, 0);
    assert.ok(skyPalette(19).stars > skyPalette(18).stars);
    assert.ok(skyPalette(19).stars < skyPalette(20).stars);
    assert.notEqual(skyPalette(18).background, skyPalette(19).background);
    assert.equal(skyPalette(0).background, skyPalette(24).background);
    assert.equal(skyPalette(0).stars, skyPalette(24).stars);
});

test('every quarter hour keeps text and control boundaries readable', () => {
    for (let quarter = 0; quarter <= 96; quarter++) {
        const hour = quarter / 4;
        const palette = skyPalette(hour);

        for (const surface of ['background', 'surface', 'privacy', 'control', 'selected', 'sky']) {
            for (const textColor of ['ink', 'muted', 'accent']) {
                assert.ok(contrast(palette[textColor], palette[surface]) >= 4.5,
                    `${textColor} on ${surface} at ${hour}:00`);
            }
        }

        assert.ok(contrast(palette.line, palette.control) >= 3, `control boundary at ${hour}:00`);
    }
});

test('secondary and accent colors keep their hierarchy outside brief twilight crossings', () => {
    let fallbackMinutes = 0;

    for (let minute = 0; minute < 1440; minute++) {
        const palette = skyPalette(minute / 60);

        if (['#000000', '#ffffff'].includes(palette.muted)
            || ['#000000', '#ffffff'].includes(palette.accent)) {
            fallbackMinutes++;
        }
    }

    assert.ok(fallbackMinutes <= 45, `${fallbackMinutes} minutes without secondary color`);

    for (const hour of [5, 6, 7, 17, 18, 19, 20]) {
        const palette = skyPalette(hour);
        assert.notEqual(palette.muted, '#000000');
        assert.notEqual(palette.accent, '#000000');
        assert.notEqual(palette.muted, '#ffffff');
        assert.notEqual(palette.accent, '#ffffff');
    }
});
