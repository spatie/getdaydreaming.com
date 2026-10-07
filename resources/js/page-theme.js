import { mixColor } from './sky-theme.js';

const weatherTones = {
    rain: { day: '#cadde5', night: '#15253d', strength: .34 },
    snow: { day: '#e4f2f8', night: '#273952', strength: .2 },
    fog: { day: '#d9e5e8', night: '#203149', strength: .32 },
    storm: { day: '#b9cedb', night: '#101c32', strength: .48 },
};

const gradientStops = [
    ['#fff5d7', '#040816'],
    ['#ffdcc5', '#071126'],
    ['#f7d5db', '#0b1730'],
    ['#dcdaf5', '#0a162c'],
    ['#d2e8f5', '#081329'],
];

export function pageColors(palette, weather = 'clear') {
    const nightWeight = Math.min(1, palette.stars * 1.12);
    const crossingWeight = Math.min(1,
        Math.max(0, (palette.stars - .14) / .19),
        Math.max(0, (.82 - palette.stars) / .2));
    const weatherTone = weatherTones[weather];

    return gradientStops.map(([day, night]) => {
        let color = mixColor(day, night, nightWeight);
        color = mixColor(color, palette.background, .1);

        if (weatherTone) {
            const tintColor = mixColor(weatherTone.day, weatherTone.night, nightWeight);
            color = mixColor(color, tintColor, weatherTone.strength);
        }

        return mixColor(color, palette.background, crossingWeight * .95);
    });
}
