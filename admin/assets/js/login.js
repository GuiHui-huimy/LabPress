/* ==================================================================
   LabPress — Admin login behaviour
   - live clock in the top bar
   - password visibility toggle
   - Caps Lock detection
   - inline validation
   - submit loading state (native POST, no fake auth)
   ================================================================== */
(function () {
    'use strict';

    var form = document.getElementById('loginForm');
    var usernameInput = document.getElementById('username');
    var passwordInput = document.getElementById('password');
    var submitBtn = document.getElementById('submitBtn');
    var btnLabel = submitBtn ? submitBtn.querySelector('.btn-label') : null;
    var toggleBtn = document.getElementById('togglePw');
    var capsNote = document.getElementById('capsNote');
    var formWrap = document.getElementById('formWrap');
    var clockEl = document.getElementById('clock');

    /* ---------------- live clock ---------------- */
    function tickClock() {
        if (!clockEl) return;
        var d = new Date();
        clockEl.textContent = [d.getHours(), d.getMinutes(), d.getSeconds()]
            .map(function (n) { return String(n).padStart(2, '0'); })
            .join(':');
    }
    tickClock();
    window.setInterval(tickClock, 1000);

    /* ---------------- validation helpers ---------------- */
    function fieldOf(input) { return input.closest('.field'); }

    function setError(input, message) {
        var field = fieldOf(input);
        if (!field) return;
        var msgEl = field.querySelector('.field-error');
        if (message) {
            field.classList.add('has-error');
            if (msgEl) msgEl.textContent = message;
            input.setAttribute('aria-invalid', 'true');
        } else {
            field.classList.remove('has-error');
            if (msgEl) msgEl.textContent = '';
            input.removeAttribute('aria-invalid');
        }
    }

    function validateUsername() {
        if (!usernameInput.value.trim()) {
            setError(usernameInput, '请输入用户名。');
            return false;
        }
        setError(usernameInput, null);
        return true;
    }

    function validatePassword() {
        if (!passwordInput.value) {
            setError(passwordInput, '请输入密码。');
            return false;
        }
        setError(passwordInput, null);
        return true;
    }

    function shakeCard() {
        if (!formWrap) return;
        formWrap.classList.remove('shake');
        void formWrap.offsetWidth; /* restart animation */
        formWrap.classList.add('shake');
    }

    /* ---------------- live validation ---------------- */
    usernameInput.addEventListener('blur', function () {
        if (usernameInput.value.trim()) validateUsername();
    });
    passwordInput.addEventListener('blur', function () {
        if (passwordInput.value) validatePassword();
        if (capsNote) capsNote.hidden = true;
    });
    usernameInput.addEventListener('input', function () {
        if (fieldOf(usernameInput) && fieldOf(usernameInput).classList.contains('has-error')) validateUsername();
    });
    passwordInput.addEventListener('input', function () {
        if (fieldOf(passwordInput) && fieldOf(passwordInput).classList.contains('has-error')) validatePassword();
    });

    /* ---------------- Caps Lock ---------------- */
    function syncCaps(e) {
        if (!capsNote || typeof e.getModifierState !== 'function') return;
        capsNote.hidden = !e.getModifierState('CapsLock');
    }
    passwordInput.addEventListener('keydown', syncCaps);
    passwordInput.addEventListener('keyup', syncCaps);

    /* ---------------- password toggle ---------------- */
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            var isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            toggleBtn.textContent = isHidden ? 'HIDE' : 'SHOW';
            toggleBtn.setAttribute('aria-pressed', String(isHidden));
            toggleBtn.setAttribute('aria-label', isHidden ? '隐藏密码' : '显示密码');
        });
    }

    /* ---------------- submit ---------------- */
    form.addEventListener('submit', function (e) {
        var okUser = validateUsername();
        var okPass = validatePassword();

        if (!okUser || !okPass) {
            e.preventDefault();
            shakeCard();
            (okUser ? passwordInput : usernameInput).focus();
            return;
        }

        /* let the native POST continue; just show a busy state */
        submitBtn.disabled = true;
        submitBtn.classList.add('loading');
        if (btnLabel) btnLabel.textContent = '验证中';
    });

    /* ---------------- server-side error state ---------------- */
    if (document.body.getAttribute('data-auth-error') === '1') {
        setError(passwordInput, passwordInput.closest('.field').getAttribute('data-server-error') || '用户名或密码错误。');
        shakeCard();
        window.setTimeout(function () { passwordInput.focus(); }, 120);
    }

    /* ---------------- autofocus ---------------- */
    if (window.matchMedia('(min-width: 961px)').matches) {
        window.setTimeout(function () { usernameInput.focus(); }, 350);
    }
})();
