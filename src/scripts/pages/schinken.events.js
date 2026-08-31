(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["schinken-click-001"] = function (event) { openCart() };
  handlers["schinken-click-002"] = function (event) { closeCart() };
  handlers["schinken-click-003"] = function (event) { closeCart() };
  handlers["schinken-click-004"] = function (event) { checkout() };
  handlers["schinken-click-005"] = function (event) { closeZoom() };
})();
