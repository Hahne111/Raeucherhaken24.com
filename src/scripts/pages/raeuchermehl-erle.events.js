(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["raeuchermehl-erle-click-001"] = function (event) { openZoom('assets/raeuchermehl-erle-produkt.jpg') };
  handlers["raeuchermehl-erle-click-002"] = function (event) { addToCart('mehl-erle') };
  handlers["raeuchermehl-erle-click-003"] = function (event) { openCart() };
  handlers["raeuchermehl-erle-click-004"] = function (event) { closeCart() };
  handlers["raeuchermehl-erle-click-005"] = function (event) { closeCart() };
  handlers["raeuchermehl-erle-click-006"] = function (event) { checkout() };
  handlers["raeuchermehl-erle-click-007"] = function (event) { closeZoom() };
})();
