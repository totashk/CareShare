
(function () {
  'use strict';


  var filter = document.querySelector('.inline-filter select');
  if (filter) {
    filter.addEventListener('change', function () { filter.form.submit(); });
    var button = filter.form.querySelector('button');
    if (button) button.hidden = true;
  }

  
  var active = document.querySelector('.account-tabs a.active');
  if (active && active.scrollIntoView) active.scrollIntoView({ block: 'nearest', inline: 'center' });
})();
