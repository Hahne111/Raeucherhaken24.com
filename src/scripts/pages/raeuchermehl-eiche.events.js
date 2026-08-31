(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["raeuchermehl-eiche-click-001"] = function (event) { openZoom('assets/raeuchermehl-eiche-produkt.jpg') };
  handlers["raeuchermehl-eiche-click-002"] = function (event) { addToCart('mehl-eiche') };
  handlers["raeuchermehl-eiche-click-003"] = function (event) { openCart() };
  handlers["raeuchermehl-eiche-click-004"] = function (event) { closeCart() };
  handlers["raeuchermehl-eiche-click-005"] = function (event) { closeCart() };
  handlers["raeuchermehl-eiche-click-006"] = function (event) { checkout() };
  handlers["raeuchermehl-eiche-click-007"] = function (event) { closeZoom() };
})();
