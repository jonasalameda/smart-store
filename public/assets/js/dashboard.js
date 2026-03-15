// Thermometer gauge random simulation
setInterval(function() {
  $('.termometer .temperature').each(function(){
    var minTemp = -10;
    var maxTemp = 10; // cold fridge
    var temp = (Math.random() * (maxTemp - minTemp) + minTemp).toFixed(1);
    $(this).css('height', ((temp - minTemp)/(maxTemp - minTemp)*100)+'%');
    $(this).attr('data-value', temp + '°C');
  });
}, 3000);

// Humidity gauge random simulation
setInterval(function() {
  var humidity = (Math.random() * (100 - 20) + 20).toFixed(1);
  var humidityArcDeg = (humidity/100)*180;

  $('.humidity').each(function() {
    $(this).html(humidity);
  });

  $('.humidity-gauge .reveal').each(function() {
    $(this).css('transform','rotate(-'+humidityArcDeg+'deg)');
  });

  $('.humidity-gauge .indicator').each(function() {
    $(this).css('transform','rotate(-'+(parseInt(humidityArcDeg)+90)+'deg)');
  });

}, 3000);







const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('toggle-btn');

toggleBtn.addEventListener('click', () => {
  sidebar.classList.toggle('expanded');
});