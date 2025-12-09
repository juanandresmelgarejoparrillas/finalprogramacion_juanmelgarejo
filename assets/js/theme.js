/* assets/js/theme.js */
// Lógica para el Modo Oscuro / Claro

// Función principal para cambiar el tema
function toggleTheme() {
    const body = document.body;
    const currentTheme = body.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

    // Aplicamos el nuevo tema al body
    body.setAttribute('data-theme', newTheme);

    // Guardamos la preferencia en el navegador para el futuro
    localStorage.setItem('theme', newTheme);

    // Actualizamos el icono del botón
    updateThemeIcon(newTheme);
}

// Función para actualizar el icono del sol/luna
function updateThemeIcon(theme) {
    const icon = document.getElementById('theme-icon');
    if (icon) {
        if (theme === 'dark') {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun'); // En modo oscuro, mostramos el sol para ir a claro
        } else {
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon'); // En modo claro, mostramos la luna para ir a oscuro
        }
    }
}

// Al cargar la página, verificamos la preferencia guardada
document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('theme') || 'light';
    updateThemeIcon(savedTheme); // Aseguramos que el icono coincida
});
