(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["raeucherhaken-filet-click-001"] = function (event) { zoom('assets/filet.png','Räucherhaken Filet') };
  handlers["raeucherhaken-filet-click-002"] = function (event) { addToCart('filet') };
  handlers["raeucherhaken-filet-click-003"] = function (event) { openCart() };
  handlers["raeucherhaken-filet-click-004"] = function (event) { closeCart() };
  handlers["raeucherhaken-filet-click-005"] = function (event) { closeCart() };
  handlers["raeucherhaken-filet-click-006"] = function (event) { checkout() };
  handlers["raeucherhaken-filet-click-007"] = function (event) { closeZoom() };
})();
