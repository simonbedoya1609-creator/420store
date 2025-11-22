/**
 * =====================================================
 * ARCHIVO: tienda.js
 * DESCRIPCIÓN: Lógica principal de la tienda
 * PROYECTO: 420 Store
 * =====================================================
 */

const API_BASE = 'php/';
let carrito = [];
let usuarioActual = null;

// =====================================================
// INICIALIZACIÓN
// =====================================================

document.addEventListener('DOMContentLoaded', async () => {
  console.log('🚀 Iniciando 420 Store...');
  
  // Verificar sesión
  await verificarSesion();
  
  // Cargar productos según la página
  const pagina = window.location.pathname.split('/').pop();
  
  if (pagina === 'index.html' || pagina === '') {
    cargarProductosDestacados();
  } else if (pagina === 'productos.html') {
    cargarTodosLosProductos();
    cargarCategorias();
  } else if (pagina === 'ofertas.html') {
    cargarOfertas();
  }
  
  // Cargar carrito
  await cargarCarrito();
  
  // Actualizar puntos si hay usuario
  if (usuarioActual) {
    actualizarPanelPuntos();
  }
});

// =====================================================
// VERIFICAR SESIÓN
// =====================================================

async function verificarSesion() {
  try {
    const response = await fetch(`${API_BASE}verificar_sesion.php`);
    const result = await response.json();
    
    if (result.success && result.data.usuario) {
      usuarioActual = result.data.usuario;
      console.log('✅ Usuario logueado:', usuarioActual.nombre);
    } else {
      usuarioActual = null;
      console.log('⚠️ No hay sesión activa');
    }
  } catch (error) {
    console.error('Error al verificar sesión:', error);
    usuarioActual = null;
  }
}

// =====================================================
// CARGAR PRODUCTOS
// =====================================================

async function cargarProductosDestacados() {
  try {
    const response = await fetch(`${API_BASE}obtenerProductos.php?accion=destacados&limite=6`);
    const result = await response.json();
    
    const container = document.getElementById('productos-destacados');
    
    if (result.success && result.data.productos.length > 0) {
      container.innerHTML = '';
      result.data.productos.forEach(producto => {
        container.appendChild(crearTarjetaProducto(producto));
      });
    } else {
      container.innerHTML = '<p style="text-align:center;color:#999;">No hay productos disponibles</p>';
    }
  } catch (error) {
    console.error('Error al cargar productos:', error);
  }
}

async function cargarTodosLosProductos() {
  try {
    const response = await fetch(`${API_BASE}obtenerProductos.php?accion=listar&limite=50`);
    const result = await response.json();
    
    const container = document.getElementById('grid-productos');
    
    if (result.success && result.data.productos.length > 0) {
      container.innerHTML = '';
      result.data.productos.forEach(producto => {
        container.appendChild(crearTarjetaProducto(producto));
      });
    } else {
      container.innerHTML = '<p style="text-align:center;color:#999;">No hay productos disponibles</p>';
    }
  } catch (error) {
    console.error('Error al cargar productos:', error);
  }
}

async function cargarOfertas() {
  try {
    const response = await fetch(`${API_BASE}obtenerProductos.php?accion=ofertas&limite=20`);
    const result = await response.json();
    
    const container = document.getElementById('productos-ofertas');
    
    if (result.success && result.data.productos.length > 0) {
      container.innerHTML = '';
      result.data.productos.forEach(producto => {
        container.appendChild(crearTarjetaProducto(producto, true));
      });
    } else {
      container.innerHTML = '<p style="text-align:center;color:#999;">No hay ofertas disponibles</p>';
    }
  } catch (error) {
    console.error('Error al cargar ofertas:', error);
  }
}

// =====================================================
// CREAR TARJETA DE PRODUCTO
// =====================================================

