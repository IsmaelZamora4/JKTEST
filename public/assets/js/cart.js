// assets/js/cart.js
// Fallback ligero para botones de eliminar en el carrito.
// Solo se activa si no existe la API SGUCart (evita duplicar listeners).

document.addEventListener('DOMContentLoaded', function () {
  if (window.SGUCart && typeof window.SGUCart.removeItemServer === 'function') {
    // Ya existe la implementación principal; no hacemos nada para evitar duplicar handlers
    console.log('[cart.js] SGUCart detected — fallback disabled');
    return;
  }

  const buttons = document.querySelectorAll('.remove-from-cart');
  buttons.forEach((btn) => {
    btn.addEventListener('click', async function (e) {
      e.preventDefault();
      const pid = btn.dataset.productId || btn.getAttribute('data-product-id');
      if (!pid) return;
      if (!confirm('¿Eliminar este producto del carrito?')) return;

      try {
        const body = new URLSearchParams();
        body.append('action', 'remove_item');
        body.append('product_id', pid);

        const resp = await fetch('cart_action.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: body.toString(),
          cache: 'no-store'
        });

        const json = await resp.json();
        if (json && json.status === 'ok') {
          // Remover fila del DOM
          const row = btn.closest('tr[data-product-id]');
          if (row) row.remove();

          // Actualizar contador si viene en la respuesta
          if (json.summary && typeof json.summary.total_items !== 'undefined') {
            const countEl = document.getElementById('cart-count');
            if (countEl) countEl.textContent = json.summary.total_items;
          }

          // Intentar actualizar totales en la página
          if (typeof window.updateCartTotals === 'function') {
            try { window.updateCartTotals(); } catch (e) {}
          }

          // Si existe la función de SGUCart para actualizar DOM totales, usarla
          if (window.SGUCart && typeof window.SGUCart.updateCartItemDOM === 'function') {
            try { window.SGUCart.updateCartItemDOM(pid, 0); } catch (e) {}
          }

        } else {
          alert((json && json.message) || 'No se pudo eliminar el producto');
        }
      } catch (err) {
        console.warn('Fallback remove failed', err);
        alert('Error al comunicarse con el servidor');
      }
    });
  });
});
