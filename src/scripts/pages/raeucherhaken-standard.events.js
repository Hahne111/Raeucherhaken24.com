(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["raeucherhaken-standard-click-001"] = function (event) { zoom('assets/standard.png','Räucherhaken Standard') };
  handlers["raeucherhaken-standard-click-002"] = function (event) { addToCart('std') };
  handlers["raeucherhaken-standard-click-003"] = function (event) { openCart() };
  handlers["raeucherhaken-standard-click-004"] = function (event) { closeCart() };
  handlers["raeucherhaken-standard-click-005"] = function (event) { closeCart() };
  handlers["raeucherhaken-standard-click-006"] = function (event) { checkout() };
  handlers["raeucherhaken-standard-click-007"] = function (event) { closeZoom() };
})();