function crearTarjetaProducto(producto, mostrarDescuento = false) {
  const div = document.createElement('div');
  div.className = 'tarjeta';
  
  let precioHTML = `<p class="precio">$${Number(producto.precio).toLocaleString()}</p>`;
  
  if (mostrarDescuento && producto.descuento > 0) {
    const precioOriginal = producto.precio / (1 - producto.descuento / 100);
    precioHTML = `
      <span class="descuento">${producto.descuento}% OFF</span>
      <p style="text-decoration:line-through;color:#999;font-size:0.9rem;">$${precioOriginal.toFixed(0)}</p>
      <p class="precio">$${Number(producto.precio).toLocaleString()}</p>
    `;
  }
  
  div.innerHTML = `
    <img src="${producto.imagen_url}" alt="${producto.nombre}" onerror="this.src='https://via.placeholder.com/300?text=Sin+Imagen'">
    <h3>${producto.nombre}</h3>
    <p>${producto.descripcion}</p>
    ${precioHTML}
    <p style="color:#aaa;font-size:0.9rem;">Stock: ${producto.stock}</p>
    <button class="btn" onclick="agregarAlCarrito(${producto.id})">Agregar al Carrito</button>
  `;
  
  return div;
}

// =====================================================
// GESTIÓN DEL CARRITO
// =====================================================

async function agregarAlCarrito(productoId) {
  console.log('🛒 Agregando producto:', productoId);
  
  if (usuarioActual) {
    // Usuario logueado - agregar a BD
    try {
      const formData = new FormData();
      formData.append('producto_id', productoId);
      formData.append('cantidad', 1);
      
      const response = await fetch(`${API_BASE}carrito.php?accion=agregar`, {
        method: 'POST',
        body: formData
      });
      
      const result = await response.json();
      
      if (result.success) {
        mostrarNotificacion('✅ Producto agregado al carrito', 'exito');
        await cargarCarrito();
      } else {
        mostrarNotificacion('❌ ' + result.message, 'error');
      }
    } catch (error) {
      console.error('Error:', error);
      mostrarNotificacion('❌ Error al agregar al carrito', 'error');
    }
  } else {
    // Usuario no logueado - usar localStorage
    const carritoLocal = JSON.parse(localStorage.getItem('carrito') || '[]');
    
    const itemExiste = carritoLocal.find(item => item.producto_id === productoId);
    
    if (itemExiste) {
      itemExiste.cantidad++;
    } else {
      carritoLocal.push({ producto_id: productoId, cantidad: 1 });
    }
    
    localStorage.setItem('carrito', JSON.stringify(carritoLocal));
    mostrarNotificacion('✅ Producto agregado al carrito', 'exito');
    await cargarCarrito();
  }
}

async function cargarCarrito() {
  if (usuarioActual) {
    // Cargar desde BD
    try {
      const response = await fetch(`${API_BASE}carrito.php?accion=obtener`);
      const result = await response.json();
      
      if (result.success) {
        carrito = result.data.items || [];
        renderizarCarrito();
      }
    } catch (error) {
      console.error('Error al cargar carrito:', error);
    }
  } else {
    // Cargar desde localStorage
    const carritoLocal = JSON.parse(localStorage.getItem('carrito') || '[]');
    
    if (carritoLocal.length > 0) {
      // Obtener detalles de productos
      const ids = carritoLocal.map(item => item.producto_id).join(',');
      try {
        const response = await fetch(`${API_BASE}obtenerProductos.php?accion=listar&limite=100`);
        const result = await response.json();
        
        if (result.success) {
          carrito = carritoLocal.map(item => {
            const producto = result.data.productos.find(p => p.id === item.producto_id);
            return {
              ...item,
              nombre: producto?.nombre || 'Producto',
              precio: producto?.precio || 0,
              imagen_url: producto?.imagen_url || ''
            };
          });
          renderizarCarrito();
        }
      } catch (error) {
        console.error('Error:', error);
      }
    } else {
      carrito = [];
      renderizarCarrito();
    }
  }
}

