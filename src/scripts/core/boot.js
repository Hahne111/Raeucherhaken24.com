(() => {
  const root = document.documentElement;
  root.classList.add('rh82Boot', 'rh-atelier', 'rh-atelier-js');
  window.setTimeout(() => root.classList.remove('rh82Boot'), 2500);
})();
