(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["impressum-click-001"] = function (event) { openCart() };
  handlers["impressum-click-002"] = function (event) { closeCart() };
  handlers["impressum-click-003"] = function (event) { closeCart() };
  handlers["impressum-click-004"] = function (event) { checkout() };
  handlers["impressum-click-005"] = function (event) { closeZoom() };
})();