function renderizarCarrito() {
  const contenido = document.getElementById('carritoContenido');
  const total = document.getElementById('carritoTotal');
  const contador = document.getElementById('carritoContador');
  
  if (!contenido) return;
  
  if (carrito.length === 0) {
    contenido.innerHTML = '<p style="text-align:center;color:#999;padding:2em;">El carrito está vacío</p>';
    total.textContent = '$0';
    contador.textContent = '0';
    return;
  }
  
  let totalPrecio = 0;
  contenido.innerHTML = '';
  
  carrito.forEach((item, index) => {
    const subtotal = item.precio * item.cantidad;
    totalPrecio += subtotal;
    
    const div = document.createElement('div');
    div.className = 'carrito-item';
    div.innerHTML = `
      <div>
        <strong>${item.nombre}</strong><br>
        <span style="color:#daa520;">$${Number(item.precio).toLocaleString()}</span> x ${item.cantidad}
        = <strong>$${subtotal.toLocaleString()}</strong>
      </div>
      <button onclick="eliminarDelCarrito(${index})" style="background:#ff4444;border:none;color:#fff;padding:0.5em;border-radius:5px;cursor:pointer;">❌</button>
    `;
    contenido.appendChild(div);
  });
  
  total.textContent = '$' + totalPrecio.toLocaleString();
  contador.textContent = carrito.length;
}

async function eliminarDelCarrito(index) {
  if (usuarioActual) {
    // Eliminar de BD
    const item = carrito[index];
    try {
      const formData = new FormData();
      formData.append('carrito_id', item.id);
      
      const response = await fetch(`${API_BASE}carrito.php?accion=eliminar`, {
        method: 'POST',
        body: formData
      });
      
      const result = await response.json();
      
      if (result.success) {
        await cargarCarrito();
      }
    } catch (error) {
      console.error('Error:', error);
    }
  } else {
    // Eliminar de localStorage
    const carritoLocal = JSON.parse(localStorage.getItem('carrito') || '[]');
    carritoLocal.splice(index, 1);
    localStorage.setItem('carrito', JSON.stringify(carritoLocal));
    await cargarCarrito();
  }
}

async function vaciarCarrito() {
  if (!confirm('¿Vaciar todo el carrito?')) return;
  
  if (usuarioActual) {
    // Vaciar BD
    try {
      const response = await fetch(`${API_BASE}carrito.php?accion=vaciar`, {
        method: 'POST'
      });
      
      const result = await response.json();
      
      if (result.success) {
        await cargarCarrito();
        mostrarNotificacion('🗑️ Carrito vaciado', 'info');
      }
    } catch (error) {
      console.error('Error:', error);
    }
  } else {
    // Vaciar localStorage
    localStorage.removeItem('carrito');
    await cargarCarrito();
    mostrarNotificacion('🗑️ Carrito vaciado', 'info');
  }
}

function toggleCarrito() {
  const panel = document.getElementById('carritoPanel');
  if (panel) {
    panel.classList.toggle('activo');
  }
}

// =====================================================
// PROCESAR COMPRA
// =====================================================

async function procesarCompra() {
  if (!usuarioActual) {
    alert('Debes iniciar sesión para comprar');
    window.location.href = 'login.html';
    return;
  }
  
  if (carrito.length === 0) {
    alert('El carrito está vacío');
    return;
  }
  
  if (!confirm('¿Confirmar compra?')) return;
  
  try {
    const response = await fetch(`${API_BASE}pedidos.php?accion=procesar`, {
      method: 'POST'
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert(`¡Compra exitosa! 🎉\n\nGanaste ${result.data.puntos_ganados} puntos\nTotal de puntos: ${result.data.puntos_totales}\nNivel: ${result.data.nivel}`);
      
      await cargarCarrito();
      toggleCarrito();
      
      // Actualizar puntos
      if (usuarioActual) {
        await verificarSesion();
        actualizarPanelPuntos();
      }
    } else {
      alert('❌ ' + result.message);
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error al procesar la compra');
  }
}

// =====================================================
// PANEL DE PUNTOS
// =====================================================

function actualizarPanelPuntos() {
  if (!usuarioActual) return;
  
  const barra = document.getElementById('barra-progreso');
  const estado = document.getElementById('estado-puntos');
  const menuPuntos = document.getElementById('menu-puntos');
  
  const puntos = usuarioActual.puntos || 0;
  const nivel = usuarioActual.nivel || 'Bronce';
  const progreso = usuarioActual.progreso_nivel || 0;
  
  if (barra) {
    barra.style.width = progreso + '%';
  }
  
  if (estado) {
    estado.textContent = `${puntos} puntos | Nivel ${nivel} (${Math.round(progreso)}%)`;
  }
  
  if (menuPuntos) {
    menuPuntos.textContent = `⭐ ${puntos} pts`;
  }
}

// =====================================================
// FILTROS Y BÚSQUEDA
// =====================================================

async function cargarCategorias() {
  try {
    const response = await fetch(`${API_BASE}obtenerProductos.php?accion=categorias`);
    const result = await response.json();
    
    const select = document.getElementById('selectCategoria');
    if (!select) return;
    
    if (result.success && result.data.categorias) {
      result.data.categorias.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.categoria;
        option.textContent = `${cat.categoria} (${cat.cantidad})`;
        select.appendChild(option);
      });
    }
  } catch (error) {
    console.error('Error:', error);
  }
}

