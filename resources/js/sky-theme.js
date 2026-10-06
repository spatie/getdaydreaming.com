const palettes = [
    { hour: 0, background: '#050a17', surface: '#0d1b31', ink: '#f2f5f8', muted: '#bdccdc', line: '#40536c', accent: '#a9d7ee', privacy: '#102332', control: '#14243b', selected: '#293c56', sky: '#020610', glow: '#182948', stars: 1 },
    { hour: 4, background: '#0b1730', surface: '#1c2d49', ink: '#f2f5f8', muted: '#c4cedb', line: '#52647b', accent: '#bfdbec', privacy: '#18313e', control: '#21324d', selected: '#384e68', sky: '#071126', glow: '#344a76', stars: .88 },
    { hour: 5.25, background: '#0b1730', surface: '#1c2d49', ink: '#f2f5f8', muted: '#c4cedb', line: '#52647b', accent: '#bfdbec', privacy: '#18313e', control: '#21324d', selected: '#384e68', sky: '#071126', glow: '#344a76', stars: .88 },
    { hour: 5.75, background: '#f4d1c4', surface: '#f2dbcf', ink: '#26374c', muted: '#42526a', line: '#8d8ca0', accent: '#305e7a', privacy: '#e1d5cb', control: '#f9e2d4', selected: '#ffe3c4', sky: '#e7adac', glow: '#ffd8a7', stars: .04 },
    { hour: 6, background: '#f9ddc9', surface: '#f7e8d8', ink: '#26374c', muted: '#42526a', line: '#8d8ca0', accent: '#305e7a', privacy: '#e7e5d7', control: '#fff0dc', selected: '#ffdfbd', sky: '#eebfb3', glow: '#ffe0ad', stars: 0 },
    { hour: 8, background: '#fff2dd', surface: '#f5eee1', ink: '#18394a', muted: '#526675', line: '#d8dfdb', accent: '#276787', privacy: '#eaf5ed', control: '#fff9ec', selected: '#fff0c9', sky: '#f8d9b7', glow: '#ffe3aa', stars: 0 },
    { hour: 12, background: '#fffcf6', surface: '#f3f5ef', ink: '#18394a', muted: '#526675', line: '#d6e0df', accent: '#276787', privacy: '#eaf5ed', control: '#fffefa', selected: '#fff0c9', sky: '#f8f5e9', glow: '#ffeac8', stars: 0 },
    { hour: 16, background: '#fff2de', surface: '#f7ecde', ink: '#18394a', muted: '#526675', line: '#dddad2', accent: '#276787', privacy: '#ecf1e9', control: '#fff8eb', selected: '#ffe4b5', sky: '#fbe6c9', glow: '#ffd5a0', stars: 0 },
    { hour: 18, background: '#ffcfb0', surface: '#f9dfcc', ink: '#2c3c50', muted: '#4d5d70', line: '#bd9e9c', accent: '#315d78', privacy: '#f0dccd', control: '#ffe2ca', selected: '#ffd3a8', sky: '#efb6aa', glow: '#ffbd83', stars: 0 },
    { hour: 19, background: '#f4bfa5', surface: '#f7d1b8', ink: '#2c3c50', muted: '#4d5d70', line: '#bd9e9c', accent: '#315d78', privacy: '#edd1bd', control: '#f9d8c3', selected: '#ffcb9f', sky: '#dca19e', glow: '#ffb478', stars: .04 },
    { hour: 19.25, background: '#efb39b', surface: '#f4cbb4', ink: '#2c3c50', muted: '#4d5d70', line: '#bd9e9c', accent: '#315d78', privacy: '#e7c7b5', control: '#f5d0bb', selected: '#ffc49c', sky: '#d09299', glow: '#f5aa80', stars: .08 },
    { hour: 19.75, background: '#22365b', surface: '#263d5c', ink: '#f1f3f5', muted: '#d1d8e3', line: '#74809a', accent: '#c3e3f2', privacy: '#253e4e', control: '#2a3f60', selected: '#3a5472', sky: '#182a52', glow: '#8c6a88', stars: .6 },
    { hour: 20, background: '#111f3d', surface: '#1d3050', ink: '#f1f3f5', muted: '#d1d8e3', line: '#74809a', accent: '#c3e3f2', privacy: '#1a3242', control: '#203452', selected: '#334d6c', sky: '#0a1832', glow: '#455681', stars: .75 },
    { hour: 22, background: '#091329', surface: '#15243f', ink: '#f2f5f8', muted: '#c0cddd', line: '#435571', accent: '#a9d7ee', privacy: '#11283a', control: '#192941', selected: '#2d425c', sky: '#050d20', glow: '#2c416b', stars: .93 },
    { hour: 24, background: '#050a17', surface: '#0d1b31', ink: '#f2f5f8', muted: '#bdccdc', line: '#40536c', accent: '#a9d7ee', privacy: '#102332', control: '#14243b', selected: '#293c56', sky: '#020610', glow: '#182948', stars: 1 },
];

