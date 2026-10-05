const moments = {
    morning: {
        label: '01 / MORNING LIGHT',
        status: 'A fresh start, softly lit',
        scene: 'A mountain landscape in morning light',
    },
    golden: {
        label: '02 / GOLDEN HOUR',
        status: 'The warmth before evening',
        scene: 'The same mountain landscape at golden hour',
    },
    rain: {
        label: '03 / AFTER RAIN',
        status: 'A quieter kind of afternoon',
        scene: 'The same mountain landscape during rain',
    },
};

const scene = document.getElementById('scene');

document.querySelectorAll('[data-select-moment]').forEach((button) => {
    button.addEventListener('click', () => {
        const moment = moments[button.dataset.selectMoment];

        scene.dataset.moment = button.dataset.selectMoment;
        document.getElementById('preview-current').textContent = moment.label;
        document.getElementById('scene-status-text').textContent = moment.status;
        document.getElementById('scene-title').textContent = moment.scene;

        document.querySelectorAll('[data-select-moment]').forEach((option) => {
            const selected = option === button;

            option.classList.toggle('is-active', selected);
            option.setAttribute('aria-pressed', String(selected));
        });
    });
});