async function aplicarFiltros() {
  const categoria = document.getElementById('selectCategoria')?.value || '';
  const minPrecio = document.getElementById('minPrecio')?.value || 0;
  const maxPrecio = document.getElementById('maxPrecio')?.value || 999999999;
  const orden = document.getElementById('selectOrden')?.value || 'nombre_asc';
  
  try {
    const url = `${API_BASE}obtenerProductos.php?accion=filtrar&categoria=${categoria}&min_precio=${minPrecio}&max_precio=${maxPrecio}&orden=${orden}`;
    const response = await fetch(url);
    const result = await response.json();
    
    const container = document.getElementById('grid-productos');
    
    if (result.success && result.data.productos.length > 0) {
      container.innerHTML = '';
      result.data.productos.forEach(producto => {
        container.appendChild(crearTarjetaProducto(producto));
      });
    } else {
      container.innerHTML = '<p style="text-align:center;color:#999;">No se encontraron productos</p>';
    }
  } catch (error) {
    console.error('Error:', error);
  }
}

function limpiarFiltros() {
  document.getElementById('selectCategoria').value = '';
  document.getElementById('minPrecio').value = '';
  document.getElementById('maxPrecio').value = '';
  document.getElementById('selectOrden').value = 'nombre_asc';
  cargarTodosLosProductos();
}

// Buscador
const buscador = document.getElementById('buscadorProductos');
if (buscador) {
  let timeout;
  buscador.addEventListener('input', (e) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      buscarProductos(e.target.value);
    }, 500);
  });
}

async function buscarProductos(termino) {
  if (!termino || termino.length < 2) {
    cargarTodosLosProductos();
    return;
  }
  
  try {
    const response = await fetch(`${API_BASE}obtenerProductos.php?accion=buscar&termino=${termino}`);
    const result = await response.json();
    
    const container = document.getElementById('grid-productos');
    
    if (result.success && result.data.productos.length > 0) {
      container.innerHTML = '';
      result.data.productos.forEach(producto => {
        container.appendChild(crearTarjetaProducto(producto));
      });
    } else {
      container.innerHTML = '<p style="text-align:center;color:#999;">No se encontraron productos</p>';
    }
  } catch (error) {
    console.error('Error:', error);
  }
}

// =====================================================
// NOTIFICACIONES
// =====================================================

function mostrarNotificacion(mensaje, tipo = 'info') {
  const div = document.createElement('div');
  div.className = `notificacion ${tipo} mostrar`;
  div.textContent = mensaje;
  document.body.appendChild(div);
  
  setTimeout(() => {
    div.classList.remove('mostrar');
    setTimeout(() => div.remove(), 300);
  }, 3000);
}

// =====================================================
// LOG
// =====================================================

console.log('✅ tienda.js cargado correctamente');

// =====================================================
// SISTEMA DE NOTIFICACIONES
// =====================================================

async function cargarNotificaciones() {
  if (!usuarioActual) return;
  
  try {
    const response = await fetch(`${API_BASE}notificaciones.php?accion=obtener&limite=5&no_leidas=1`);
    const result = await response.json();
    
    if (result.success && result.data.notificaciones.length > 0) {
      // Mostrar contador
      const contador = document.getElementById('notificaciones-contador');
      if (contador) {
        contador.textContent = result.data.total_no_leidas;
        contador.style.display = result.data.total_no_leidas > 0 ? 'inline' : 'none';
      }
    }
  } catch (error) {
    console.error('Error al cargar notificaciones:', error);
  }
}

// Cargar notificaciones cada 30 segundos
if (usuarioActual) {
  cargarNotificaciones();
  setInterval(cargarNotificaciones, 30000);
}