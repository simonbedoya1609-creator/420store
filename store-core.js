
/* ---------- Configuración ---------- */
const API_BASE = 'php/';
const CART_KEY = "420_cart_v1";
const POINTS_KEY = "420_points_v1";

let carrito = [];
let puntos = 0;
let lastAward = 0;
let usuarioLogueado = null;

/* ---------- Inicialización ---------- */
document.addEventListener("DOMContentLoaded", async () => {
  await verificarSesion();
  await cargarCarritoDesdeDB();
  cargarPuntosLocal();
  renderCarrito();
  updatePuntosUI(false);
});

/* ====================================================
   GESTIÓN DE SESIÓN
==================================================== */

async function verificarSesion() {
  try {
    const response = await fetch(`${API_BASE}obtenerUsuario.php`);
    const result = await response.json();
    
    if (result.success && result.data.sesion_activa) {
      usuarioLogueado = result.data;
      puntos = usuarioLogueado.puntos || 0;
      mostrarUsuarioLogueado();
    }
  } catch (error) {
    console.log('Sin sesión activa');
  }
}

function mostrarUsuarioLogueado() {
  const menuPuntos = getElByIds("menu-puntos", "menuPuntos");
  if (menuPuntos && usuarioLogueado) {
    menuPuntos.innerHTML = `👤 ${usuarioLogueado.nombre} | ${usuarioLogueado.puntos} pts (${usuarioLogueado.nivel})`;
  }
}

/* ====================================================
   CARRITO - INTEGRACIÓN CON BD
==================================================== */

async function cargarCarritoDesdeDB() {
  if (!usuarioLogueado) {
    // Si no hay sesión, cargar de localStorage
    cargarCarritoLocal();
    return;
  }
  
  try {
    const response = await fetch(`${API_BASE}carrito.php?accion=obtener`);
    const result = await response.json();
    
    if (result.success) {
      carrito = result.data.items.map(item => ({
        id: item.id,
        nombre: item.nombre,
        precio: item.precio,
        imagen: item.imagen_url,
        cantidad: item.cantidad,
        producto_id: item.producto_id
      }));
      renderCarrito();
    }
  } catch (error) {
    console.error('Error al cargar carrito:', error);
    cargarCarritoLocal();
  }
}

function cargarCarritoLocal() {
  try {
    const raw = localStorage.getItem(CART_KEY);
    carrito = raw ? JSON.parse(raw) : [];
    if (!Array.isArray(carrito)) carrito = [];
  } catch (e) {
    carrito = [];
  }
}

function cargarPuntosLocal() {
  if (!usuarioLogueado) {
    try {
      const rp = localStorage.getItem(POINTS_KEY);
      puntos = rp ? parseInt(rp, 10) || 0 : 0;
    } catch (e) {
      puntos = 0;
    }
  }
}

/* ====================================================
   AGREGAR AL CARRITO
==================================================== */

async function agregarAlCarrito(nombre, precio, imagen = '', producto_id = null) {
  try {
    if (usuarioLogueado && producto_id) {
      // Agregar a BD
      const formData = new FormData();
      formData.append('producto_id', producto_id);
      formData.append('cantidad', 1);
      
      const response = await fetch(`${API_BASE}carrito.php?accion=agregar`, {
        method: 'POST',
        body: formData
      });
      
      const result = await response.json();
      
      if (result.success) {
        await cargarCarritoDesdeDB();
        mostrarNotificacionPersonalizada("exito", `${nombre} agregado al carrito`);
        awardPointsForPurchase();
      } else {
        mostrarNotificacionPersonalizada("error", result.message);
      }
    } else {
      // Agregar a localStorage
      const item = { nombre: String(nombre), precio: Number(precio) || 0, imagen: imagen || "" };
      carrito.push(item);
      localStorage.setItem(CART_KEY, JSON.stringify(carrito));
      renderCarrito();
      awardPointsForPurchase();
      mostrarNotificacionPersonalizada("exito", `${nombre} agregado al carrito`);
    }
  } catch (e) {
    console.error("Error al agregar:", e);
    mostrarNotificacionPersonalizada("error", "Error al agregar producto");
  }
}

// Compatibilidad con firma antigua
function addToCart(a, b, c) {
  if (c !== undefined) {
    agregarAlCarrito(String(b), Number(c) || 0, "", Number(a));
  } else {
    agregarAlCarrito(String(a), Number(b) || 0, "");
  }
}

/* ====================================================
   ELIMINAR DEL CARRITO
==================================================== */

async function eliminarProducto(index) {
  if (!Number.isInteger(index) || index < 0 || index >= carrito.length) return;
  
  const item = carrito[index];
  
  try {
    if (usuarioLogueado && item.id) {
      // Eliminar de BD
      const formData = new FormData();
      formData.append('carrito_id', item.id);
      
      const response = await fetch(`${API_BASE}carrito.php?accion=eliminar`, {
        method: 'POST',
        body: formData
      });
      
      const result = await response.json();
      
      if (result.success) {
        await cargarCarritoDesdeDB();
        mostrarNotificacionPersonalizada("advertencia", "Artículo eliminado");
      }
    } else {
      // Eliminar de localStorage
      carrito.splice(index, 1);
      localStorage.setItem(CART_KEY, JSON.stringify(carrito));
      renderCarrito();
      mostrarNotificacionPersonalizada("advertencia", "Artículo eliminado");
    }
  } catch (error) {
    console.error('Error al eliminar:', error);
  }
}

