(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["thermometer-click-001"] = function (event) { openCart() };
  handlers["thermometer-click-002"] = function (event) { closeCart() };
  handlers["thermometer-click-003"] = function (event) { closeCart() };
  handlers["thermometer-click-004"] = function (event) { checkout() };
  handlers["thermometer-click-005"] = function (event) { closeZoom() };
})();
