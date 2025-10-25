// assets/js/cart-sync.js
// Versión ampliada: expone updateQuantityServer, removeItemServer y clearCartServer
(function () {
  const CART_KEY = "sgu_cart";
  const CART_COUNT_ID = "cart-count";
  // Usar la ruta con extensión para compatibilidad con setups sin rewrite
  // Usar la ruta corta para pasar por el front controller (public/index.php) si existe
  const ACTION_URL = "cart_action";

  function getLocalCart() {
    try {
      return JSON.parse(localStorage.getItem(CART_KEY) || "[]");
    } catch (e) {
      return [];
    }
  }
  function saveLocalCart(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    try {
      window.dispatchEvent(
        new StorageEvent("storage", {
          key: CART_KEY,
          newValue: JSON.stringify(cart),
        })
      );
    } catch (e) {}
  }
  function calcTotalItems(cart) {
    return cart.reduce((s, i) => s + (parseInt(i.quantity) || 0), 0);
  }
  function updateBadge(count) {
    const el = document.getElementById(CART_COUNT_ID);
    if (!el) return;
    el.innerText = Number(count || 0);
    el.style.display = Number(count) > 0 ? "inline" : "none";
  }
  function getSafeServerTotal(summary) {
    if (!summary) return null;
    if (
      typeof summary.total_items !== "undefined" &&
      summary.total_items !== null
    ) {
      const n = Number(summary.total_items);
      return isNaN(n) ? null : n;
    }
    return null;
  }

  // ---- acciones de servidor ----
  async function addProduct(productId, quantity = 1, meta = {}) {
    const cart = getLocalCart();
    const existing = cart.find((i) => Number(i.id) === Number(productId));
    if (existing)
      existing.quantity = (parseInt(existing.quantity) || 0) + quantity;
    else
      cart.push({
        id: Number(productId),
        name: meta.name || "",
        price: meta.price || 0,
        quantity: quantity,
        image: meta.image || "",
      });
    saveLocalCart(cart);
    updateBadge(calcTotalItems(cart));

    try {
      const body = new URLSearchParams();
      body.append("action", "add");
      body.append("product_id", productId);
      body.append("quantity", quantity);
      const resp = await fetch(ACTION_URL, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        body: body.toString(),
        cache: "no-store",
      });
      const json = await resp.json();
      if (json.status === "ok" && typeof json.total_items !== "undefined")
        updateBadge(Number(json.total_items));
    } catch (e) {
      console.warn("Add failed", e);
    }
  }

  // Actualizar cantidad: actualiza localStorage y luego servidor
  async function updateQuantityServer(productId, quantity) {
    quantity = Math.max(0, parseInt(quantity || 0));
    let cart = getLocalCart();

    if (quantity <= 0) {
      cart = cart.filter((i) => Number(i.id) !== Number(productId));
    } else {
      const item = cart.find((i) => Number(i.id) === Number(productId));
      if (item) item.quantity = quantity;
      else
        cart.push({
          id: Number(productId),
          name: "",
          price: 0,
          quantity: quantity,
        });
    }

    // Guardar localmente primero (UX)
    saveLocalCart(cart);
    updateBadge(calcTotalItems(cart));

    // Enviar al servidor
    try {
      const body = new URLSearchParams();
      body.append("action", "update_quantity");
      body.append("product_id", productId);
      body.append("quantity", quantity);
      const resp = await fetch(ACTION_URL, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        body: body.toString(),
        cache: "no-store",
      });
      const json = await resp.json();
      if (json.status === "ok") {
        const serverTotal =
          getSafeServerTotal(json.summary || null) ??
          calcTotalItems(getLocalCart());
        updateBadge(serverTotal);
        console.log('[SGUCart] removeItemServer response ok for', productId, json);
        // Intentar eliminar la fila correspondiente y confirmar en consola
        try {
          const row = document.querySelector(`tr[data-product-id="${productId}"]`);
          console.log('[SGUCart] removeItemServer found row=', !!row, row);
          removeCartItemDOM(productId);
          console.log('[SGUCart] removeItemServer removed row for', productId);
        } catch (e) {
          console.warn('[SGUCart] removeItemServer DOM removal error for', productId, e);
        }
      }
    } catch (e) {
      console.warn("updateQuantityServer error", e);
    }

    // Si estamos en cart.php, actualizar DOM dinámicamente
    if (window.location.pathname.split("/").pop() === "cart.php") {
      updateCartItemDOM(productId, quantity);
    }
  }

  // Eliminar un item
  async function removeItemServer(productId) {
    // Actualizar localStorage primero
    let cart = getLocalCart().filter((i) => Number(i.id) !== Number(productId));
    saveLocalCart(cart);
    updateBadge(calcTotalItems(cart));

    // Llamada al servidor
    try {
      const body = new URLSearchParams();
      body.append("action", "remove_item");
      body.append("product_id", productId);
      const resp = await fetch(ACTION_URL, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        body: body.toString(),
        cache: "no-store",
      });
      const json = await resp.json();
      if (json.status === "ok") {
        const serverTotal =
          getSafeServerTotal(json.summary || null) ??
          calcTotalItems(getLocalCart());
        updateBadge(serverTotal);
        // Actualizar DOM inmediatamente: eliminar la fila correspondiente y recalcular totales
        try {
          removeCartItemDOM(productId);
        } catch (e) {
          // noop
        }
      }
    } catch (e) {
      console.warn("removeItemServer error", e);
    }

    if (window.location.pathname.split("/").pop() === "cart.php") {
      removeCartItemDOM(productId);
    }
  }

  // Vaciar carrito (server + local)
  async function clearCartServer() {
    // Limpiar local inmediatamente
    try {
      localStorage.removeItem(CART_KEY);
    } catch (e) {}
    updateBadge(0);

    // Llamada servidor
    try {
      const body = new URLSearchParams();
      body.append("action", "clear_cart");
      const resp = await fetch(ACTION_URL + "?action=clear_cart", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        cache: "no-store",
      });
      const json = await resp.json();
      if (json.status === "ok") {
        // good
      } else {
        console.warn("clearCartServer: server responded error", json);
      }
    } catch (e) {
      console.warn("clearCartServer error", e);
    }

    if (window.location.pathname.split("/").pop() === "cart.php") {
      location.reload(); // Para clear cart sí necesitamos recargar
    }
  }

  // Sync local->server (únicamente en páginas que no sean cart.php)
  async function syncLocalToServer() {
    const local = getLocalCart();
    if (!local || local.length === 0) {
      // pedimos resumen al servidor para badge
      try {
        const res = await fetch(ACTION_URL + "?action=get_summary", {
          cache: "no-store",
        });
        const j = await res.json();
        if (j.status === "ok" && j.summary) {
          const serverTotal = getSafeServerTotal(j.summary);
          if (serverTotal !== null) updateBadge(serverTotal);
          else updateBadge(0);
        } else {
          updateBadge(0);
        }
      } catch (e) {
        updateBadge(0);
      }
      return;
    }

    try {
      const resp = await fetch(ACTION_URL + "?action=sync", {
        method: "POST",
        headers: { "Content-Type": "application/json; charset=utf-8" },
        body: JSON.stringify({ cart: local }),
        cache: "no-store",
      });
      const json = await resp.json();
      if (json.status === "ok" && json.summary) {
        const items = (json.summary.items || []).map((it) => ({
          id: it.product_id || it.id,
          name: it.name || "",
          price: it.price || 0,
          quantity: it.quantity || 1,
          image: it.image_url || it.image || "",
        }));
        saveLocalCart(items);
        updateBadge(calcTotalItems(items));
      }
    } catch (e) {
      console.warn("sync failed", e);
    }
  }

  // ---- delegación de eventos ----
  document.addEventListener("click", function (e) {
    // DESHABILITADO: Delegado para botones .add-to-cart
    // Este listener está duplicado con product.php y causa doble inserción
    // const btn = e.target.closest('.add-to-cart');
    // if (btn) {
    //   ... código comentado para evitar duplicación ...
    // }

    // Delegado: eliminar desde lista (botón con clase .remove-from-cart)
    const rem = e.target.closest(".remove-from-cart");
    if (rem) {
      e.preventDefault();
      const pid = rem.dataset.productId || rem.getAttribute("data-product-id");
      if (!pid) return;
      console.log('[SGUCart] remove clicked, productId=', pid);
      if (!confirm("¿Eliminar este producto del carrito?")) return;
      // Ejecutar y manejar fallo para dar feedback en consola
      removeItemServer(pid).then(() => {
        console.log('[SGUCart] removeItemServer succeeded for', pid);
      }).catch((err) => {
        console.warn('[SGUCart] removeItemServer failed for', pid, err);
        // Intentar eliminar la fila en el DOM como fallback
        try { removeCartItemDOM(pid); } catch (e) {}
      });
      return;
    }

    // Delegado: Vaciar carrito (botón con id clear-cart-btn)
    const clearBtn = e.target.closest("#clear-cart-btn");
    if (clearBtn) {
      e.preventDefault();
      if (!confirm("¿Estás seguro de que deseas vaciar el carrito?")) return;
      clearCartServer();
      return;
    }
  });

  // Cambios en inputs de cantidad (clase .cart-quantity)
  document.addEventListener("change", function (e) {
    const el = e.target;
    if (el && el.classList && el.classList.contains("cart-quantity")) {
      const pid = el.dataset.productId || el.getAttribute("data-product-id");
      let qty = parseInt(el.value || 1);

      // Validar restricciones de mayorista
      const isMayorista = el.getAttribute("data-is-mayorista") === "1";
      const minQty = parseInt(el.getAttribute("data-min-quantity") || 1);
      const step = parseInt(el.getAttribute("step") || 1);

      if (isMayorista) {
        // Para mayorista: validar mínimo y múltiplos
        if (qty < minQty) {
          qty = minQty;
          el.value = qty;
          if (typeof showToast === "function") {
            showToast(
              `Cantidad mínima para mayorista: ${minQty} unidades`,
              "warning"
            );
          }
        } else if ((qty - minQty) % step !== 0) {
          // Ajustar a múltiplo válido más cercano
          const remainder = (qty - minQty) % step;
          qty =
            remainder < step / 2 ? qty - remainder : qty + (step - remainder);
          el.value = qty;
          if (typeof showToast === "function") {
            showToast(
              `Cantidad ajustada a incrementos de ${step} unidades`,
              "info"
            );
          }
        }
      }

      updateQuantityServer(pid, qty);
    }
  });

  // Al cargar: actualizar badge y precio total en navbar
  document.addEventListener("DOMContentLoaded", function () {
    (async function () {
      try {
        const res = await fetch(ACTION_URL + "?action=get_summary", {
          cache: "no-store",
        });
        const j = await res.json();
        if (j.status === "ok" && j.summary) {
          const serverTotal = getSafeServerTotal(j.summary);
          if (serverTotal !== null) updateBadge(serverTotal);
          else updateBadge(calcTotalItems(getLocalCart()));

          // Actualizar precio total en navbar - solo si hay productos
          if (j.summary.total && serverTotal > 0) {
            const cartTotalElement = document.getElementById("cart-total");
            if (cartTotalElement) {
              cartTotalElement.textContent = parseFloat(
                j.summary.total
              ).toFixed(2);
            }
          } else {
            // Si no hay productos, mostrar 0.00
            const cartTotalElement = document.getElementById("cart-total");
            if (cartTotalElement) {
              cartTotalElement.textContent = "0.00";
            }
          }
        } else {
          updateBadge(calcTotalItems(getLocalCart()));
          // Si no hay respuesta del servidor, asegurar que el total sea 0.00
          const cartTotalElement = document.getElementById("cart-total");
          if (cartTotalElement) {
            cartTotalElement.textContent = "0.00";
          }
        }
      } catch (e) {
        updateBadge(calcTotalItems(getLocalCart()));
        // En caso de error, también asegurar que el total sea 0.00
        const cartTotalElement = document.getElementById("cart-total");
        if (cartTotalElement) {
          cartTotalElement.textContent = "0.00";
        }
      }
    })();

    const page = window.location.pathname.split("/").pop();
    if (page !== "cart.php") syncLocalToServer();
  });

  // Multi-pestaña: actualizar badge si cambian los datos
  window.addEventListener("storage", (e) => {
    if (e.key === CART_KEY) {
      const cart = JSON.parse(e.newValue || "[]");
      updateBadge(calcTotalItems(cart));
    }
  });

  // Funciones para actualizar DOM dinámicamente en cart.php
  function updateCartItemDOM(productId, quantity) {
    const row = document.querySelector(`tr[data-product-id="${productId}"]`);
    if (!row) return;

    if (quantity <= 0) {
      row.remove();
      updateCartTotals();
      return;
    }

    const quantityInput = row.querySelector(".cart-quantity");
    if (quantityInput) {
      quantityInput.value = quantity;
    }

    // Actualizar subtotal del item
    const priceCell = row.querySelector(".item-price");
    const subtotalCell = row.querySelector(".item-subtotal");
    if (priceCell && subtotalCell) {
      const price = parseFloat(priceCell.textContent.replace(/[^0-9.]/g, ""));
      const subtotal = price * quantity;
      subtotalCell.textContent = `S/ ${subtotal.toFixed(2)}`;
    }

    updateCartTotals();
  }

  function removeCartItemDOM(productId) {
    const row = document.querySelector(`tr[data-product-id="${productId}"]`);
    if (row) {
      row.remove();
      updateCartTotals();
    }
  }

  function updateCartTotals() {
    let subtotal = 0;
    const subtotalCells = document.querySelectorAll(".item-subtotal");
    subtotalCells.forEach((cell) => {
      const amount = parseFloat(cell.textContent.replace(/[^0-9.]/g, ""));
      if (!isNaN(amount)) subtotal += amount;
    });

    const shipping = subtotal >= 150 ? 0 : 15;
    const tax = 0; // Sin impuestos por ahora
    const total = subtotal + shipping + tax;

    // Actualizar elementos del DOM
    const subtotalElement = document.getElementById("cart-subtotal");
    if (subtotalElement) {
      subtotalElement.textContent = `S/ ${subtotal.toFixed(2)}`;
    }

    const taxElement = document.getElementById("cart-tax");
    if (taxElement) {
      taxElement.textContent = `S/ ${tax.toFixed(2)}`;
    }

    // Actualizar AMBOS elementos cart-total (header y cart.php)
    const totalElements = document.querySelectorAll("#cart-total");
    totalElements.forEach((element) => {
      element.textContent = total.toFixed(2);
    });

    // Actualizar mensaje de envío gratis
    const shippingElement = document.getElementById("cart-shipping");
    if (shippingElement) {
      if (shipping === 0) {
        shippingElement.innerHTML = '<span class="text-success">Gratis</span>';
      } else {
        shippingElement.textContent = `S/ ${shipping.toFixed(2)}`;
      }
    }

    // Actualizar alerta de envío gratis
    const freeShippingAlert = document.querySelector(".alert.alert-info");
    if (freeShippingAlert) {
      if (subtotal >= 150) {
        freeShippingAlert.style.display = "none";
      } else {
        freeShippingAlert.style.display = "block";
        const remaining = 150 - subtotal;
        freeShippingAlert.innerHTML = `<i class="fas fa-info-circle"></i> Agrega S/ ${remaining.toFixed(
          2
        )} más para envío gratis`;
      }
    }

    // Mostrar/ocultar mensaje de carrito vacío
    const cartTable = document.querySelector(".cart-table");
    const emptyMessage = document.querySelector(".empty-cart-message");
    if (subtotal === 0) {
      if (cartTable) cartTable.style.display = "none";
      if (emptyMessage) emptyMessage.style.display = "block";
    } else {
      if (cartTable) cartTable.style.display = "table";
      if (emptyMessage) emptyMessage.style.display = "none";
    }
  }

  // Export público para llamadas desde inline JS
  window.SGUCart = {
    getLocalCart,
    saveLocalCart,
    addProduct,
    updateQuantityServer,
    removeItemServer,
    clearCartServer,
    syncLocalToServer,
    updateCartItemDOM,
    removeCartItemDOM,
  };
})();

