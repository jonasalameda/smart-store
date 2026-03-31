 // fan
$(document).ready(function() {
    const $fan = $('#fan-img');
    const $button = $('#fan-toggle');
    const $status = $('#fan-status');

    let isOn = false;

    $button.click(function() {
     isOn = !isOn;

    // Call backend to toggle GPIO
    fetch(APP_BASE_URL + `/toggle-fan?state=${isOn ? 'on' : 'off'}`)
        .then(res => res.json())
        .then(data => console.log('Fan toggle response:', data))
        .catch(err => console.error('Fan toggle error:', err));

    // Update UI
    $fan.css('animation', isOn ? 'fananim 1s linear infinite' : 'none');
    $button.removeClass(isOn ? 'fan-off' : 'fan-on').addClass(isOn ? 'fan-on' : 'fan-off').text(isOn ? 'ON' : 'OFF');
    $status.text(`Status: ${isOn ? 'ON' : 'OFF'}`);
});
});