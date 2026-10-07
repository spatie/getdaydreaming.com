import test from 'node:test';
import assert from 'node:assert/strict';
import { framePair, keyboardHour, shouldAutoplay } from '../resources/js/photo-demo.js';

const weather = [0, 7, 14, 19].map((hour) => ({ minutes: hour * 60, file: `fog-${hour}` }));
test('weather blends through midnight without dropping the selected weather', () => {
    const afternoon = framePair(weather, 10.5);
    assert.equal(afternoon.lower.file, 'fog-7');
    assert.equal(afternoon.upper.file, 'fog-14');
    assert.equal(afternoon.weight, .5);
    const night = framePair(weather, 21.5);
    assert.equal(night.upper.file, 'fog-0');
    assert.equal(night.weight, .5);
    assert.equal(framePair(weather, 24).weight, 1);
});
test('continuous pointer values retain meaningful bounded keyboard steps', () => {
    assert.equal(keyboardHour('ArrowRight', 10.25), 11);
    assert.equal(keyboardHour('PageDown', 4), 0);
    assert.equal(keyboardHour('PageUp', 21), 24);
    assert.equal(keyboardHour('Home', 10), 0);
    assert.equal(keyboardHour('End', 10), 24);
    assert.equal(keyboardHour('Tab', 10), null);
});
test('autoplay defaults off for reduced motion and reduced data', () => {
    assert.equal(shouldAutoplay({ reducedMotion: true, saveData: false }), false);
    assert.equal(shouldAutoplay({ reducedMotion: false, saveData: true }), false);
    assert.equal(shouldAutoplay({ reducedMotion: false, saveData: false }), true);
});
