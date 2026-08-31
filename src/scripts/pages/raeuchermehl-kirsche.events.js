(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["raeuchermehl-kirsche-click-001"] = function (event) { openZoom('assets/raeuchermehl-kirsche-produkt.jpg') };
  handlers["raeuchermehl-kirsche-click-002"] = function (event) { addToCart('mehl-kirsche') };
  handlers["raeuchermehl-kirsche-click-003"] = function (event) { openCart() };
  handlers["raeuchermehl-kirsche-click-004"] = function (event) { closeCart() };
  handlers["raeuchermehl-kirsche-click-005"] = function (event) { closeCart() };
  handlers["raeuchermehl-kirsche-click-006"] = function (event) { checkout() };
  handlers["raeuchermehl-kirsche-click-007"] = function (event) { closeZoom() };
})();
