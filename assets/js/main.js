/**
 * ScutS Dashboard Interactive Logic
 */
document.addEventListener('DOMContentLoaded', () => {
  // Mobile Sidebar Toggle
  const sidebar = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');
  const menuToggleBtn = document.getElementById('menuToggleBtn');
  const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');

  function openSidebar() {
    sidebar.classList.add('open');
    sidebarOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar.classList.remove('open');
    sidebarOverlay.classList.remove('active');
    document.body.style.overflow = '';
  }

  if (menuToggleBtn) {
    menuToggleBtn.addEventListener('click', openSidebar);
  }

  if (sidebarCloseBtn) {
    sidebarCloseBtn.addEventListener('click', closeSidebar);
  }

  if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', closeSidebar);
  }

  // Profile Dropdown Toggle
  // Centrally controlled by components/navbar.php to ensure universal support on all current and future pages.
  // Fallback binding only if not already bound by navbar.php component:
  const userProfileMenu = document.getElementById('userProfileMenu');
  const userProfileBtn = document.getElementById('userProfileBtn');

  if (userProfileBtn && userProfileMenu && userProfileBtn.dataset.bound !== 'true') {
    userProfileBtn.dataset.bound = 'true';
    userProfileBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      if (typeof e.stopImmediatePropagation === 'function') {
        e.stopImmediatePropagation();
      }
      const isOpen = userProfileMenu.classList.toggle('open');
      userProfileBtn.setAttribute('aria-expanded', isOpen);
    });

    document.addEventListener('click', (e) => {
      if (userProfileMenu.classList.contains('open') && !userProfileMenu.contains(e.target)) {
        userProfileMenu.classList.remove('open');
        userProfileBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // Analytics Filter Chips
  const chipButtons = document.querySelectorAll('.filter-chips-wrap .chip-btn');
  chipButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      chipButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    });
  });

  // Close on Escape Key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeSidebar();
      if (userProfileMenu) {
        userProfileMenu.classList.remove('open');
        if (userProfileBtn) userProfileBtn.setAttribute('aria-expanded', 'false');
      }
    }
  });
});
