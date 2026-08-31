(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["404-click-001"] = function (event) { openCart() };
  handlers["404-click-002"] = function (event) { closeCart() };
  handlers["404-click-003"] = function (event) { closeCart() };
  handlers["404-click-004"] = function (event) { checkout() };
  handlers["404-click-005"] = function (event) { closeZoom() };
})();
