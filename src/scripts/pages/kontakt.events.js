(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["kontakt-click-001"] = function (event) { openCart() };
  handlers["kontakt-click-002"] = function (event) { closeCart() };
  handlers["kontakt-click-003"] = function (event) { closeCart() };
  handlers["kontakt-click-004"] = function (event) { checkout() };
  handlers["kontakt-click-005"] = function (event) { closeZoom() };
})();
