(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["raeuchermehl-birke-click-001"] = function (event) { openZoom('assets/raeuchermehl-birke-produkt.jpg') };
  handlers["raeuchermehl-birke-click-002"] = function (event) { addToCart('mehl-birke') };
  handlers["raeuchermehl-birke-click-003"] = function (event) { openCart() };
  handlers["raeuchermehl-birke-click-004"] = function (event) { closeCart() };
  handlers["raeuchermehl-birke-click-005"] = function (event) { closeCart() };
  handlers["raeuchermehl-birke-click-006"] = function (event) { checkout() };
  handlers["raeuchermehl-birke-click-007"] = function (event) { closeZoom() };
})();
