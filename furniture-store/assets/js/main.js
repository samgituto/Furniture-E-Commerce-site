/**
 * assets/js/main.js
 * -----------------------------------------------------------------
 * Client-side interactivity: AJAX add-to-cart with toast feedback,
 * password visibility toggles, image upload previews, delete
 * confirmations, and the mobile admin-sidebar toggle. All critical
 * validation still happens server-side (see PHP files) — this is
 * strictly for UX.
 * -----------------------------------------------------------------
 */
document.addEventListener('DOMContentLoaded', function () {

  /* ---------------- Toast notifications ---------------- */
  function showToast(message, type) {
    const containerId = 'toastContainer';
    let container = document.getElementById(containerId);
    if (!container) {
      container = document.createElement('div');
      container.id = containerId;
      container.style.position = 'fixed';
      container.style.top = '20px';
      container.style.right = '20px';
      container.style.zIndex = '2000';
      document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = 'alert alert-' + (type === 'error' ? 'danger' : 'success') + ' shadow-sm';
    toast.style.minWidth = '220px';
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
  }

  /* ---------------- AJAX add-to-cart forms ---------------- */
  document.querySelectorAll('.add-to-cart-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const formData = new FormData(form);
      fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          showToast(data.message, data.success ? 'success' : 'error');
          const badge = document.getElementById('cartCount');
          if (badge && typeof data.cart_count !== 'undefined') {
            badge.textContent = data.cart_count;
          }
        })
        .catch(() => showToast('Something went wrong. Please try again.', 'error'));
    });
  });

  /* ---------------- Delete confirmation (forms) ---------------- */
  document.querySelectorAll('.delete-confirm-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!confirm('Are you sure you want to delete this item? This cannot be undone.')) {
        e.preventDefault();
      }
    });
  });

  /* ---------------- Delete confirmation (links) ---------------- */
  document.querySelectorAll('.delete-confirm-link').forEach(function (link) {
    link.addEventListener('click', function (e) {
      if (!confirm('Are you sure you want to delete this record? This cannot be undone.')) {
        e.preventDefault();
      }
    });
  });

  /* ---------------- Password visibility toggle ---------------- */
  document.querySelectorAll('.toggle-password').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const targetId = btn.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (!input) return;
      const icon = btn.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
      }
    });
  });

  /* ---------------- Image upload preview ---------------- */
  const imageInput = document.getElementById('imageInput');
  if (imageInput) {
    imageInput.addEventListener('change', function () {
      const preview = document.getElementById('imagePreview');
      const existing = document.getElementById('imagePreviewExisting');
      if (imageInput.files && imageInput.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
          preview.src = e.target.result;
          preview.classList.remove('d-none');
          if (existing) existing.classList.add('d-none');
        };
        reader.readAsDataURL(imageInput.files[0]);
      }
    });
  }

  /* ---------------- Admin sidebar toggle (mobile) ---------------- */
  const sidebarToggle = document.getElementById('sidebarToggle');
  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function () {
      document.querySelector('.admin-sidebar').classList.toggle('show');
    });
  }

  /* ---------------- Simple client-side form validation ---------------- */
  document.querySelectorAll('.needs-validation').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }
      form.classList.add('was-validated');
    });
  });

});
