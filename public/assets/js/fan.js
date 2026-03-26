// fan
$(document).ready(function() {
    const $fan = $('#fan-img');
    const $button = $('#fan-toggle');
    const $status = $('#fan-status');

    let isOn = false;

    $button.click(function() {
        if (!isOn) {
            // Turn fan ON
            $fan.css('animation', 'fananim 1s linear infinite');
            $button.removeClass('fan-off').addClass('fan-on').text('ON');
            $status.text('Status: ON');
            isOn = true;
        } else {
            // Turn fan OFF
            $fan.css('animation', 'none');
            $button.removeClass('fan-on').addClass('fan-off').text('OFF');
            $status.text('Status: OFF');
            isOn = false;
        }
    });
});