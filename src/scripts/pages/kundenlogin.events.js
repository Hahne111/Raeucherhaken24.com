(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["kundenlogin-click-001"] = function (event) { showAccountTab('login',this) };
  handlers["kundenlogin-click-002"] = function (event) { showAccountTab('register',this) };
  handlers["kundenlogin-click-003"] = function (event) { showAccountTab('orderstatus',this) };
  handlers["kundenlogin-click-004"] = function (event) { localLogin(false) };
  handlers["kundenlogin-click-005"] = function (event) { toast('Bitte wenden Sie sich an service@raeucherhaken24.com') };
  handlers["kundenlogin-click-006"] = function (event) { localRegister(false) };
  handlers["kundenlogin-click-007"] = function (event) { openCart() };
  handlers["kundenlogin-click-008"] = function (event) { closeCart() };
  handlers["kundenlogin-click-009"] = function (event) { closeCart() };
  handlers["kundenlogin-click-010"] = function (event) { checkout() };
  handlers["kundenlogin-click-011"] = function (event) { closeZoom() };
})();
