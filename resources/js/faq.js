export function prepareFaq(motionPreference) {
    document.querySelectorAll('.faq-list details').forEach(details => {
        const summary = details.querySelector('summary');
        const content = details.querySelector(':scope > div');
        let animation;
        let expanded = details.open;

        summary.addEventListener('click', event => {
            if (motionPreference.matches || !details.animate) {
                animation?.cancel();
                animation = null;
                expanded = !details.open;

                return;
            }

            event.preventDefault();
            expanded = !expanded;

            const startHeight = details.getBoundingClientRect().height;

            animation?.cancel();

            if (expanded) {
                details.open = true;
            }

            const endHeight = summary.offsetHeight + (expanded ? content.offsetHeight : 0);

            animation = details.animate([
                { height: `${startHeight}px` },
                { height: `${endHeight}px` },
            ], {
                duration: 280,
                easing: 'cubic-bezier(.2, 0, 0, 1)',
            });

            animation.onfinish = () => {
                if (!expanded) {
                    details.open = false;
                }

                animation = null;
            };
        });
    });
}
