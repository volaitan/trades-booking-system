// Booking form: load free time slots whenever the service or date changes.
(function () {
  const form = document.getElementById('booking-form');
  if (form) {
    const date = document.getElementById('date');
    const time = document.getElementById('time');
    const wanted = time.dataset.selected;

    async function loadSlots() {
      const service = form.querySelector('input[name="service_id"]:checked');
      if (!service || !date.value) return;
      time.innerHTML = '<option value="">Loading…</option>';
      try {
        const res = await fetch(`/slots?service_id=${service.value}&date=${date.value}`);
        const data = await res.json();
        const slots = data.slots || [];
        time.innerHTML = slots.length
          ? '<option value="">Choose a time…</option>' + slots.map(s => `<option ${s === wanted ? 'selected' : ''}>${s}</option>`).join('')
          : '<option value="">No times left on this day</option>';
      } catch {
        time.innerHTML = '<option value="">Could not load times</option>';
      }
    }
    form.addEventListener('change', (e) => {
      if (e.target.name === 'service_id' || e.target === date) loadSlots();
    });
    loadSlots();
  }

  // Admin: status dropdowns save as soon as they change.
  document.querySelectorAll('select.auto-submit').forEach((select) => {
    select.addEventListener('change', () => select.form.submit());
  });

  const printBtn = document.getElementById('print-btn');
  if (printBtn) printBtn.addEventListener('click', () => window.print());
})();
