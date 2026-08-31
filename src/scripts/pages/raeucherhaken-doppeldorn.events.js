(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["raeucherhaken-doppeldorn-click-001"] = function (event) { zoom('assets/doppeldorn.png','Räucherhaken Doppeldorn') };
  handlers["raeucherhaken-doppeldorn-click-002"] = function (event) { addToCart('doppel') };
  handlers["raeucherhaken-doppeldorn-click-003"] = function (event) { openCart() };
  handlers["raeucherhaken-doppeldorn-click-004"] = function (event) { closeCart() };
  handlers["raeucherhaken-doppeldorn-click-005"] = function (event) { closeCart() };
  handlers["raeucherhaken-doppeldorn-click-006"] = function (event) { checkout() };
  handlers["raeucherhaken-doppeldorn-click-007"] = function (event) { closeZoom() };
})();
