// sidebar-pin.js - Sistema para fijar/desfijar el sidebar
(function() {
    'use strict';
    
    const STORAGE_KEY = 'sgv_sidebar_pinned';
    const SIDEBAR_ID = 'sidebar';
    const PIN_BUTTON_ID = 'sidebarPinBtn';
    
    // Estado inicial: leído de localStorage o true por defecto (fijado)
    let isPinned = localStorage.getItem(STORAGE_KEY) !== 'false';
    
    function initSidebarPin() {
        const sidebar = document.getElementById(SIDEBAR_ID);
        if (!sidebar) return;
        
        // Crear botón de pin si no existe
        let pinButton = document.getElementById(PIN_BUTTON_ID);
        if (!pinButton) {
            pinButton = createPinButton();
            const sidebarHeader = sidebar.querySelector('.p-3');
            if (sidebarHeader) {
                // Buscar el título h5
                const title = sidebarHeader.querySelector('h5');
                if (title) {
                    // Crear contenedor para título y botón
                    const titleWrapper = document.createElement('div');
                    titleWrapper.className = 'd-flex justify-content-between align-items-center mb-4';
                    titleWrapper.style.cssText = 'position: relative;';
                    
                    // Clonar el título dentro del wrapper
                    const titleClone = title.cloneNode(true);
                    titleClone.className = 'mb-0';
                    titleWrapper.appendChild(titleClone);
                    
                    // Agregar botón de pin
                    titleWrapper.appendChild(pinButton);
                    
                    // Reemplazar el título original con el wrapper
                    title.replaceWith(titleWrapper);
                } else {
                    // Si no hay título, agregar al inicio con un contenedor
                    const pinContainer = document.createElement('div');
                    pinContainer.className = 'text-end mb-3';
                    pinContainer.appendChild(pinButton);
                    sidebarHeader.insertBefore(pinContainer, sidebarHeader.firstChild);
                }
            }
        }
        
        // Aplicar estado inicial
        setSidebarState(isPinned);
        
        // Event listener para el botón de pin
        pinButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebarPin();
        });
    }
    
    function createPinButton() {
        const button = document.createElement('button');
        button.id = PIN_BUTTON_ID;
        button.type = 'button';
        button.className = 'btn btn-sm btn-link text-white p-1';
        button.setAttribute('title', isPinned ? 'Desfijar sidebar (modo flotante)' : 'Fijar sidebar (siempre visible)');
        button.innerHTML = isPinned 
            ? '<i class="fas fa-thumbtack"></i>'
            : '<i class="fas fa-thumbtack" style="opacity: 0.5;"></i>';
        button.style.cssText = 'opacity: 0.7; transition: opacity 0.2s; cursor: pointer; border: none;';
        return button;
    }
    
    function toggleSidebarPin() {
        isPinned = !isPinned;
        localStorage.setItem(STORAGE_KEY, isPinned.toString());
        setSidebarState(isPinned);
        updatePinButton();
    }
    
    function setSidebarState(pinned) {
        const sidebar = document.getElementById(SIDEBAR_ID);
        const mainContent = document.querySelector('.main-content');
        const pinButton = document.getElementById(PIN_BUTTON_ID);
        
        if (!sidebar) return;
        
        if (pinned) {
            // Modo fijado: sidebar siempre visible
            sidebar.classList.remove('sidebar-floating');
            sidebar.classList.add('sidebar-pinned');
            
            // En escritorio, mostrar sidebar
            if (window.innerWidth >= 992) {
                sidebar.style.left = '0';
            }
            
            // Ajustar main-content
            if (mainContent && window.innerWidth >= 992) {
                mainContent.style.marginLeft = '240px';
            }
        } else {
            // Modo flotante: sidebar colapsado por defecto
            sidebar.classList.remove('sidebar-pinned');
            sidebar.classList.add('sidebar-floating');
            
            // En escritorio, ocultar sidebar inicialmente
            if (window.innerWidth >= 992) {
                sidebar.style.left = '-240px';
            }
            
            // Ajustar main-content
            if (mainContent && window.innerWidth >= 992) {
                mainContent.style.marginLeft = '0';
            }
            
            // Agregar hover para mostrar sidebar en modo flotante
            setupFloatingHover();
        }
    }
    
    function updatePinButton() {
        const pinButton = document.getElementById(PIN_BUTTON_ID);
        if (!pinButton) return;
        
        pinButton.setAttribute('title', isPinned ? 'Desfijar sidebar (modo flotante)' : 'Fijar sidebar (siempre visible)');
        
        if (isPinned) {
            pinButton.innerHTML = '<i class="fas fa-thumbtack"></i>';
            pinButton.style.opacity = '1';
        } else {
            pinButton.innerHTML = '<i class="fas fa-thumbtack" style="opacity: 0.5;"></i>';
            pinButton.style.opacity = '0.7';
        }
    }
    
    let hoverTimeout = null;
    
    function setupFloatingHover() {
        const sidebar = document.getElementById(SIDEBAR_ID);
        if (!sidebar || !sidebar.classList.contains('sidebar-floating')) return;
        
        // Limpiar listeners anteriores
        document.removeEventListener('mousemove', handleMouseMove);
        sidebar.removeEventListener('mouseenter', keepSidebarVisible);
        sidebar.removeEventListener('mouseleave', hideSidebarOnHover);
        
        // Solo en escritorio
        if (window.innerWidth >= 992) {
            // Mostrar sidebar cuando el mouse está cerca del borde izquierdo
            document.addEventListener('mousemove', handleMouseMove);
            
            // Mantener visible cuando el mouse está sobre el sidebar
            sidebar.addEventListener('mouseenter', keepSidebarVisible);
            
            // Ocultar cuando el mouse sale del sidebar
            sidebar.addEventListener('mouseleave', hideSidebarOnHover);
        }
    }
    
    function handleMouseMove(e) {
        if (window.innerWidth < 992 || isPinned) return;
        
        const sidebar = document.getElementById(SIDEBAR_ID);
        if (!sidebar) return;
        
        // Mostrar sidebar cuando el mouse está cerca del borde izquierdo (15px)
        if (e.clientX < 15) {
            clearTimeout(hoverTimeout);
            sidebar.style.left = '0';
            sidebar.style.transition = 'left 0.3s ease';
        }
    }
    
    function keepSidebarVisible() {
        clearTimeout(hoverTimeout);
    }
    
    function hideSidebarOnHover(e) {
        if (window.innerWidth < 992 || isPinned) return;
        
        // Solo ocultar si el mouse realmente salió del sidebar
        const sidebar = document.getElementById(SIDEBAR_ID);
        if (sidebar && e.relatedTarget && !sidebar.contains(e.relatedTarget)) {
            hoverTimeout = setTimeout(function() {
                if (!sidebar.matches(':hover')) {
                    sidebar.style.left = '-240px';
                }
            }, 200); // Esperar 200ms antes de ocultar
        }
    }
    
    // Manejar resize
    function handleResize() {
        const sidebar = document.getElementById(SIDEBAR_ID);
        const mainContent = document.querySelector('.main-content');
        
        if (window.innerWidth < 992) {
            // En móviles, siempre oculto por defecto (comportamiento normal)
            if (sidebar) {
                sidebar.style.left = '-260px';
            }
            if (mainContent) {
                mainContent.style.marginLeft = '0';
            }
        } else {
            // En escritorio, aplicar el estado guardado
            setSidebarState(isPinned);
        }
    }
    
    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebarPin);
    } else {
        initSidebarPin();
    }
    
    // Manejar cambios de tamaño de ventana
    window.addEventListener('resize', handleResize);
    
    // Exportar función para toggle manual (si se necesita desde otros scripts)
    window.toggleSidebarPin = toggleSidebarPin;
    window.isSidebarPinned = function() {
        return isPinned;
    };
})();

