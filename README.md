# 🌿 420 STORE - Tienda Deportiva Premium

> Sistema completo de e-commerce con gamificación, sistema de puntos y panel administrativo

[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)

---

## 📋 Tabla de Contenidos

- [Características](#-características)
- [Demo](#-demo)
- [Tecnologías](#️-tecnologías-utilizadas)
- [Instalación](#-instalación)
- [Configuración](#️-configuración)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Usuarios de Prueba](#-usuarios-de-prueba)
- [Funcionalidades](#-funcionalidades-principales)
- [API Endpoints](#-api-endpoints)
- [Capturas de Pantalla](#-capturas-de-pantalla)
- [Roadmap](#-roadmap)
- [Contribución](#-contribución)
- [Licencia](#-licencia)
- [Autor](#-autor)

---

## ✨ Características

### 🛒 **E-commerce Completo**
- Catálogo de productos con búsqueda y filtros
- Carrito de compras persistente (BD + LocalStorage)
- Sistema de checkout y procesamiento de pedidos
- Gestión de stock automática

### 🎮 **Sistema de Gamificación**
- Sistema de puntos por compras
- 4 niveles de usuario: **Bronce**, **Plata**, **Oro**, **Diamante**
- Bonificaciones según nivel
- Barra de progreso visual

### 👥 **Multi-usuario**
- **Clientes:** Pueden comprar y acumular puntos
- **Vendedores:** Pueden agregar productos (en desarrollo)
- **Administradores:** Control total del sistema

### 📊 **Panel de Administración**
- Dashboard con estadísticas en tiempo real
- CRUD completo de productos
- Gestión de usuarios y permisos
- Control de pedidos y estados
- Sistema de notificaciones

### 🔔 **Sistema de Notificaciones**
- Notificaciones en tiempo real
- Alertas de compras, puntos y nivel
- Notificaciones persistentes en BD

### 🎨 **Diseño Premium**
- Tema oscuro con acentos dorados
- Animaciones suaves y transiciones
- Diseño responsive
- Efectos visuales modernos

---

## 🎯 Demo

### 🌐 URL del Proyecto
```
http://localhost/420store
```

### 📸 Preview
*(Agregar screenshots después de hacer deploy)*

---

## 🛠️ Tecnologías Utilizadas

### **Frontend**
- HTML5
- CSS3 (Custom design + Animations)
- JavaScript (Vanilla ES6+)
- Fetch API

### **Backend**
- PHP 8.x
- PDO para conexión a BD
- Arquitectura MVC simplificada
- RESTful API endpoints

### **Base de Datos**
- MySQL 8.0
- Stored procedures (triggers)
- Relaciones normalizadas

### **Servidor**
- Apache 2.4
- XAMPP (desarrollo local)

### **Herramientas**
- Git & GitHub
- phpMyAdmin
- VS Code

---

## 📦 Instalación

### **Requisitos Previos**

- **XAMPP** 8.0 o superior (PHP 8.x + MySQL 8.0)
- **Git** (opcional)
- Navegador moderno (Chrome, Firefox, Edge)

### **Paso 1: Clonar el repositorio**

```bash
# Clonar con Git
git clone https://github.com/TU-USUARIO/420-store.git

# O descargar ZIP y extraer
```

### **Paso 2: Mover a htdocs**

```bash
# Mover la carpeta a:
C:\xampp\htdocs\420store
```

### **Paso 3: Crear la base de datos**

1. Abre **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Crea una nueva base de datos llamada: `tienda_deportiva_420`
3. Importa el archivo `database.sql` (incluido en el repositorio)
4. O copia y ejecuta el script SQL proporcionado en `docs/database.sql`

### **Paso 4: Configurar conexión**

Edita `php/conexion.php` si es necesario:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tienda_deportiva_420');
define('DB_USER', 'root');
define('DB_PASS', ''); // Dejar vacío en XAMPP por defecto
```

### **Paso 5: Iniciar XAMPP**

1. Abre el **Panel de Control de XAMPP**
2. Inicia **Apache** y **MySQL**

### **Paso 6: Acceder al proyecto**

```
http://localhost/420store
```

---

## ⚙️ Configuración

### **Variables de Entorno**

Las configuraciones principales están en:
- `php/conexion.php` - Configuración de BD
- Zona horaria: `America/Bogota`

### **Crear Administrador**

Si no se creó automáticamente, ejecuta en phpMyAdmin:

```sql
INSERT INTO administradores (nombre, email, password, nivel, estado) VALUES
('Admin Principal', 'admin@420store.com', '$2y$12$...hash...', 'superadmin', 'activo');
```

O usa el script `generar_hash.php` (incluido) para generar un hash seguro.

---

## 📁 Estructura del Proyecto

```
420store/
├── index.html              # Página principal
├── productos.html          # Catálogo de productos
├── ofertas.html           # Página de ofertas
├── carrito.html           # Carrito de compras
├── login.html             # Login multi-usuario
├── perfil.html            # Perfil de usuario
├── admin.html             # Panel de administración
├── registrarusuario.html  # Registro de clientes
├── registrar_Vendedor.html # Registro de vendedores
├── contacto.html          # Formulario de contacto
│
├── css/
│   └── estilos.css        # Estilos principales (Premium)
│
├── js/
│   ├── tienda.js          # Lógica principal
│   └── verificar-sesion-global.js
│
├── php/
│   ├── conexion.php       # Conexión a BD
│   ├── login.php          # Autenticación
│   ├── logout.php         # Cerrar sesión
│   ├── registrarUsuario.php
│   ├── registrarVendedor.php
│   ├── verificar_sesion.php
│   ├── obtenerProductos.php  # API de productos
│   ├── carrito.php        # API del carrito
│   ├── pedidos.php        # Procesar compras
│   ├── admin.php          # Panel admin
│   └── notificaciones.php
│
├── docs/
│   └── database.sql       # Script de la BD
│
├── .gitignore
└── README.md
```

---

## 👤 Usuarios de Prueba

### **Cliente/Usuario**
```
Email: test@test.com
Contraseña: test123
Puntos: 350
Nivel: Plata
```

### **Administrador**
```
Email: admin@420store.com
Contraseña: admin123
Permisos: Control total
```

### **Vendedor**
```
Email: simónbedoya1609@gmail.com
Contraseña: Star1609
Permisos: Gestión de productos
```

---

## 🚀 Funcionalidades Principales

### **Para Clientes**
- ✅ Registro e inicio de sesión
- ✅ Explorar catálogo con filtros
- ✅ Búsqueda de productos
- ✅ Agregar al carrito
- ✅ Realizar compras
- ✅ Ganar puntos y subir de nivel
- ✅ Historial de pedidos
- ✅ Notificaciones personalizadas

### **Para Administradores**
- ✅ Dashboard con estadísticas
- ✅ CRUD de productos
- ✅ Gestión de usuarios
- ✅ Control de pedidos
- ✅ Cambiar estados de pedidos
- ✅ Bloquear/activar usuarios
- ✅ Ver reportes de ventas

### **Sistema de Gamificación**
- ✅ **Bronce** (0-99 pts): 5 pts por compra
- ✅ **Plata** (100-499 pts): 10 pts por compra
- ✅ **Oro** (500-999 pts): 15 pts por compra
- ✅ **Diamante** (1000+ pts): 20 pts por compra

---

## 🔌 API Endpoints

### **Productos**
```
GET  /php/obtenerProductos.php?accion=listar
GET  /php/obtenerProductos.php?accion=destacados&limite=6
GET  /php/obtenerProductos.php?accion=ofertas
GET  /php/obtenerProductos.php?accion=buscar&termino=nike
GET  /php/obtenerProductos.php?accion=obtener&id=1
```

### **Carrito**
```
GET  /php/carrito.php?accion=obtener
POST /php/carrito.php?accion=agregar
POST /php/carrito.php?accion=eliminar
POST /php/carrito.php?accion=vaciar
```

### **Pedidos**
```
POST /php/pedidos.php?accion=procesar
GET  /php/pedidos.php?accion=historial
GET  /php/pedidos.php?accion=detalle&pedido_id=1
```

### **Administración**
```
GET  /php/admin.php?accion=estadisticas
GET  /php/admin.php?accion=listar_usuarios
GET  /php/admin.php?accion=listar_pedidos
POST /php/admin.php?accion=agregar_producto
POST /php/admin.php?accion=editar_producto
POST /php/admin.php?accion=eliminar_producto
```

## 🗓️ Roadmap

### **✅ Completado**
- [x] Sistema de autenticación
- [x] CRUD de productos
- [x] Carrito de compras
- [x] Sistema de gamificación
- [x] Panel de administración
- [x] Notificaciones
- [x] Diseño responsive

### **🔨 En Desarrollo**
- [ ] Pasarela de pago real (MercadoPago/PayU)
- [ ] Sistema de reseñas de productos
- [ ] Wishlist / Lista de deseos
- [ ] Comparador de productos

### **🔮 Futuro**
- [ ] Chat en vivo
- [ ] Sistema de cupones
- [ ] Envío de emails (confirmación de pedidos)
- [ ] Recuperación de contraseña
- [ ] Dashboard con gráficos (Chart.js)
- [ ] Exportar reportes (PDF/Excel)
- [ ] Subida de imágenes de productos
- [ ] Multi-idioma (i18n)

---

## 🤝 Contribución

Las contribuciones son bienvenidas. Para cambios importantes:

1. Fork el proyecto
2. Crea una rama (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add: nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

---

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver `LICENSE` para más detalles.

---

## 👨‍💻 Autor

**[Simón Bedoya Londoño, Cristian David Velez, Samuel Lopez Marulanda]**

- GitHub: [@TU-USUARIO](https://github.com/simonbedoya1609-creator)
- LinkedIn: [Tu LinkedIn](https://linkedin.com/in/tu-simonbedoya1609-creator)
- Email: simonbedoya1609@gmail.com

---

## 🙏 Agradecimientos

- Inspiración en tiendas modernas de e-commerce
- Comunidad de desarrolladores PHP
- [Unsplash](https://unsplash.com) por las imágenes de productos
- Familia y amigos por el apoyo

---

## 📞 Soporte

Si tienes problemas o preguntas:

1. Revisa la [documentación](#)
2. Abre un [Issue](https://github.com/simonbedoya1609-creator/420-store/issues)
3. Contacta al autor

---

<div align="center">

### ⭐ Si te gustó este proyecto, dale una estrella en GitHub

**Hecho con ❤️ y mucho ☕**
