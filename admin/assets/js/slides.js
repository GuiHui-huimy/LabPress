// 轮播图管理脚本

function showSlideForm() {
    document.getElementById('slideModal').style.display = 'block';
    document.getElementById('slideModalTitle').innerText = window.__SLIDES_LANG.add_title;
    const form = document.getElementById('slideForm');
    form.reset();
    form.id.value = '';
    document.getElementById('imagePreview').innerHTML = '';
}

function closeSlideModal() {
    document.getElementById('slideModal').style.display = 'none';
}

function editSlide(slide) {
    showSlideForm();
    document.getElementById('slideModalTitle').innerText = window.__SLIDES_LANG.edit_title;
    const form = document.getElementById('slideForm');
    form.id.value = slide.id || '';
    form.title.value = slide.title;
    form.description.value = slide.description;
    form.image.value = slide.image;
    form.sort_order.value = slide.sort_order || 0;
    if (slide.image) {
        document.getElementById('imagePreview').innerHTML = `<img src="${slide.image}" style="max-width:200px;">`;
    }
}

async function deleteSlide(id) {
    if (!confirm(window.__SLIDES_LANG.delete_confirm)) return;
    const res = await fetch('../api/save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type: 'slides', action: 'delete', data: { id } })
    });
    const result = await res.json();
    if (result.success) location.reload();
    else alert(window.__SLIDES_LANG.error + ': ' + (result.error || ''));
}

document.addEventListener('DOMContentLoaded', function () {
    const imageInput = document.getElementById('slideImageInput');
    if (imageInput) {
        imageInput.addEventListener('change', async function (e) {
            const file = e.target.files[0];
            if (!file) return;
            const L = window.__SLIDES_LANG;
            const formData = new FormData();
            formData.append('image', file);
            formData.append('type', 'slides');
            try {
                const res = await fetch('../api/upload.php', { method: 'POST', body: formData });
                const result = await res.json();
                if (result.success) {
                    document.getElementById('slideForm').image.value = result.url;
                    document.getElementById('imagePreview').innerHTML = `<img src="${result.url}" style="max-width:200px;">`;
                } else {
                    alert(L.upload_fail + ': ' + (result.error || ''));
                }
            } catch (e) {
                alert(L.upload_fail);
            }
        });
    }

    const slideForm = document.getElementById('slideForm');
    if (slideForm) {
        slideForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const L = window.__SLIDES_LANG;
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            data.sort_order = parseInt(data.sort_order) || 0;
            try {
                const res = await fetch('../api/save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type: 'slides', data })
                });
                const result = await res.json();
                if (result.success) location.reload();
                else alert(L.save_fail + ': ' + (result.error || ''));
            } catch (e) {
                alert(L.save_fail);
            }
        });
    }
});