function mixColor(first, second, weight) {
    const start = first.match(/[a-f\d]{2}/gi).map(channel => parseInt(channel, 16));
    const end = second.match(/[a-f\d]{2}/gi).map(channel => parseInt(channel, 16));

    return `#${start.map((channel, index) => Math.round(channel + (end[index] - channel) * weight).toString(16).padStart(2, '0')).join('')}`;
}

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

export function skyPalette(hour) {
    const time = ((hour % 24) + 24) % 24;
    const later = palettes.find(palette => palette.hour > time) ?? palettes.at(-1);
    const earlier = palettes[palettes.indexOf(later) - 1] ?? palettes[0];
    const weight = earlier === later ? 0 : (time - earlier.hour) / (later.hour - earlier.hour);
    const palette = {};

    Object.keys(earlier).filter(key => !['hour', 'ink', 'muted', 'accent', 'line'].includes(key)).forEach(key => {
        palette[key] = key === 'stars'
            ? earlier[key] + (later[key] - earlier[key]) * weight
            : mixColor(earlier[key], later[key], weight);
    });

    const useDarkText = luminance(palette.background) >= .1833;
    const readableColor = useDarkText ? '#000000' : '#ffffff';
    const surfaces = ['background', 'surface', 'privacy', 'control', 'selected', 'sky'];

    for (let attempt = 0; attempt < 24; attempt++) {
        if (surfaces.every(key => contrast(readableColor, palette[key]) >= 4.5)) {
            break;
        }

        surfaces.slice(1).forEach(key => {
            palette[key] = mixColor(palette[key], palette.background, .25);
        });
    }

    if (!surfaces.every(key => contrast(readableColor, palette[key]) >= 4.5)) {
        surfaces.slice(1).forEach(key => { palette[key] = palette.background; });
    }

    const readableOnEverySurface = color => surfaces.every(key => contrast(color, palette[key]) >= 4.5);
    const preferredInk = useDarkText ? '#18394a' : '#f2f5f8';
    const preferredMuted = useDarkText ? '#526675' : '#dce8f2';
    const preferredAccent = useDarkText ? '#276787' : '#b9e8ff';
    const preferredLine = useDarkText ? '#647b89' : '#a6c2dc';

    const alternateMuted = useDarkText ? '#143542' : '#f2f5f8';
    const alternateAccent = useDarkText ? '#0a3550' : '#f2f5f8';
    const firstReadable = colors => colors.find(readableOnEverySurface) ?? readableColor;

    palette.ink = firstReadable([preferredInk]);
    palette.muted = firstReadable([preferredMuted, alternateMuted, palette.ink]);
    palette.accent = firstReadable([preferredAccent, alternateAccent, palette.ink]);
    palette.line = contrast(preferredLine, palette.control) >= 3 ? preferredLine : readableColor;

    return palette;
}
