export function framePair(frames, hour) {
    const minutes = Math.max(0, Math.min(24, hour)) * 60;
    const lower = frames.findLast((frame) => frame.minutes <= minutes) ?? frames.at(-1);
    const upper = frames.find((frame) => frame.minutes > minutes) ?? frames[0];
    const start = lower.minutes;
    const end = upper.minutes > start ? upper.minutes : upper.minutes + 1440;
    return { lower, upper, weight: (minutes - start) / (end - start) };
}

export function keyboardHour(key, hour) {
    const offsets = { ArrowRight: 1, ArrowUp: 1, ArrowLeft: -1, ArrowDown: -1, PageUp: 6, PageDown: -6 };
    if (key === 'Home') {
        return 0;
    }
    if (key === 'End') {
        return 24;
    }
    if (!(key in offsets)) {
        return null;
    }
    const start = offsets[key] > 0 ? Math.floor(hour) : Math.ceil(hour);
    return Math.max(0, Math.min(24, start + offsets[key]));
}

export function shouldAutoplay({ reducedMotion, saveData }) {
    return !reducedMotion && !saveData;
}
