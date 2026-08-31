(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["zahlung-versand-click-001"] = function (event) { openCart() };
  handlers["zahlung-versand-click-002"] = function (event) { closeCart() };
  handlers["zahlung-versand-click-003"] = function (event) { closeCart() };
  handlers["zahlung-versand-click-004"] = function (event) { checkout() };
  handlers["zahlung-versand-click-005"] = function (event) { closeZoom() };
})();