/* ====================================================
   VACIAR CARRITO
==================================================== */

async function vaciarCarrito() {
  try {
    if (usuarioLogueado) {
      const response = await fetch(`${API_BASE}carrito.php?accion=vaciar`, {
        method: 'POST'
      });
      
      const result = await response.json();
      
      if (result.success) {
        carrito = [];
        renderCarrito();
        mostrarNotificacionPersonalizada("error", "Carrito vaciado");
      }
    } else {
      carrito = [];
      localStorage.setItem(CART_KEY, JSON.stringify(carrito));
      renderCarrito();
      mostrarNotificacionPersonalizada("error", "Carrito vaciado");
    }
  } catch (error) {
    console.error('Error:', error);
  }
}

/* ====================================================
   PROCESAR COMPRA
==================================================== */

async function procesarCompra() {
  if (carrito.length === 0) {
    mostrarNotificacionPersonalizada("advertencia", "El carrito está vacío");
    return;
  }
  
  if (!usuarioLogueado) {
    mostrarNotificacionPersonalizada("advertencia", "Debes iniciar sesión para comprar");
    window.location.href = 'login.html';
    return;
  }
  
  if (!confirm('¿Confirmar compra?')) return;
  
  try {
    const response = await fetch(`${API_BASE}pedidos.php?accion=procesar`, {
      method: 'POST'
    });
    
    const result = await response.json();
    
    if (result.success) {
      mostrarNotificacionPersonalizada("exito", `¡Compra exitosa! Ganaste ${result.data.puntos_ganados} puntos`);
      
      // Actualizar puntos locales
      puntos = result.data.puntos_totales;
      usuarioLogueado.puntos = puntos;
      
      // Recargar carrito y UI
      await cargarCarritoDesdeDB();
      updatePuntosUI(true);
      
      // Cerrar panel del carrito
      const panel = document.getElementById("carritoPanel");
      if (panel) panel.classList.remove("activo");
      
      setTimeout(() => {
        alert(`Pedido #${result.data.pedido_id} realizado exitosamente\nTotal: $${result.data.total.toLocaleString()} COP\nPuntos ganados: ${result.data.puntos_ganados}`);
      }, 500);
    } else {
      mostrarNotificacionPersonalizada("error", result.message);
    }
  } catch (error) {
    console.error('Error al procesar compra:', error);
    mostrarNotificacionPersonalizada("error", "Error al procesar la compra");
  }
}

/* ====================================================
   RENDER DEL CARRITO
==================================================== */

function renderCarrito() {
  const lista = getElByIds("lista-carrito", "carritoContenido", "listaCarrito");
  const totalEl = getElByIds("total-carrito", "carritoTotal", "totalCarrito");

  if (!lista || !totalEl) {
    updateMenuPointsBadge();
    actualizarContadorCarrito();
    return;
  }

  lista.innerHTML = "";
  let total = 0;

  carrito.forEach((item, i) => {
    const precio = Number(item.precio) || 0;
    total += precio * (item.cantidad || 1);

    const imgHTML = item.imagen
      ? `<img src="${escapeHtml(item.imagen)}" alt="${escapeHtml(item.nombre)}" style="width:56px;height:56px;object-fit:cover;border-radius:8px;margin-right:10px;">`
      : "";

    const row = document.createElement("div");
    row.className = "carrito-item";
    row.innerHTML = `
      <div style="display:flex;align-items:center;gap:10px;">
        ${imgHTML}
        <div>
          <p style="margin:0;font-weight:600">${escapeHtml(item.nombre)}</p>
          <small style="color:#bbb">$${formatMoney(precio)} COP</small>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:8px;">
        <button class="btn-eliminar" data-idx="${i}" style="background:transparent;border:1px solid #daa520;color:#daa520;padding:6px 8px;border-radius:6px;cursor:pointer;">X</button>
      </div>
    `;
    lista.appendChild(row);
  });

  totalEl.textContent = `${formatMoney(total)} COP`;

  lista.querySelectorAll(".btn-eliminar").forEach(btn => {
    btn.removeEventListener("click", onEliminarClick);
    btn.addEventListener("click", onEliminarClick);
  });

  actualizarContadorCarrito();
  updateMenuPointsBadge();
}

function onEliminarClick(e) {
  const idx = Number(e.currentTarget.getAttribute("data-idx"));
  if (!Number.isInteger(idx)) return;
  eliminarProducto(idx);
}

function actualizarContadorCarrito() {
  const contador = getElByIds("carritoContador", "carritoContadorHeader");
  if (contador) contador.textContent = carrito.length;
}

/* ====================================================
   TOGGLE CARRITO
==================================================== */

