(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["marktplatz-regeln-click-001"] = function (event) { openCart() };
  handlers["marktplatz-regeln-click-002"] = function (event) { closeCart() };
  handlers["marktplatz-regeln-click-003"] = function (event) { closeCart() };
  handlers["marktplatz-regeln-click-004"] = function (event) { checkout() };
  handlers["marktplatz-regeln-click-005"] = function (event) { closeZoom() };
})();
