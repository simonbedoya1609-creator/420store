let puntos = 0;
let carrito = [];

function mostrarNotificacion(mensaje, color="#4caf50") {
  let notif = document.getElementById("notificacion");
  notif.innerText = mensaje;
  notif.style.background = color;
  notif.style.display = "block";
  setTimeout(() => { notif.style.display = "none"; }, 3000);
}

function actualizarCarrito() {
  let lista = document.getElementById("listaCarrito");
  lista.innerHTML = "";
  let total = 0;
  carrito.forEach(item => {
    lista.innerHTML += `<div>${item.nombre} - $${item.precio}</div>`;
    total += item.precio;
  });
  document.getElementById("total").innerText = total;
}

function agregarCarrito(nombre, precio) {
  carrito.push({nombre, precio});
  actualizarCarrito();
  mostrarNotificacion(`${nombre} agregado al carrito 🛒`);
}

function finalizarCompra() {
  if (carrito.length === 0) {
    mostrarNotificacion("El carrito está vacío ❌", "#f44336");
    return;
  }
  let totalCompra = carrito.reduce((sum, item) => sum + item.precio, 0);
  puntos += carrito.length * 10;
  document.getElementById("puntos").innerText = "Puntos acumulados: " + puntos;
  mostrarNotificacion(`Compra realizada ✅ Total: $${totalCompra} | +${carrito.length*10} puntos`);
  carrito = [];
  actualizarCarrito();
}

// abrir/cerrar carrito
document.getElementById("abrirCarrito").onclick = () => {
  document.getElementById("carrito").classList.toggle("active");
};
