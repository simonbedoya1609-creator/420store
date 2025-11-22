/**
 * =====================================================
 * ARCHIVO: verificar-sesion-global.js
 * DESCRIPCIÓN: Verifica sesión y actualiza navegación en todas las páginas
 * PROYECTO: 420 Store
 * =====================================================
 */

// Verificar sesión y actualizar navegación
async function actualizarNavegacionUsuario() {
  try {
    const response = await fetch('php/verificar_sesion.php');
    const result = await response.json();
    
    // Buscar el link de navegación que dice "Ingresar"
    const navLinks = document.querySelectorAll('nav a');
    let loginLink = null;
    
    navLinks.forEach(link => {
      if (link.textContent.includes('Ingresar') || link.href.includes('login.html')) {
        loginLink = link;
      }
    });
    
    // Buscar span de puntos en el menú
    const menuPuntos = document.getElementById('menu-puntos');
    
    if (result.success && result.data.usuario) {
      const usuario = result.data.usuario;
      
      // Cambiar link de "Ingresar" por nombre del usuario
      if (loginLink) {
        loginLink.textContent = usuario.nombre;
        loginLink.href = 'perfil.html';
        loginLink.classList.add('usuario-logueado');
        
        // Agregar botón de cerrar sesión después del nombre
        if (!document.getElementById('btn-logout-nav')) {
          const logoutBtn = document.createElement('a');
          logoutBtn.id = 'btn-logout-nav';
          logoutBtn.href = 'php/logout.php';
          logoutBtn.textContent = 'Cerrar Sesión';
          logoutBtn.style.cssText = 'color:#ff4444;margin-left:1em;';
          loginLink.after(logoutBtn);
        }
      }
      
      // Actualizar puntos en el menú
      if (menuPuntos) {
        menuPuntos.textContent = `⭐ ${usuario.puntos} pts`;
        menuPuntos.style.display = 'inline';
      }
      
      console.log('✅ Usuario logueado:', usuario.nombre);
      
    } else {
      // No hay sesión activa
      if (loginLink) {
        loginLink.textContent = 'Ingresar';
        loginLink.href = 'login.html';
        loginLink.classList.remove('usuario-logueado');
      }
      
      // Ocultar puntos
      if (menuPuntos) {
        menuPuntos.style.display = 'none';
      }
      
      // Eliminar botón de logout si existe
      const logoutBtn = document.getElementById('btn-logout-nav');
      if (logoutBtn) {
        logoutBtn.remove();
      }
      
      console.log('ℹ️ No hay sesión activa');
    }
  } catch (error) {
    console.error('Error al verificar sesión:', error);
  }
}

// Ejecutar cuando cargue la página
document.addEventListener('DOMContentLoaded', () => {
  actualizarNavegacionUsuario();
});

// Exportar función para uso en otros scripts
if (typeof window !== 'undefined') {
  window.actualizarNavegacionUsuario = actualizarNavegacionUsuario;
}