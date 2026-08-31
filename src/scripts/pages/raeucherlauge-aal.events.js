(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["raeucherlauge-aal-click-001"] = function (event) { addLaugeToCart('lauge-aal-0','Räucherlauge Aal',4.95,'assets/lauge-aal_standard.png') };
  handlers["raeucherlauge-aal-click-002"] = function (event) { addLaugeToCart('lauge-aal-1','Räucherlauge Aal Pfeffer',4.95,'assets/lauge-aal_pfeffer.png') };
  handlers["raeucherlauge-aal-click-003"] = function (event) { addLaugeToCart('lauge-aal-2','Räucherlauge Aal Delikat',4.95,'assets/lauge-aal_delikat.png') };
  handlers["raeucherlauge-aal-click-004"] = function (event) { openCart() };
  handlers["raeucherlauge-aal-click-005"] = function (event) { closeCart() };
  handlers["raeucherlauge-aal-click-006"] = function (event) { closeCart() };
  handlers["raeucherlauge-aal-click-007"] = function (event) { checkout() };
  handlers["raeucherlauge-aal-click-008"] = function (event) { closeZoom() };
})();
