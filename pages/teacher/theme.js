// Theme handling
document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    
    // Check for saved theme preference or use system preference
    const savedTheme = localStorage.getItem('theme') || 
        (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    
    // Apply the theme
    applyTheme(savedTheme);
    
    // Toggle theme when button is clicked
    themeToggle.addEventListener('click', () => {
        const currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        applyTheme(newTheme);
        localStorage.setItem('theme', newTheme);
    });
});

function applyTheme(theme) {
    const themeIcon = document.getElementById('theme-icon');
    
    if (theme === 'dark') {
        document.documentElement.classList.add('dark');
        themeIcon.classList.remove('fa-sun');
        themeIcon.classList.add('fa-moon');
    } else {
        document.documentElement.classList.remove('dark');
        themeIcon.classList.remove('fa-moon');
        themeIcon.classList.add('fa-sun');
    }
} 