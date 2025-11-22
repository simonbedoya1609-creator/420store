/**
 * productos-dinamicos.js - VERSIÓN CORREGIDA
 */

const API_BASE = 'php/';

async function cargarProductos(contenedor, opciones = {}) {
  const {
    accion = 'listar',
    categoria = '',
    limite = 100,
    busqueda = ''
  } = opciones;
  
  console.log('🔄 Cargando productos...', { accion, contenedor });
  
  try {
    let url = `${API_BASE}obtenerProductos.php?accion=${accion}&limite=${limite}`;
    
    if (categoria) url += `&categoria=${encodeURIComponent(categoria)}`;
    if (busqueda) url += `&busqueda=${encodeURIComponent(busqueda)}`;
    
    console.log('📡 Petición a:', url);
    
    const response = await fetch(url);
    console.log('📥 Respuesta status:', response.status);
    
    const result = await response.json();
    console.log('✅ Datos recibidos:', result);
    
    if (result.success && result.data && result.data.productos) {
      console.log(`✅ ${result.data.productos.length} productos encontrados`);
      renderizarProductos(contenedor, result.data.productos);
    } else {
      console.error('❌ Error:', result.message);
      mostrarMensaje(contenedor, result.message || 'No se encontraron productos');
    }
  } catch (error) {
    console.error('❌ Error al cargar productos:', error);
    mostrarMensaje(contenedor, `Error: ${error.message}`);
  }
}

function renderizarProductos(contenedorId, productos) {
  const contenedor = document.getElementById(contenedorId);
  
  if (!contenedor) {
    console.error('❌ Contenedor NO encontrado:', contenedorId);
    return;
  }
  
  console.log('🎨 Renderizando en:', contenedorId, 'productos:', productos.length);
  
  contenedor.innerHTML = '';
  
  if (productos.length === 0) {
    contenedor.innerHTML = '<p style="text-align:center;color:#999;padding:3em;">No hay productos disponibles</p>';
    return;
  }
  
  productos.forEach(producto => {
    const tarjeta = crearTarjetaProducto(producto);
    contenedor.appendChild(tarjeta);
  });
  
  console.log('✅ Productos renderizados correctamente');
}

function crearTarjetaProducto(producto) {
  const div = document.createElement('div');
  div.className = 'tarjeta';
  
  const descuento = Number(producto.descuento) || 0;
  const precio = Number(producto.precio) || 0;
  
  let precioOriginal = precio;
  if (descuento > 0) {
    precioOriginal = (precio / (1 - descuento / 100)).toFixed(0);
  }
  
  const descuentoHTML = descuento > 0 
    ? `<span class="descuento">-${descuento}%</span>` 
    : '';
  
  const precioHTML = descuento > 0
    ? `<p class="precio">$${formatearPrecio(precio)} COP <del>$${formatearPrecio(precioOriginal)}</del></p>`
    : `<p class="precio">$${formatearPrecio(precio)} COP</p>`;
  
  const stock = Number(producto.stock) || 0;
  const stockHTML = stock <= 0 
    ? '<p style="color:#ff4444;font-size:0.9rem;">Agotado</p>'
    : stock < 5 
    ? `<p style="color:#ff9800;font-size:0.9rem;">Solo quedan ${stock}</p>`
    : '';
  
  div.innerHTML = `
    ${descuentoHTML}
    <img src="${escapeHtml(producto.imagen_url)}" 
         alt="${escapeHtml(producto.nombre)}" 
         onerror="this.src='https://via.placeholder.com/300x300?text=Sin+Imagen'">
    <h3>${escapeHtml(producto.nombre)}</h3>
    <p style="font-size:0.9rem;color:#999;margin:0.5em 0;">${escapeHtml(producto.descripcion || '')}</p>
    ${precioHTML}
    ${stockHTML}
    <button class="btn" 
            onclick="agregarAlCarrito('${escapeHtml(producto.nombre)}', ${precio}, '${escapeHtml(producto.imagen_url)}', ${producto.id})"
            ${stock <= 0 ? 'disabled' : ''}>
      ${stock <= 0 ? 'Agotado' : 'Agregar al Carrito'}
    </button>
  `;
  
  return div;
}

async function cargarProductosDestacados(contenedorId, limite = 6) {
  console.log('⭐ Cargando productos destacados...');
  await cargarProductos(contenedorId, { accion: 'destacados', limite });
}

