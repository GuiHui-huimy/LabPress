document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('install-form');
    const btn = document.getElementById('install-btn');
    const progressDiv = document.getElementById('install-progress');
    const progressBar = document.getElementById('progress-bar');
    const progressStatus = document.getElementById('progress-status');
    const resultDiv = document.getElementById('install-result');
    const L = window.__INSTALL_LANG;

    function updateProgress(percent, statusText) {
        progressBar.style.width = percent + '%';
        progressBar.setAttribute('aria-valuenow', percent);
        progressBar.textContent = percent + '%';
        progressStatus.textContent = statusText;
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const fileInput = form.elements['zip'];
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            resultDiv.innerHTML = '<div class="alert alert-danger">' + L.select_file + '</div>';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> ' + L.installing;
        progressDiv.style.display = 'block';
        updateProgress(10, L.uploading);
        resultDiv.innerHTML = '';

        const formData = new FormData();
        formData.append('zip', fileInput.files[0]);

        try {
            let progress = 10;
            const timer = setInterval(function () {
                if (progress < 80) {
                    progress += 10;
                    updateProgress(progress, L.installing_progress);
                }
            }, 300);

            const res = await fetch('../api/plugin-install.php', {
                method: 'POST',
                body: formData
            });
            clearInterval(timer);
            updateProgress(100, L.install_complete);

            const rawText = await res.text();
            let result;
            try {
                result = JSON.parse(rawText);
            } catch (jsonErr) {
                resultDiv.innerHTML = '<div class="alert alert-danger">' + rawText.replace(/</g, '&lt;') + '</div>';
                return;
            }

            if (result.success) {
                const plugin = result.plugin_info || {};
                resultDiv.innerHTML = `
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-circle"></i> ${L.success_title}</h5>
                        <hr>
                        <p><strong>${L.name_label}</strong> ${plugin.name || L.unknown}</p>
                        <p><strong>${L.desc_label}</strong> ${plugin.description || L.none}</p>
                        <p><strong>${L.version_label}</strong> ${plugin.version || L.none}</p>
                        <p><strong>${L.author_label}</strong> ${plugin.author || L.unknown}</p>
                        <a href="?view=plugins" class="btn btn-sm btn-primary mt-2">${L.manage_btn}</a>
                    </div>`;
                form.reset();
            } else {
                resultDiv.innerHTML = '<div class="alert alert-danger">' + (result.error || L.install_fail) + '</div>';
            }
        } catch (e) {
            resultDiv.innerHTML = '<div class="alert alert-danger">' + L.network_error + ': ' + e.message + '</div>';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload"></i> ' + L.install_btn;
            setTimeout(function () {
                progressDiv.style.display = 'none';
                updateProgress(0, '');
            }, 2000);
        }
    });

    // 卸载插件按钮
    document.querySelectorAll('.uninstall-plugin-btn').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const slug = this.dataset.slug;
            if (!confirm(L.uninstall_confirm + ' ' + slug + '?')) return;
            try {
                const res = await fetch('../api/plugin-uninstall.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ slug: slug })
                });
                const result = await res.json();
                if (result.success) {
                    alert(L.uninstall_success);
                    location.reload();
                } else {
                    alert(L.uninstall_fail + ': ' + (result.error || ''));
                }
            } catch (e) {
                alert(L.network_error);
            }
        });
    });
});