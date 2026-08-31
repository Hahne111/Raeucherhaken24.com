(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["raeucherfisch-click-001"] = function (event) { openCart() };
  handlers["raeucherfisch-click-002"] = function (event) { closeCart() };
  handlers["raeucherfisch-click-003"] = function (event) { closeCart() };
  handlers["raeucherfisch-click-004"] = function (event) { checkout() };
  handlers["raeucherfisch-click-005"] = function (event) { closeZoom() };
})();