async function cargarOfertas(contenedorId, limite = 10) {
  console.log('🔥 Cargando ofertas...');
  await cargarProductos(contenedorId, { accion: 'ofertas', limite });
}

async function buscarProductos(termino, contenedorId) {
  if (!termino || termino.trim().length < 2) {
    mostrarMensaje(contenedorId, 'Escribe al menos 2 caracteres para buscar');
    return;
  }
  
  try {
    const url = `${API_BASE}obtenerProductos.php?accion=buscar&termino=${encodeURIComponent(termino)}`;
    const response = await fetch(url);
    const result = await response.json();
    
    if (result.success && result.data.productos) {
      renderizarProductos(contenedorId, result.data.productos);
      
      if (typeof mostrarNotificacionPersonalizada === 'function') {
        const mensaje = result.data.total > 0 
          ? `Se encontraron ${result.data.total} productos`
          : 'No se encontraron productos';
        mostrarNotificacionPersonalizada('info', mensaje);
      }
    }
  } catch (error) {
    console.error('Error en búsqueda:', error);
  }
}

async function filtrarProductos(contenedorId, filtros = {}) {
  const {
    min_precio = 0,
    max_precio = 999999999,
    categoria = '',
    orden = 'nombre_asc'
  } = filtros;
  
  try {
    let url = `${API_BASE}obtenerProductos.php?accion=filtrar`;
    url += `&min_precio=${min_precio}`;
    url += `&max_precio=${max_precio}`;
    url += `&orden=${orden}`;
    if (categoria) url += `&categoria=${encodeURIComponent(categoria)}`;
    
    const response = await fetch(url);
    const result = await response.json();
    
    if (result.success && result.data.productos) {
      renderizarProductos(contenedorId, result.data.productos);
    }
  } catch (error) {
    console.error('Error al filtrar:', error);
  }
}

async function cargarCategorias(selectId) {
  try {
    const response = await fetch(`${API_BASE}obtenerProductos.php?accion=categorias`);
    const result = await response.json();
    
    if (result.success && result.data.categorias) {
      const select = document.getElementById(selectId);
      if (!select) return;
      
      select.innerHTML = '<option value="">Todas las categorías</option>';
      
      result.data.categorias.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.categoria;
        option.textContent = `${cat.categoria} (${cat.cantidad})`;
        select.appendChild(option);
      });
    }
  } catch (error) {
    console.error('Error al cargar categorías:', error);
  }
}

function mostrarMensaje(contenedorId, mensaje) {
  const contenedor = document.getElementById(contenedorId);
  if (!contenedor) return;
  
  contenedor.innerHTML = `
    <div style="text-align:center;color:#ff4444;padding:3em;border:2px solid #ff4444;border-radius:10px;background:#1a0000;">
      ${mensaje}
    </div>
  `;
}

function formatearPrecio(precio) {
  return Number(precio).toLocaleString();
}

function escapeHtml(str) {
  if (!str) return '';
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

function inicializarBusqueda(inputId, contenedorId) {
  const input = document.getElementById(inputId);
  if (!input) return;
  
  let timeout = null;
  
  input.addEventListener('input', (e) => {
    clearTimeout(timeout);
    const termino = e.target.value.trim();
    
    if (termino.length < 2) {
      cargarProductos(contenedorId);
      return;
    }
    
    timeout = setTimeout(() => {
      buscarProductos(termino, contenedorId);
    }, 500);
  });
}

function inicializarFiltros(formId, contenedorId) {
  const form = document.getElementById(formId);
  if (!form) return;
  
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    
    const formData = new FormData(form);
    const filtros = {
      min_precio: formData.get('min_precio') || 0,
      max_precio: formData.get('max_precio') || 999999999,
      categoria: formData.get('categoria') || '',
      orden: formData.get('orden') || 'nombre_asc'
    };
    
    filtrarProductos(contenedorId, filtros);
  });
}

// Exponer funciones globalmente
window.cargarProductos = cargarProductos;
window.cargarProductosDestacados = cargarProductosDestacados;
window.cargarOfertas = cargarOfertas;
window.buscarProductos = buscarProductos;
window.filtrarProductos = filtrarProductos;
window.cargarCategorias = cargarCategorias;
window.inicializarBusqueda = inicializarBusqueda;
window.inicializarFiltros = inicializarFiltros;