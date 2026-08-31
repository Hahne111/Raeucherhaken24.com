(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["raeucherhaken-standard-aal-click-001"] = function (event) { zoom('assets/standard-aal-weiss.png','Räucherhaken Standard Aal') };
  handlers["raeucherhaken-standard-aal-click-002"] = function (event) { addToCart('aal') };
  handlers["raeucherhaken-standard-aal-click-003"] = function (event) { openCart() };
  handlers["raeucherhaken-standard-aal-click-004"] = function (event) { closeCart() };
  handlers["raeucherhaken-standard-aal-click-005"] = function (event) { closeCart() };
  handlers["raeucherhaken-standard-aal-click-006"] = function (event) { checkout() };
  handlers["raeucherhaken-standard-aal-click-007"] = function (event) { closeZoom() };
})();
