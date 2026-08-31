(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["raeucherhaken-ultra-click-001"] = function (event) { zoom('assets/ultra-original-korrekt.png','Räucherhaken Ultra') };
  handlers["raeucherhaken-ultra-click-002"] = function (event) { openCart() };
  handlers["raeucherhaken-ultra-click-003"] = function (event) { closeCart() };
  handlers["raeucherhaken-ultra-click-004"] = function (event) { closeCart() };
  handlers["raeucherhaken-ultra-click-005"] = function (event) { checkout() };
  handlers["raeucherhaken-ultra-click-006"] = function (event) { closeZoom() };
})();
