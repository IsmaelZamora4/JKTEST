// products-filter.js
// Manejo de filtros dinámicos para mayorista/unidad en products.php

document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const pricingModeRadios = document.querySelectorAll('input[name="pricing_mode"]');
    const categoryLinks = document.querySelectorAll('.category-filters a');
    const sortSelect = document.getElementById('sort');
    
    // Función para obtener parámetros actuales de la URL
    function getCurrentParams() {
        const urlParams = new URLSearchParams(window.location.search);
        return {
            search: urlParams.get('search') || '',
            category: urlParams.get('category') || '',
            sort: urlParams.get('sort') || 'newest',
            pricing: urlParams.get('pricing') || 'unidad'
        };
    }
    
    // Función para construir nueva URL con parámetros
    function buildURL(params) {
        const url = new URL(window.location.origin + window.location.pathname);
        Object.keys(params).forEach(key => {
            if (params[key] && params[key] !== '') {
                url.searchParams.set(key, params[key]);
            }
        });
        return url.toString();
    }
    
    // Función para filtrar productos por modalidad
    function filterByPricingMode(mode) {
        const currentParams = getCurrentParams();
        currentParams.pricing = mode;
        
        // Mostrar loading
        showLoadingState();
        
        // Redirigir con nuevos parámetros
        window.location.href = buildURL(currentParams);
    }
    
    // Función para mostrar estado de carga
    function showLoadingState() {
        const productsGrid = document.querySelector('.products-grid');
        if (productsGrid) {
            productsGrid.style.opacity = '0.5';
            productsGrid.style.pointerEvents = 'none';
        }
        
        // Agregar spinner si no existe
        if (!document.querySelector('.loading-spinner')) {
            const spinner = document.createElement('div');
            spinner.className = 'loading-spinner text-center my-4';
            spinner.innerHTML = `
                <div class="spinner-border text-warning" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 text-muted">Actualizando productos...</p>
            `;
            
            const container = document.querySelector('.container .row');
            if (container) {
                container.appendChild(spinner);
            }
        }
    }
    
    // Event listeners para cambio de modalidad
    pricingModeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                filterByPricingMode(this.value);
            }
        });
    });
    
    // Actualizar enlaces de categoría para mantener modalidad actual
    categoryLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const currentParams = getCurrentParams();
            const href = this.getAttribute('href');
            const url = new URL(href, window.location.origin);
            
            // Mantener modalidad actual
            url.searchParams.set('pricing', currentParams.pricing);
            
            // Mantener búsqueda si existe
            if (currentParams.search) {
                url.searchParams.set('search', currentParams.search);
            }
            
            showLoadingState();
            window.location.href = url.toString();
        });
    });
    
    // Actualizar formulario de ordenamiento
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const form = this.closest('form');
            if (form) {
                showLoadingState();
                form.submit();
            }
        });
    }
    
    // Función para actualizar productos via AJAX (opcional)
    async function updateProductsAjax(params) {
        try {
            const response = await fetch(`products.php?${new URLSearchParams(params).toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Actualizar grid de productos
                const newProductsGrid = doc.querySelector('.products-grid');
                const currentProductsGrid = document.querySelector('.products-grid');
                
                if (newProductsGrid && currentProductsGrid) {
                    currentProductsGrid.innerHTML = newProductsGrid.innerHTML;
                }
                
                // Actualizar paginación
                const newPagination = doc.querySelector('.pagination');
                const currentPagination = document.querySelector('.pagination');
                
                if (newPagination && currentPagination) {
                    currentPagination.innerHTML = newPagination.innerHTML;
                }
                
                // Actualizar contador de productos
                const newCounter = doc.querySelector('.text-muted.small');
                const currentCounter = document.querySelector('.text-muted.small');
                
                if (newCounter && currentCounter) {
                    currentCounter.textContent = newCounter.textContent;
                }
                
                // Remover loading
                const spinner = document.querySelector('.loading-spinner');
                if (spinner) {
                    spinner.remove();
                }
                
                const productsGrid = document.querySelector('.products-grid');
                if (productsGrid) {
                    productsGrid.style.opacity = '1';
                    productsGrid.style.pointerEvents = 'auto';
                }
                
                // Reinicializar event listeners para nuevos elementos
                initializeProductCards();
                
            } else {
                throw new Error('Error al cargar productos');
            }
        } catch (error) {
            console.error('Error updating products:', error);
            // Fallback a recarga completa
            window.location.reload();
        }
    }
    
    // Inicializar cards de productos
    function initializeProductCards() {
        const productCards = document.querySelectorAll('.product-card');
        productCards.forEach(card => {
            // Animación de entrada
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.3s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, Math.random() * 200);
        });
    }
    
    // Inicializar al cargar
    initializeProductCards();
    
    // Manejar botón de limpiar filtros
    const clearFiltersBtn = document.querySelector('a[href*="products.php"]');
    if (clearFiltersBtn && clearFiltersBtn.textContent.includes('Limpiar')) {
        clearFiltersBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showLoadingState();
            
            const currentParams = getCurrentParams();
            // Mantener solo modalidad y ordenamiento
            const cleanParams = {
                pricing: currentParams.pricing,
                sort: currentParams.sort
            };
            
            window.location.href = buildURL(cleanParams);
        });
    }
});

// Función global para cambiar modalidad (llamada desde HTML si es necesario)
window.changePricingMode = function(mode) {
    const radio = document.querySelector(`input[name="pricing_mode"][value="${mode}"]`);
    if (radio) {
        radio.checked = true;
        radio.dispatchEvent(new Event('change'));
    }
};
