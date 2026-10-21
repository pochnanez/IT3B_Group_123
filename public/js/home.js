(() => {
    const toggle = document.querySelector('.menu-toggle');
    const navigation = document.getElementById('site-navigation');
    const mobile = window.matchMedia('(max-width: 1100px)');
    if (!toggle || !navigation) return;

    const closeMenu = () => {
        toggle.setAttribute('aria-expanded', 'false');
        navigation.classList.remove('is-open');
    };
    toggle.hidden = false;
    document.documentElement.classList.add('js');
    toggle.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') !== 'true';
        toggle.setAttribute('aria-expanded', String(open));
        navigation.classList.toggle('is-open', open);
    });
    navigation.addEventListener('click', event => {
        const link = event.target.closest('a');
        if (!link) return;
        closeMenu();
        if (mobile.matches && link.hash && link.origin === location.origin) {
            const section = document.getElementById(link.hash.slice(1));
            if (section) {
                section.setAttribute('tabindex', '-1');
                section.focus({preventScroll: true});
                section.addEventListener('blur', () => section.removeAttribute('tabindex'), {once: true});
            }
        }
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            closeMenu();
            toggle.focus();
        }
    });
    document.addEventListener('click', event => {
        if (!event.target.closest('.nav-wrap')) closeMenu();
    });
    navigation.addEventListener('focusout', event => {
        if (!navigation.contains(event.relatedTarget) && event.relatedTarget !== toggle) closeMenu();
    });
    mobile.addEventListener('change', closeMenu);
})();
