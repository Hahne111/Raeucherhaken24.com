(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["fleischerhaken-s-form-5mm-click-001"] = function (event) { zoom('assets/fleischer.jpeg','Fleischerhaken S-Form 5 mm') };
  handlers["fleischerhaken-s-form-5mm-click-002"] = function (event) { addToCart('fleisch') };
  handlers["fleischerhaken-s-form-5mm-click-003"] = function (event) { openCart() };
  handlers["fleischerhaken-s-form-5mm-click-004"] = function (event) { closeCart() };
  handlers["fleischerhaken-s-form-5mm-click-005"] = function (event) { closeCart() };
  handlers["fleischerhaken-s-form-5mm-click-006"] = function (event) { checkout() };
  handlers["fleischerhaken-s-form-5mm-click-007"] = function (event) { closeZoom() };
})();
