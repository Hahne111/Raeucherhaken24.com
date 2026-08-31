
(function(){
  // Der Klartext des Vorschau-Passworts gehört nicht in ein öffentliches Repository.
  // Diese clientseitige Sperre ist nur ein Vorschauhinweis, kein echter Zugriffsschutz.
  var PASSWORD_SHA256 = "fb338e53da57ec89c638f24e8f87697a70d0c0d8e4cc3a56f754211d2c6f0444";
  var STORAGE_KEY = "rh24_preview_unlocked";

  async function sha256(value){
    var bytes = new TextEncoder().encode(value);
    var digest = await crypto.subtle.digest("SHA-256", bytes);
    return Array.from(new Uint8Array(digest), function(byte){
      return byte.toString(16).padStart(2, "0");
    }).join("");
  }

  function unlock(gate){
    try { sessionStorage.setItem(STORAGE_KEY, "1"); } catch(e) {}
    if(gate && gate.parentNode) gate.parentNode.removeChild(gate);
    document.documentElement.classList.remove("rh24-locked");
    document.body.classList.remove("rh24-locked");
  }

  function createGate(){
    var unlocked = false;
    try { unlocked = sessionStorage.getItem(STORAGE_KEY) === "1"; } catch(e) {}
    if(unlocked) return;

    document.documentElement.classList.add("rh24-locked");
    document.body.classList.add("rh24-locked");

    var gate = document.createElement("div");
    gate.id = "rh24PasswordGate";
    gate.innerHTML =
      '<div class="rh24PasswordCard">' +
        '<div class="rh24PasswordLogo">RÄUCHERHAKEN<span>24</span></div>' +
        '<h1>Geschützter Testzugang</h1>' +
        '<p>Bitte Passwort eingeben, um die Shop-Vorschau zu öffnen.</p>' +
        '<form id="rh24PasswordForm">' +
          '<div class="rh24PasswordRow">' +
            '<input id="rh24PasswordInput" type="password" placeholder="Passwort" autocomplete="current-password" autofocus>' +
            '<button id="rh24PasswordToggle" type="button" aria-label="Passwort anzeigen">👁</button>' +
          '</div>' +
          '<button id="rh24PasswordSubmit" type="submit">Shop öffnen</button>' +
        '</form>' +
        '<div id="rh24PasswordError" class="rh24PasswordError"></div>' +
      '</div>';

    document.body.appendChild(gate);

    var form = document.getElementById("rh24PasswordForm");
    var input = document.getElementById("rh24PasswordInput");
    var toggle = document.getElementById("rh24PasswordToggle");
    var submit = document.getElementById("rh24PasswordSubmit");
    var error = document.getElementById("rh24PasswordError");

    async function checkPassword(){
      var value = (input.value || "").trim();
      if(submit.disabled) return;
      submit.disabled = true;
      submit.textContent = "Wird geprüft …";
      error.textContent = "";
      var matches = false;
      try {
        matches = await sha256(value) === PASSWORD_SHA256;
      } catch(e) {
        error.textContent = "Passwortprüfung wird von diesem Browser nicht unterstützt.";
      }
      if(matches){
        submit.textContent = "Wird geöffnet …";
        unlock(gate);
      } else {
        if(!error.textContent) error.textContent = "Passwort nicht korrekt.";
        submit.disabled = false;
        submit.textContent = "Shop öffnen";
        input.focus();
        input.select();
      }
    }

    form.addEventListener("submit", function(ev){
      ev.preventDefault();
      ev.stopPropagation();
      checkPassword();
      return false;
    });

    submit.addEventListener("click", function(ev){
      ev.preventDefault();
      ev.stopPropagation();
      checkPassword();
    });

    toggle.addEventListener("click", function(){
      input.type = input.type === "password" ? "text" : "password";
      toggle.textContent = input.type === "password" ? "👁" : "🙈";
      input.focus();
    });

    input.addEventListener("keydown", function(ev){
      if(ev.key === "Enter"){
        ev.preventDefault();
        checkPassword();
      }
    });

    setTimeout(function(){ input.focus(); }, 50);
  }

  if(document.readyState === "loading"){
    document.addEventListener("DOMContentLoaded", createGate, {once:true});
  } else {
    createGate();
  }
})();
