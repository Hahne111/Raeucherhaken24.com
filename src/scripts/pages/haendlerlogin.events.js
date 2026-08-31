(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["haendlerlogin-click-001"] = function (event) { showAccountTab('login',this) };
  handlers["haendlerlogin-click-002"] = function (event) { showAccountTab('register',this) };
  handlers["haendlerlogin-click-003"] = function (event) { localLogin(true) };
  handlers["haendlerlogin-click-004"] = function (event) { toast('Bitte wenden Sie sich an service@raeucherhaken24.com') };
  handlers["haendlerlogin-click-005"] = function (event) { localRegister(true) };
  handlers["haendlerlogin-click-006"] = function (event) { openCart() };
  handlers["haendlerlogin-click-007"] = function (event) { closeCart() };
  handlers["haendlerlogin-click-008"] = function (event) { closeCart() };
  handlers["haendlerlogin-click-009"] = function (event) { checkout() };
  handlers["haendlerlogin-click-010"] = function (event) { closeZoom() };
})();
