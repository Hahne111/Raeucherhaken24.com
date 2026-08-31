(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["agb-click-001"] = function (event) { openCart() };
  handlers["agb-click-002"] = function (event) { closeCart() };
  handlers["agb-click-003"] = function (event) { closeCart() };
  handlers["agb-click-004"] = function (event) { checkout() };
  handlers["agb-click-005"] = function (event) { closeZoom() };
})();
