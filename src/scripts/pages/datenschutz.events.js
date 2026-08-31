(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["datenschutz-click-001"] = function (event) { openCart() };
  handlers["datenschutz-click-002"] = function (event) { closeCart() };
  handlers["datenschutz-click-003"] = function (event) { closeCart() };
  handlers["datenschutz-click-004"] = function (event) { checkout() };
  handlers["datenschutz-click-005"] = function (event) { closeZoom() };
})();
