(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["rezepte-anleitungen-click-001"] = function (event) { window.print() };
  handlers["rezepte-anleitungen-click-002"] = function (event) { openCart() };
  handlers["rezepte-anleitungen-click-003"] = function (event) { closeCart() };
  handlers["rezepte-anleitungen-click-004"] = function (event) { closeCart() };
  handlers["rezepte-anleitungen-click-005"] = function (event) { checkout() };
  handlers["rezepte-anleitungen-click-006"] = function (event) { closeZoom() };
})();
