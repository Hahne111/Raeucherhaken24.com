(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["sonderanfertigung-prototyp-click-001"] = function (event) { openCart() };
  handlers["sonderanfertigung-prototyp-click-002"] = function (event) { closeCart() };
  handlers["sonderanfertigung-prototyp-click-003"] = function (event) { closeCart() };
  handlers["sonderanfertigung-prototyp-click-004"] = function (event) { checkout() };
  handlers["sonderanfertigung-prototyp-click-005"] = function (event) { closeZoom() };
})();
