document.addEventListener('DOMContentLoaded', () => {
    const menuButton = document.getElementById('menu-button');
    const menuDropdown = document.getElementById('menu-dropdown');
    let isMenuOpen = false;

    // Toggle menu on button click
    menuButton.addEventListener('click', (e) => {
        e.stopPropagation();
        isMenuOpen = !isMenuOpen;
        menuDropdown.classList.toggle('hidden', !isMenuOpen);
    });

    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
        if (isMenuOpen && !menuDropdown.contains(e.target) && e.target !== menuButton) {
            isMenuOpen = false;
            menuDropdown.classList.add('hidden');
        }
    });

    // Prevent menu from closing when clicking inside
    menuDropdown.addEventListener('click', (e) => {
        e.stopPropagation();
    });
}); 