(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["marktplatz-agb-click-001"] = function (event) { openCart() };
  handlers["marktplatz-agb-click-002"] = function (event) { closeCart() };
  handlers["marktplatz-agb-click-003"] = function (event) { closeCart() };
  handlers["marktplatz-agb-click-004"] = function (event) { checkout() };
  handlers["marktplatz-agb-click-005"] = function (event) { closeZoom() };
})();
