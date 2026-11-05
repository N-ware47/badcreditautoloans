/* The calculator is entirely local: no storage, analytics, or network requests. */
(() => {
  'use strict';

  document.documentElement.classList.add('bc-has-js');

  const toggle = document.querySelector('.bc-menu-toggle');
  const navigation = document.querySelector('#bc-primary-navigation');

  if (toggle && navigation) {
    const setMenu = (open) => {
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
      navigation.classList.toggle('is-open', open);
    };

    toggle.addEventListener('click', () => setMenu(toggle.getAttribute('aria-expanded') !== 'true'));
    navigation.addEventListener('click', (event) => {
      if (event.target.closest('a')) setMenu(false);
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
        setMenu(false);
        toggle.focus();
      }
    });
    window.matchMedia('(min-width: 960px)').addEventListener('change', () => setMenu(false));
  }

  const calculator = document.querySelector('[data-bc-calculator]');
  if (!calculator) return;

  const amount = calculator.querySelector('[name="amount"]');
  const interest = calculator.querySelector('[name="interest"]');
  const term = calculator.querySelector('[name="term"]');
  const amountDisplay = calculator.querySelector('#bc-amount-display');
  const monthlyDisplay = calculator.querySelector('#bc-monthly-payment');
  const interestDisplay = calculator.querySelector('#bc-total-interest');
  const totalDisplay = calculator.querySelector('#bc-total-payment');
  const error = calculator.querySelector('[data-bc-calculator-error]');
  const currency = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 });

  const updateEstimate = () => {
    const principal = Number(amount.value);
    const months = Number(term.value);
    const annualRate = Number(interest.value);
    const valid = amount.checkValidity() && interest.checkValidity() && term.checkValidity();

    amountDisplay.value = currency.format(principal);
    amount.setAttribute('aria-valuetext', currency.format(principal));
    amount.style.setProperty('--bc-range-progress', `${((principal - Number(amount.min)) / (Number(amount.max) - Number(amount.min))) * 100}%`);
    error.hidden = valid;
    interest.setAttribute('aria-invalid', String(!interest.checkValidity()));

    if (!valid || !Number.isFinite(annualRate)) {
      monthlyDisplay.value = '—';
      interestDisplay.textContent = '—';
      totalDisplay.textContent = '—';
      return;
    }

    const monthlyRate = annualRate / 1200;
    const monthlyPayment = monthlyRate === 0
      ? principal / months
      : (principal * monthlyRate) / (1 - Math.pow(1 + monthlyRate, -months));
    const totalPayment = monthlyPayment * months;

    monthlyDisplay.value = currency.format(monthlyPayment);
    interestDisplay.textContent = currency.format(Math.max(0, totalPayment - principal));
    totalDisplay.textContent = currency.format(totalPayment);
  };

  calculator.addEventListener('input', updateEstimate);
  calculator.addEventListener('change', updateEstimate);
  calculator.addEventListener('submit', (event) => {
    event.preventDefault();
    updateEstimate();
  });
  updateEstimate();
})();