function toggleCarrito() {
  const panel = document.getElementById("carritoPanel");
  if (panel) {
    panel.classList.toggle("activo");
  }
}

/* ====================================================
   GAMIFICACIÓN (PUNTOS)
==================================================== */

function obtenerNivelSegunPuntos(p) {
  if (p >= 2000) return "Diamante";
  if (p >= 1000) return "Oro";
  if (p >= 500) return "Plata";
  return "Bronce";
}

function puntosParaSiguienteNivel(p) {
  if (p < 500) return 500 - p;
  if (p < 1000) return 1000 - p;
  if (p < 2000) return 2000 - p;
  return 0;
}

function awardPointsForPurchase() {
  const nivel = obtenerNivelSegunPuntos(puntos);
  let bonus = 5;
  if (nivel === "Plata") bonus = 10;
  if (nivel === "Oro") bonus = 15;
  if (nivel === "Diamante") bonus = 20;

  puntos = (Number(puntos) || 0) + bonus;
  lastAward = bonus;

  if (!usuarioLogueado) {
    try { localStorage.setItem(POINTS_KEY, String(puntos)); } catch (e) {}
  }
  
  updatePuntosUI(true);
}

function updatePuntosUI(announce = false) {
  const barra = getElByIds("barra-progreso", "barraProgreso");
  const estado = getElByIds("estado-puntos", "estadoPuntos");
  const menuPuntos = getElByIds("menu-puntos", "menuPuntos");

  if (!barra || !estado) return;

  const nivel = obtenerNivelSegunPuntos(puntos);
  const faltan = puntosParaSiguienteNivel(puntos);

  let percent = 0;
  if (nivel === "Bronce") percent = (puntos / 500) * 100;
  else if (nivel === "Plata") percent = ((puntos - 500) / 500) * 100;
  else if (nivel === "Oro") percent = ((puntos - 1000) / 1000) * 100;
  else percent = 100;

  barra.style.width = Math.min(percent, 100) + "%";

  estado.innerHTML = `
    <strong>${puntos} pts</strong>
    — Nivel <strong style="color:#daa520">${nivel}</strong>
    <br>${nivel !== "Diamante" ? `Te faltan ${faltan} pts para subir` : "¡Nivel máximo!"}
  `;

  if (menuPuntos && usuarioLogueado) {
    menuPuntos.innerHTML = `👤 ${usuarioLogueado.nombre} | ${puntos} pts (${nivel})`;
  } else if (menuPuntos) {
    menuPuntos.textContent = `${puntos} pts (${nivel})`;
  }

  if (announce && faltan === 0) {
    mostrarNotificacionPersonalizada("exito", `🏆 ¡Subiste a nivel ${nivel}!`);
  }
}

function updateMenuPointsBadge() {
  const menuPuntos = getElByIds("menu-puntos", "menuPuntos");
  if (!menuPuntos) return;
  const nivel = obtenerNivelSegunPuntos(puntos);
  if (usuarioLogueado) {
    menuPuntos.innerHTML = `👤 ${usuarioLogueado.nombre} | ${puntos} pts (${nivel})`;
  } else {
    menuPuntos.textContent = `${puntos} pts (${nivel})`;
  }
}

/* ====================================================
   NOTIFICACIONES
==================================================== */

function mostrarNotificacionPersonalizada(tipo, texto, duracion = 2100) {
  try {
    const tiposValidos = ["exito", "error", "info", "advertencia"];
    if (!tiposValidos.includes(tipo)) tipo = "info";

    const n = document.createElement("div");
    n.className = `notificacion ${tipo}`;
    n.textContent = texto;
    document.body.appendChild(n);

    requestAnimationFrame(() => n.classList.add("mostrar"));
    setTimeout(() => {
      n.classList.remove("mostrar");
      setTimeout(() => n.remove(), 400);
    }, duracion);
  } catch (e) {
    console.warn("Error en notificación:", e);
  }
}

function mostrarNotificacion(texto) {
  mostrarNotificacionPersonalizada("info", texto);
}

/* ====================================================
   HELPERS
==================================================== */

function getElByIds(...ids) {
  for (const id of ids) {
    const el = document.getElementById(id);
    if (el) return el;
  }
  return null;
}

function escapeHtml(s) {
  if (typeof s !== "string") return s;
  return s.replace(/[&<>"']/g, m => ({ "&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;" })[m]);
}

function formatMoney(n) {
  return Number(n || 0).toLocaleString();
}

/* ====================================================
   EXPOSICIONES GLOBALES
==================================================== */

window.agregarAlCarrito = agregarAlCarrito;
window.addToCart = addToCart;
window.toggleCarrito = toggleCarrito;
window.vaciarCarrito = vaciarCarrito;
window.eliminarProducto = eliminarProducto;
window.procesarCompra = procesarCompra;
window.mostrarNotificacion = mostrarNotificacion;
window.mostrarNotificacionPersonalizada = mostrarNotificacionPersonalizada;
window.updatePuntosUI = updatePuntosUI;
window.verificarSesion = verificarSesion;