(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["raeucherhaken-kralle-click-001"] = function (event) { zoom('assets/kralle.png','Räucherhaken Kralle') };
  handlers["raeucherhaken-kralle-click-002"] = function (event) { addToCart('kralle') };
  handlers["raeucherhaken-kralle-click-003"] = function (event) { openCart() };
  handlers["raeucherhaken-kralle-click-004"] = function (event) { closeCart() };
  handlers["raeucherhaken-kralle-click-005"] = function (event) { closeCart() };
  handlers["raeucherhaken-kralle-click-006"] = function (event) { checkout() };
  handlers["raeucherhaken-kralle-click-007"] = function (event) { closeZoom() };
})();
