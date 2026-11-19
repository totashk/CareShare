
(function () {
  'use strict';


  var toggle = document.querySelector('.nav-toggle');
  var nav = document.getElementById('site-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && nav.classList.contains('open')) { toggle.click(); toggle.focus(); }
    });
  }


  function messageFor(field, form) {
    var v = field.validity;
    if (v.valueMissing) return field.type === 'checkbox' ? 'Please tick this box to continue.' : 'Please fill in this field.';
    if (v.typeMismatch && field.type === 'email') return 'Enter a valid email address, e.g. name@example.com.';
    if (v.tooShort) return 'Use at least ' + field.minLength + ' characters.';
    if (v.tooLong) return 'Use no more than ' + field.maxLength + ' characters.';
    if (v.patternMismatch) return field.getAttribute('data-pattern-message') || 'Please check the format of this field.';
    if (v.rangeUnderflow || v.rangeOverflow) return 'Please choose a valid date.';
    var match = field.getAttribute('data-match');
    if (match) {
      var other = form.querySelector('[name="' + match + '"]');
      if (other && other.value !== field.value) return 'The two passwords do not match.';
    }
    return '';
  }

  function showError(field, message) {
    var wrap = field.closest('.field');
    if (!wrap) return;
    var err = wrap.querySelector('.field-error');
    if (message) {
      wrap.classList.add('has-error');
      field.setAttribute('aria-invalid', 'true');
      if (!err) {
        err = document.createElement('div');
        err.className = 'field-error';
        err.id = (field.id || field.name) + '-error';
        wrap.appendChild(err);
        field.setAttribute('aria-describedby', err.id);
      }
      err.textContent = message;
    } else {
      wrap.classList.remove('has-error');
      field.removeAttribute('aria-invalid');
      if (err && err.getAttribute('data-server') !== '1') err.remove();
    }
  }

  document.querySelectorAll('form[data-validate]').forEach(function (form) {
    form.setAttribute('novalidate', 'novalidate');
    var fields = form.querySelectorAll('input:not([type=hidden]), textarea, select');

    form.addEventListener('submit', function (e) {
      var firstBad = null;
      fields.forEach(function (f) {
        var msg = messageFor(f, form);
        if (msg) { showError(f, msg); firstBad = firstBad || f; }
        else showError(f, '');
      });
      if (firstBad) { e.preventDefault(); firstBad.focus(); }
    });

    fields.forEach(function (f) {
      f.addEventListener('input', function () {
        if (f.closest('.field') && f.closest('.field').classList.contains('has-error')) {
          showError(f, messageFor(f, form));
        }
      });
    });
  });


  var start = document.querySelector('input[name="start_date"]');
  var due = document.querySelector('input[name="due_date"]');
  if (start && due) {
    start.addEventListener('change', function () {
      if (start.value) {
        var d = new Date(start.value + 'T00:00:00');
        d.setDate(d.getDate() + 1);
        due.min = d.toISOString().slice(0, 10);
        if (due.value && due.value < due.min) due.value = due.min;
      }
    });
  }


  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!window.confirm(el.getAttribute('data-confirm'))) e.preventDefault();
    });
  });
})();
