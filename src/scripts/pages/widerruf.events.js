(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["widerruf-click-001"] = function (event) { openCart() };
  handlers["widerruf-click-002"] = function (event) { closeCart() };
  handlers["widerruf-click-003"] = function (event) { closeCart() };
  handlers["widerruf-click-004"] = function (event) { checkout() };
  handlers["widerruf-click-005"] = function (event) { closeZoom() };
  handlers["widerruf-submit-006"] = function (event) { return submitRevocation(event) };
})();
