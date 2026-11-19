
(function () {
  'use strict';
  var form = document.getElementById('filter-form');
  var grid = document.getElementById('equipment-grid');
  if (!form || !grid) return;

  var search = document.getElementById('q');
  var category = document.getElementById('category');
  var availability = document.getElementById('availability');
  var info = document.getElementById('result-info');
  var empty = document.getElementById('empty-state');
  var cards = Array.prototype.slice.call(grid.querySelectorAll('.equipment-card'));

  function applyFilters() {
    var text = search.value.trim().toLowerCase();
    var cat = category.value;
    var onlyAvailable = availability.value === 'available';
    var shown = 0;

    cards.forEach(function (card) {
      var ok = (!text || card.getAttribute('data-name').indexOf(text) !== -1) &&
               (cat === '0' || card.getAttribute('data-category') === cat) &&
               (!onlyAvailable || card.getAttribute('data-available') === '1');
      card.hidden = !ok;
      if (ok) shown++;
    });

    info.textContent = shown + ' item type' + (shown === 1 ? '' : 's') + ' found';
    empty.hidden = shown !== 0;

   
    var params = new URLSearchParams();
    if (text) params.set('q', search.value.trim());
    if (cat !== '0') params.set('category', cat);
    if (onlyAvailable) params.set('availability', 'available');
    var qs = params.toString();
    history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
  }

  var timer;
  search.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(applyFilters, 150); });
  category.addEventListener('change', applyFilters);
  availability.addEventListener('change', applyFilters);
  form.addEventListener('submit', function (e) { e.preventDefault(); applyFilters(); });
  form.addEventListener('reset', function () { setTimeout(applyFilters, 0); });
})();
