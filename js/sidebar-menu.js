// sidebar-menu.js - Sistema robusto para preservar estructura del sidebar
(function() {
    'use strict';
    
    let observer = null;
    let isInitialized = false;
    
    function forceSidebarStructure() {
        // Forzar que los submenús sean siempre visibles
        const submenus = document.querySelectorAll('#gestionSubmenu, #visitasSubmenu');
        submenus.forEach(function(submenu) {
            if (!submenu) return;
            
            // Remover TODAS las clases de Bootstrap que puedan ocultar
            submenu.classList.remove('collapse', 'collapsing', 'show', 'hide', 'hidden');
            
            // Forzar visibilidad con estilos inline (temporal, CSS !important tiene prioridad)
            submenu.style.setProperty('display', 'block', 'important');
            submenu.style.setProperty('height', 'auto', 'important');
            submenu.style.setProperty('overflow', 'visible', 'important');
            submenu.style.setProperty('opacity', '1', 'important');
            submenu.style.setProperty('visibility', 'visible', 'important');
            submenu.style.setProperty('max-height', 'none', 'important');
            submenu.style.setProperty('transform', 'none', 'important');
            submenu.style.setProperty('transition', 'none', 'important');
        });
        
        // Preservar estructura de TODOS los enlaces del sidebar
        const allNavLinks = document.querySelectorAll('.sidebar .nav-link');
        allNavLinks.forEach(function(link) {
            if (!link) return;
            
            // Limpiar estilos inline problemáticos que puedan romper la estructura
            link.style.removeProperty('transform');
            link.style.removeProperty('scale');
            link.style.removeProperty('translate');
            
            // Si es un submenú, asegurar padding y margin correctos
            if (link.classList.contains('submenu')) {
                // No forzar estilos inline, dejar que CSS maneje
                link.style.removeProperty('padding');
                link.style.removeProperty('margin');
            } else if (!link.classList.contains('text-white-50')) {
                // Enlaces normales - limpiar estilos inline
                link.style.removeProperty('padding');
                link.style.removeProperty('margin');
            }
        });
        
        // Asegurar que el contenedor del sidebar mantenga su estructura
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.style.setProperty('width', '240px', 'important');
            sidebar.style.setProperty('position', 'fixed', 'important');
            sidebar.style.setProperty('left', '0', 'important');
            sidebar.style.setProperty('top', '0', 'important');
        }
    }
    
    function preventBootstrapCollapse() {
        // Prevenir comportamiento de colapso de Bootstrap
        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function(element) {
            const targetId = element.getAttribute('data-bs-target');
            if (targetId && (targetId === '#gestionSubmenu' || targetId === '#visitasSubmenu')) {
                // Remover el atributo data-bs-toggle para prevenir Bootstrap
                element.removeAttribute('data-bs-toggle');
                
                // Agregar listener para prevenir cualquier acción
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    return false;
                }, true);
            }
        });
    }
    
    function initSidebar() {
        if (isInitialized) return;
        
        forceSidebarStructure();
        preventBootstrapCollapse();
        
        // Crear MutationObserver para detectar cambios en los submenús
        const submenus = document.querySelectorAll('#gestionSubmenu, #visitasSubmenu');
        if (submenus.length > 0 && !observer) {
            observer = new MutationObserver(function(mutations) {
                let shouldForce = false;
                
                mutations.forEach(function(mutation) {
                    // Si se agregan clases de Bootstrap
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        const target = mutation.target;
                        if (target.id === 'gestionSubmenu' || target.id === 'visitasSubmenu') {
                            if (target.classList.contains('collapse') || 
                                target.classList.contains('collapsing') ||
                                target.style.display === 'none') {
                                shouldForce = true;
                            }
                        }
                    }
                    
                    // Si se modifican estilos inline
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        const target = mutation.target;
                        if (target.id === 'gestionSubmenu' || target.id === 'visitasSubmenu') {
                            if (target.style.display === 'none' || 
                                target.style.height === '0px' ||
                                target.style.opacity === '0') {
                                shouldForce = true;
                            }
                        }
                    }
                });
                
                if (shouldForce) {
                    forceSidebarStructure();
                }
            });
            
            // Observar cambios en los submenús
            submenus.forEach(function(submenu) {
                observer.observe(submenu, {
                    attributes: true,
                    attributeFilter: ['class', 'style'],
                    childList: false,
                    subtree: false
                });
            });
        }
        
        isInitialized = true;
    }
    
    // Ejecutar inmediatamente si el DOM ya está listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initSidebar();
            // Ejecutar múltiples veces para asegurar
            setTimeout(initSidebar, 50);
            setTimeout(initSidebar, 200);
        });
    } else {
        initSidebar();
        setTimeout(initSidebar, 50);
        setTimeout(initSidebar, 200);
    }
    
    // Ejecutar cuando la página esté completamente cargada
    window.addEventListener('load', function() {
        initSidebar();
        setTimeout(initSidebar, 100);
    });
    
    // Ejecutar después de que Bootstrap se inicialice
    if (typeof bootstrap !== 'undefined') {
        setTimeout(initSidebar, 300);
    }
    
    // Ejecutar periódicamente para mantener la estructura (solo si es necesario)
    let checkInterval = null;
    function startPeriodicCheck() {
        if (checkInterval) return;
        checkInterval = setInterval(function() {
            const submenus = document.querySelectorAll('#gestionSubmenu, #visitasSubmenu');
            let needsFix = false;
            submenus.forEach(function(submenu) {
                if (submenu.classList.contains('collapse') || 
                    submenu.style.display === 'none' ||
                    submenu.style.height === '0px') {
                    needsFix = true;
                }
            });
            if (needsFix) {
                forceSidebarStructure();
            }
        }, 500);
    }
    
    // Iniciar verificación periódica después de 1 segundo
    setTimeout(startPeriodicCheck, 1000);
})();
