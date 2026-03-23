// sidebar-overlay.js - Manejo del overlay del sidebar en móviles
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (!sidebar || !sidebarToggle || !sidebarOverlay) {
        return; // Si no existen los elementos, salir
    }
    
    function toggleSidebar() {
        if (window.innerWidth < 992) {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
            // Prevenir scroll del body cuando el sidebar está abierto
            if (sidebar.classList.contains('show')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
    }
    
    function closeSidebar() {
        if (window.innerWidth < 992) {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
    
    // Toggle al hacer clic en el botón
    sidebarToggle.addEventListener('click', function(e) {
        e.preventDefault();
        toggleSidebar();
    });
    
    // Cerrar al hacer clic en el overlay
    sidebarOverlay.addEventListener('click', closeSidebar);
    
    // Cerrar al hacer clic fuera del sidebar
    document.addEventListener('click', function(event) {
        if (window.innerWidth < 992) {
            if (!sidebar.contains(event.target) && 
                !sidebarToggle.contains(event.target) && 
                sidebar.classList.contains('show')) {
                closeSidebar();
            }
        }
    });
    
    // Cerrar al redimensionar la ventana si pasa a desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
            closeSidebar();
        }
    });
    
    // Cerrar con tecla ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && sidebar.classList.contains('show')) {
            closeSidebar();
        }
    });
});

