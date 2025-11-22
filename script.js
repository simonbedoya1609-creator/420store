/* js/script.js - comportamiento básico (sin backend) */
const CART_KEY = "420_cart_v1";
const POINTS_KEY = "420_points_v1";

function toast(msg, time = 3000) {
  let t = document.getElementById("site-toast");
  if (!t) {
    t = document.createElement("div");
    t.id = "site-toast";
    t.style.cssText = "position:fixed;right:20px;bottom:20px;background:linear-gradient(90deg,#111,#222);color:#fff;padding:12px 18px;border-radius:10px;box-shadow:0 8px 20px rgba(0,0,0,0.6);z-index:9999";
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.style.opacity = "1";
  setTimeout(()=>{ t.style.opacity = "0"; }, time);
}

function getCart() {
  return JSON.parse(localStorage.getItem(CART_KEY) || "[]");
}
function saveCart(cart) {
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
  updateCartDisplay();
}

function addToCart(product) {
  const cart = getCart();
  cart.push(product);
  saveCart(cart);
  // puntos simple
  let points = Number(localStorage.getItem(POINTS_KEY) || 0);
  points += 10;
  localStorage.setItem(POINTS_KEY, points);
  toast(`✅ Añadido: ${product.name} — +10 pts (Total: ${points})`);
}

function updateCartDisplay() {
  const itemsEl = document.getElementById("cartItems");
  const subtotalEl = document.getElementById("subtotal");
  if (!itemsEl) return;
  const cart = getCart();
  if (cart.length === 0) {
    itemsEl.innerHTML = "<p>No hay artículos en el carrito — añade productos desde la tienda.</p>";
    subtotalEl && (subtotalEl.textContent = "0");
    return;
  }
  let html = "<ul style='list-style:none;padding:0;'>";
  let sum = 0;
  cart.forEach((it, idx) => {
    html += `<li style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.03)">
      <div><strong>${it.name}</strong><div style="font-size:.9rem;color:#bbb">${it.desc || ""}</div></div>
      <div style="text-align:right"><div>${it.price}</div><button onclick="removeFromCart(${idx})" style="margin-top:6px;padding:6px 10px;border-radius:6px;background:#333;border:none;color:#fff;cursor:pointer">Eliminar</button></div>
    </li>`;
    sum += Number(String(it.price).replace(/[^\d.-]/g,"")) || 0;
  });
  html += "</ul>";
  itemsEl.innerHTML = html;
  subtotalEl && (subtotalEl.textContent = `$${sum.toLocaleString()}`);
}

function removeFromCart(index) {
  const cart = getCart();
  cart.splice(index,1);
  saveCart(cart);
  toast("Artículo eliminado del carrito");
}

/* ligar botones dinámicos en páginas con tarjetas */
document.addEventListener("click", (e) => {
  if (e.target.matches(".producto-card .add, .producto-card .btn.add, .btn.add")) {
    // intentar obtener datos desde el DOM
    const card = e.target.closest(".producto-card");
    if (card) {
      const name = card.querySelector("h3") ? card.querySelector("h3").textContent : "Producto";
      const desc = card.querySelector(".desc") ? card.querySelector(".desc").textContent : "";
      const price = card.querySelector(".price") ? card.querySelector(".price").textContent : "$0";
      addToCart({name, desc, price});
    } else {
      addToCart({name:"Producto demo", price:"$0", desc:""});
    }
  }
});

/* iniciar estado carrito en carga */
document.addEventListener("DOMContentLoaded", () => {
  updateCartDisplay();
  // manejo del formulario de contacto: demo
  const contact = document.getElementById("contactForm");
  if (contact) {
    contact.addEventListener("submit", (ev) => {
      ev.preventDefault();
      toast("Mensaje enviado. Gracias ✉️");
      contact.reset();
    });
  }
  const checkoutBtn = document.getElementById("checkoutBtn");
  if (checkoutBtn) {
    checkoutBtn.addEventListener("click", () => {
      const cart = getCart();
      if (cart.length === 0) { toast("El carrito está vacío"); return; }
      // simulación de pago
      localStorage.removeItem(CART_KEY);
      toast("Compra simulada realizada ✅ ¡Gracias por tu compra!");
      updateCartDisplay();
    });
  }
});
