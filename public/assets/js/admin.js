
document.querySelectorAll('form[method="post"]').forEach(form => {
  const dates = form.querySelectorAll('input[type="date"]');
  dates.forEach(input => input.addEventListener('change', () => {
    if (input.name === 'start_date') {
      const due = form.querySelector('[name="due_date"]');
      if (due) due.min = input.value;
    }
  }));
});
