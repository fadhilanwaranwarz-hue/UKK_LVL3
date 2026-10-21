/**
 * ETALASE DIGITAL - SMK TELKOM LAMPUNG
 * Frontend Interactions & Utility Scripts
 * Ahmad Fadhil Anwar (Kelas XI RPL)
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Mobile Menu Toggle
  const menuToggle = document.getElementById('menuToggle');
  const navMenu = document.getElementById('navMenu');
  if (menuToggle && navMenu) {
    menuToggle.addEventListener('click', () => {
      navMenu.classList.toggle('open');
      const icon = menuToggle.querySelector('i');
      if (icon) {
        icon.classList.toggle('fa-bars');
        icon.classList.toggle('fa-xmark');
      }
    });
  }

  // 2. Live Image Upload Preview
  const imageInput = document.getElementById('gambarInput');
  const previewImg = document.getElementById('imagePreview');
  if (imageInput && previewImg) {
    imageInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        if (!file.type.startsWith('image/')) {
          alert('Harap pilih file bertipe gambar (JPG, PNG, WEBP, atau SVG).');
          imageInput.value = '';
          return;
        }
        const reader = new FileReader();
        reader.onload = (event) => {
          previewImg.src = event.target.result;
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // 3. Client-side Realtime Card Filter (for index.php & dashboard)
  const clientSearch = document.getElementById('clientSearchInput');
  const projectCards = document.querySelectorAll('.project-card');
  if (clientSearch && projectCards.length > 0) {
    clientSearch.addEventListener('input', (e) => {
      const term = e.target.value.toLowerCase().trim();
      projectCards.forEach((card) => {
        const title = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
        const author = card.querySelector('.author-chip')?.textContent.toLowerCase() || '';
        const desc = card.querySelector('.card-desc')?.textContent.toLowerCase() || '';

        if (title.includes(term) || author.includes(term) || desc.includes(term)) {
          card.style.display = 'flex';
        } else {
          card.style.display = 'none';
        }
      });
    });
  }

  // 4. Autofill Demo Account (on login.php)
  const demoButtons = document.querySelectorAll('[data-demo-email]');
  const emailInput = document.getElementById('loginEmail');
  const passInput = document.getElementById('loginPassword');

  demoButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const email = btn.getAttribute('data-demo-email');
      const pass = btn.getAttribute('data-demo-pass') || 'password123';
      if (emailInput && passInput) {
        emailInput.value = email;
        passInput.value = pass;
        emailInput.focus();
        
        // Highlight feedback
        emailInput.style.borderColor = 'var(--color-primary)';
        passInput.style.borderColor = 'var(--color-primary)';
        setTimeout(() => {
          emailInput.style.borderColor = '';
          passInput.style.borderColor = '';
        }, 1000);
      }
    });
  });

  // 5. Delete Confirm Dialog
  const deleteLinks = document.querySelectorAll('.btn-confirm-delete');
  deleteLinks.forEach((link) => {
    link.addEventListener('click', (e) => {
      const itemTitle = link.getAttribute('data-item-title') || 'item ini';
      if (!confirm(`Apakah Anda yakin ingin menghapus "${itemTitle}"? Data yang dihapus tidak dapat dipulihkan.`)) {
        e.preventDefault();
      }
    });
  });
});
