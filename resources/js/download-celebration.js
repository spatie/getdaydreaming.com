const colors = ['#fff3bd', '#ffd19d', '#f2a5b7', '#b9b7f5', '#9bd8ed'];

function createCelebration(link) {
    const bounds = link.getBoundingClientRect();
    const originX = bounds.left + bounds.width / 2;
    const originY = bounds.top + bounds.height / 2;
    const celebration = document.createElement('div');
    celebration.className = 'download-celebration';
    celebration.setAttribute('aria-hidden', 'true');
    celebration.style.setProperty('--origin-x', `${originX}px`);
    celebration.style.setProperty('--origin-y', `${originY}px`);

    for (let index = 0; index < 28; index++) {
        const angle = index * 2.399963 + Math.sin(index * 4.7) * .18;
        const distance = 76 + index % 6 * 19;
        const endX = Math.cos(angle) * distance;
        const endY = Math.sin(angle) * distance + 38;
        const particle = document.createElement('span');
        particle.className = index % 4 === 0 ? 'download-particle download-particle-star' : 'download-particle';
        particle.style.setProperty('--mid-x', `${Math.round(endX * .52 - Math.sin(angle) * 19)}px`);
        particle.style.setProperty('--mid-y', `${Math.round(endY * .5 - 34)}px`);
        particle.style.setProperty('--end-x', `${Math.round(endX)}px`);
        particle.style.setProperty('--end-y', `${Math.round(endY)}px`);
        particle.style.setProperty('--turn', `${(index % 2 ? -1 : 1) * (120 + index % 5 * 45)}deg`);
        particle.style.setProperty('--particle-color', colors[index % colors.length]);
        particle.style.animationDelay = `${index % 5 * 16}ms`;
        celebration.append(particle);
    }

    document.body.append(celebration);

    return celebration;
}

export function prepareDownloadCelebration(motionPreference, saveData) {
    if (saveData) {
        document.documentElement.dataset.saveData = 'true';
    }

    let downloadPending = false;

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const link = event.target.closest('a[data-download-celebration]');

        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey
            || event.shiftKey || event.altKey || (link.target && link.target !== '_self')) {
            return;
        }

        if (motionPreference.matches || saveData) {
            return;
        }

        event.preventDefault();

        if (downloadPending) {
            return;
        }

        downloadPending = true;
        const celebration = createCelebration(link);
        link.classList.add('is-celebrating');

        window.setTimeout(() => window.location.assign(link.href), 650);
        window.setTimeout(() => {
            celebration.remove();
            link.classList.remove('is-celebrating');
            downloadPending = false;
        }, 1600);
    });
}
