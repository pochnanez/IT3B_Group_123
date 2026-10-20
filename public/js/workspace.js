document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
}));
document.querySelector('.print-receipt')?.addEventListener('click', () => window.print());
document.getElementById('chart-series')?.addEventListener('change', event => {
    document.querySelectorAll('.chart-panel').forEach(panel => { panel.hidden = event.target.value !== 'all' && panel.dataset.series !== event.target.value; });
});
document.querySelectorAll('.gps-checkin').forEach(form => {
    const button = form.querySelector('.gps-button');
    const status = form.querySelector('.gps-status');
    button.addEventListener('click', () => {
        if (!navigator.geolocation) { status.textContent = 'Location is unavailable. Use the manual check-in option below.'; return; }
        button.disabled = true;
        status.textContent = 'Requesting your current location. Please allow location access if you want to record GPS coordinates.';
        navigator.geolocation.getCurrentPosition(position => {
            form.elements.latitude.value = position.coords.latitude;
            form.elements.longitude.value = position.coords.longitude;
            form.elements.manual_reason.value = '';
            status.textContent = 'Location received. Saving check-in…';
            form.requestSubmit();
        }, () => {
            button.disabled = false;
            status.textContent = 'Location could not be obtained. You can retry or use the manual check-in option below.';
            form.querySelector('details').open = true;
        }, {enableHighAccuracy: true, timeout: 15000, maximumAge: 0});
    });
    form.addEventListener('submit', event => {
        if (event.submitter?.name === 'manual') {
            form.elements.latitude.value = '';
            form.elements.longitude.value = '';
            if (form.elements.manual_reason.value.trim().length < 10) { event.preventDefault(); status.textContent = 'Explain the manual location exception in at least 10 characters.'; }
        }
    });
});
