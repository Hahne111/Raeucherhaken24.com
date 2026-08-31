(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["raeuchermehl-buche-click-001"] = function (event) { openZoom('assets/raeuchermehl-buche-produkt.jpg') };
  handlers["raeuchermehl-buche-click-002"] = function (event) { addToCart('mehl-buche') };
  handlers["raeuchermehl-buche-click-003"] = function (event) { openCart() };
  handlers["raeuchermehl-buche-click-004"] = function (event) { closeCart() };
  handlers["raeuchermehl-buche-click-005"] = function (event) { closeCart() };
  handlers["raeuchermehl-buche-click-006"] = function (event) { checkout() };
  handlers["raeuchermehl-buche-click-007"] = function (event) { closeZoom() };
})();
