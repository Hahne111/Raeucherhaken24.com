(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["index-click-001"] = function (event) { addToCart('std') };
  handlers["index-click-002"] = function (event) { addToCart('ultra') };
  handlers["index-click-003"] = function (event) { addToCart('filet') };
  handlers["index-click-004"] = function (event) { addToCart('lauge-forelle-0') };
  handlers["index-click-005"] = function (event) { addToCart('mehl-buche') };
  handlers["index-click-006"] = function (event) { addToCart('kralle') };
  handlers["index-click-007"] = function (event) { addSmokyBundle() };
  handlers["index-click-008"] = function (event) { openCart() };
  handlers["index-click-009"] = function (event) { closeCart() };
  handlers["index-click-010"] = function (event) { closeCart() };
  handlers["index-click-011"] = function (event) { checkout() };
  handlers["index-click-012"] = function (event) { closeZoom() };
})();
