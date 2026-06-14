/* ============================================================
   custom.js — Virtual Visiting Card
   Shared front-end utilities
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    /* ── Floating labels (CSS-only) ──────────────────────────
       Bootstrap's :placeholder-shown selector requires a
       placeholder attribute to be present (even if just a
       space). This makes our .form-outline labels float
       correctly on load for pre-filled values, with no
       dependency on MDB's JS Input component. */
    document.querySelectorAll('.form-outline > .form-control, .form-outline > textarea.form-control').forEach(el => {
        if (!el.hasAttribute('placeholder')) {
            el.setAttribute('placeholder', ' ');
        }
    });

    /* ── Auto-dismiss alerts after 5s ───────────────────────── */
    document.querySelectorAll('.alert.alert-success, .alert.alert-info').forEach(alert => {
        setTimeout(() => {
            const bsAlert = typeof bootstrap !== 'undefined'
                ? new bootstrap.Alert(alert) : null;
            if (bsAlert) bsAlert.close();
            else alert.style.transition = 'opacity .4s';
               alert.style.opacity = '0';
               setTimeout(() => alert.remove(), 400);
        }, 5000);
    });

    /* ── Avatar image preview ────────────────────────────────── */
    document.querySelectorAll('input[type="file"][name="profile_image"]').forEach(input => {
        input.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const preview = document.getElementById('avatarPreview')
                         || document.getElementById('editAvatarPreview');
            if (!preview) return;

            if (!file.type.startsWith('image/')) {
                alert('Please select an image file (JPG, PNG or GIF).');
                this.value = '';
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                alert('File is too large. Maximum size is 2 MB.');
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = e => preview.src = e.target.result;
            reader.readAsDataURL(file);
        });
    });

    /* ── Delete / dangerous action confirmation ──────────────── */
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            const msg = this.dataset.confirm || 'Are you sure? This action cannot be undone.';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    /* ── Copy-to-clipboard buttons ───────────────────────────── */
    document.querySelectorAll('[data-copy-target]').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.copyTarget);
            if (!target) return;
            const text = target.value || target.textContent;
            navigator.clipboard?.writeText(text).then(() => {
                const orig = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => btn.innerHTML = orig, 2000);
            }).catch(() => {
                if (target.select) { target.select(); document.execCommand('copy'); }
            });
        });
    });

    /* ── Slug generator (create page form) ───────────────────── */
    const titleInput = document.getElementById('title');
    const slugInput  = document.getElementById('slug');

    if (titleInput && slugInput && !slugInput.dataset.manual) {
        titleInput.addEventListener('input', () => {
            if (slugInput.dataset.manual === '1') return;
            slugInput.value = titleInput.value
                .toLowerCase().trim()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/[\s]+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
            slugInput.dispatchEvent(new Event('input'));
        });

        slugInput.addEventListener('input', () => {
            slugInput.dataset.manual = '1';
        });
    }

    /* ── Password strength indicator ────────────────────────── */
    const pwInput   = document.getElementById('password');
    const strengthEl = document.getElementById('passwordStrength');

    if (pwInput && strengthEl) {
        pwInput.addEventListener('input', () => {
            const val = pwInput.value;
            let score = 0;
            if (val.length >= 8)                    score++;
            if (/[A-Z]/.test(val))                  score++;
            if (/[0-9]/.test(val))                  score++;
            if (/[^A-Za-z0-9]/.test(val))           score++;

            const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
            const colors = ['', 'danger', 'warning', 'info', 'success'];

            strengthEl.innerHTML = val.length > 0
                ? `<div class="progress mt-1" style="height:4px">
                       <div class="progress-bar bg-${colors[score]}" style="width:${score * 25}%"></div>
                   </div>
                   <small class="text-${colors[score]}">${labels[score]}</small>`
                : '';
        });
    }
});
