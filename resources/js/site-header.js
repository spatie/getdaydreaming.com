const siteHeader = document.querySelector('.site-header');

if (siteHeader) {
    let isScrolled = false;

    const updateHeader = () => {
        const shouldBeScrolled = window.scrollY > 64;

        if (shouldBeScrolled !== isScrolled) {
            isScrolled = shouldBeScrolled;
            siteHeader.classList.toggle('is-scrolled', isScrolled);
        }
    };

    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });
}
