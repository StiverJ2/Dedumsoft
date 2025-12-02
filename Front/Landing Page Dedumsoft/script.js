



// Dropdown functionality
document.querySelectorAll(".dropdown-btn").forEach(btn => {
    btn.addEventListener("click", function () {
        document.querySelectorAll(".dropdown-btn").forEach(other => {
            if (other !== this) {
                other.classList.remove("active");
                other.nextElementSibling.style.display = "none";
            }
        });
        this.classList.toggle("active");
        let dropdown = this.nextElementSibling;
        dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    });
});

// Contenido dinámico
const content = {
  inicio: `
<div class="main-content">
    <div class="content-header">
        <h1>Bienvenido a Joyas Van</h1>
        <p>Lujo en Cada Detalle</p>
    </div>
    <br>
    <b>
    <br>
    <div class="dashboard-grid">
        <div class="dashboard-card gold">
            <div class="card-icon">💎</div>
            <h3>Inventario Actual</h3>
            <p class="stat">0 items</p>
            <div class="card-footer">
                <span>0%</span>
            </div>
        </div>
        
        <div class="dashboard-card silver">
            <div class="card-icon">💰</div>
            <h3>Ventas del Mes</h3>
            <p class="stat">$0</p>
            <div class="card-footer">
                <span>0% mes anterior</span>
            </div>
        </div>
        
        <div class="dashboard-card bronze">
            <div class="card-icon">📈</div>
            <h3>Órdenes Activas</h3>
            <p class="stat">0</p>
            <div class="card-footer">
                <span>0 Refabricado</span>
            </div>
        </div>
        
        <div class="dashboard-card pearl">
            <div class="card-icon">✅</div>
            <h3>Ordenes Completadas</h3>
            <p class="stat">0</p>
            <div class="card-footer">
                <span>en este mes</span>
            </div>
        </div>
    </div>
    
    <div class="recent-section">
        <h2>Actividad Reciente</h2>
        <div class="activity-list">
            <div class="activity-item">
                <div class="activity-icon">➕</div>
                <div class="activity-content">
                    <p>No hay pedido registrados</p>
                    <span class="activity-time">Hace 0 minutos</span>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-icon">📦</div>
                <div class="activity-content">
                    <p>Stock Actualizado</p>
                    <span class="activity-time">Hace 0 minutos</span>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-icon">✅</div>
                <div class="activity-content">
                    <p>No Hay Pedido Completados</p>
                    <span class="activity-time">Hace 0 minutos</span>
                </div>
            </div>
        </div>
    </div>
</div>
  `,
  equipos: `
    <div class="content-header">
        <h1>Gestión de Herramientas</h1>
        <p>Control de herramientas de taller y equipamiento</p>
    </div>
    <div class="grid-container">
        <button class="grid-item inv-1">Herramientas Disponibles</button>
        <button class="grid-item inv-2">Registrar Nueva Herramienta</button>
        <button class="grid-item inv-3">Actualizar Información</button>
        <button class="grid-item inv-4">Salida / Baja de Herramienta</button>
    </div>
  `,
  material: `
    <div class="content-header">
        <h1>Gestión de Metales</h1>
        <p>Control de oro, plata y otros metales preciosos</p>
    </div>
    <div class="grid-container">
        <button class="grid-item inv-1">Metales en Almacén</button>
        <button class="grid-item inv-2">Registrar Nuevo Metal</button>
        <button class="grid-item inv-3">Actualizar Stock</button>
        <button class="grid-item inv-4">Retirar Metal</button>
    </div>
  `,
  consumibles: `
    <div class="content-header">
        <h1>Gestión de Piedras Preciosas</h1>
        <p>Control de gemas, diamantes y piedras varias</p>
    </div>
    <div class="grid-container">
        <button class="grid-item inv-1">Piedras Disponibles</button>
        <button class="grid-item inv-2">Ingresar Nueva Piedra</button>
        <button class="grid-item inv-3">Actualizar Cantidades</button>
        <button class="grid-item inv-4">Salida de Piedras</button>
    </div>
  `,
  categorias: `
    <div class="content-header">
        <h1>Categorías</h1>
        <p>Organización de productos por categorías</p>
    </div>
    <div class="grid-container">
        <button class="grid-item inv-1">Ver Categorías</button>
        <button class="grid-item inv-2">Crear Categoría</button>
        <button class="grid-item inv-3">Editar Categoría</button>
        <button class="grid-item inv-4">Eliminar Categoría</button>
    </div>
  `,
  proveedores: `
    <div class="content-header">
        <h1>Proveedores</h1>
        <p>Gestión de proveedores de materiales</p>
    </div>
    <div class="grid-container">
        <button class="grid-item inv-1">Lista de Proveedores</button>
        <button class="grid-item inv-2">Registrar Proveedor</button>
        <button class="grid-item inv-3">Actualizar Información</button>
        <button class="grid-item inv-4">Eliminar Proveedor</button>
    </div>
  `,
  // === Gestión ===
  "rol-actividad": `
    <div class="content-header">
        <h1>Rol de la Actividad</h1>
        <p>Gestión de roles y responsabilidades</p>
    </div>
    <div class="grid-container">
        <button class="grid-item ges-1">Crear Rol</button>
        <button class="grid-item ges-2">Asignar Rol</button>
        <button class="grid-item ges-3">Modificar Rol</button>
        <button class="grid-item ges-1">Eliminar Rol</button>
    </div>
  `,
  "asignacion-tareas": `
    <div class="content-header">
        <h1>Asignación de Tareas</h1>
        <p>Distribución de tareas en el taller</p>
    </div>
    <div class="grid-container">
        <button class="grid-item ges-1">Crear Tarea</button>
        <button class="grid-item ges-2">Asignar Tarea</button>
        <button class="grid-item ges-3">Actualizar Estado</button>
        <button class="grid-item ges-1">Eliminar Tarea</button>
    </div>
  `,
  "agenda": `
    <div class="content-header">
        <h1>Agenda</h1>
        <p>Planificación de eventos y entregas</p>
    </div>
    <div class="grid-container">
        <button class="grid-item ges-1">Crear Evento</button>
        <button class="grid-item ges-2">Ver Agenda</button>
        <button class="grid-item ges-3">Actualizar Evento</button>
        <button class="grid-item ges-1">Eliminar Evento</button>
    </div>
  `,
  // === Administración ===
  usuarios: `
    <div class="content-header">
        <h1>Usuarios</h1>
        <p>Gestión de usuarios del sistema</p>
    </div>
    <div class="grid-container">
        <button class="grid-item adm-1">Lista de Usuarios</button>
        <button class="grid-item adm-2">Registrar Usuario</button>
        <button class="grid-item adm-3">Actualizar Usuario</button>
        <button class="grid-item adm-4">Eliminar Usuario</button>
    </div>
  `,
  grupos: `
    <div class="content-header">
        <h1>Grupos</h1>
        <p>Gestión de grupos de trabajo</p>
    </div>
    <div class="grid-container">
        <button class="grid-item adm-1">Lista de Grupos</button>
        <button class="grid-item adm-2">Crear Grupo</button>
        <button class="grid-item adm-3">Actualizar Grupo</button>
        <button class="grid-item adm-4">Eliminar Grupo</button>
    </div>
  `,
  perfiles: `
    <div class="content-header">
        <h1>Perfiles</h1>
        <p>Gestión de perfiles de usuario</p>
    </div>
    <div class="grid-container">
        <button class="grid-item adm-1">Ver Perfiles</button>
        <button class="grid-item adm-2">Crear Perfil</button>
        <button class="grid-item adm-3">Editar Perfil</button>
        <button class="grid-item adm-4">Eliminar Perfil</button>
    </div>
  `,
  permisos: `
    <div class="content-header">
        <h1>Permisos</h1>
        <p>Gestión de permisos del sistema</p>
    </div>
    <div class="grid-container">
        <button class="grid-item adm-1">Lista de Permisos</button>
        <button class="grid-item adm-2">Asignar Permisos</button>
        <button class="grid-item adm-3">Modificar Permisos</button>
        <button class="grid-item adm-4">Eliminar Permisos</button>
    </div>
  `,
  // === Ajustes ===
  "ajustes-general": `
    <div class="content-header">
        <h1>Ajustes Generales</h1>
        <p>Configuración general del sistema</p>
    </div>
    <div class="grid-container">
        <button class="grid-item ajs-1">Configuración del Sistema</button>
        <button class="grid-item ajs-2">Parámetros Globales</button>
        <button class="grid-item ajs-3">Personalización</button>
        <button class="grid-item ajs-1">Idioma y Zona Horaria</button>
    </div>
  `,
  "ajustes-cuenta": `
    <div class="content-header">
        <h1>Ajustes de Cuenta</h1>
        <p>Configuración de tu cuenta de usuario</p>
    </div>
    <div class="grid-container">
        <button class="grid-item ajs-1">Perfil de Usuario</button>
        <button class="grid-item ajs-2">Cambiar Contraseña</button>
        <button class="grid-item ajs-3">Preferencias</button>
        <button class="grid-item ajs-1">Notificaciones</button>
    </div>
  `,
  "ajustes-auth": `
    <div class="content-header">
        <h1>Autenticación</h1>
        <p>Configuración de seguridad y acceso</p>
    </div>
    <div class="grid-container">
        <button class="grid-item ajs-1">Configurar 2FA</button>
        <button class="grid-item ajs-2">Sesiones Activas</button>
        <button class="grid-item ajs-3">Historial de Accesos</button>
        <button class="grid-item ajs-1">Bloqueo de Seguridad</button>
    </div>
  `,
  // === Reportes ===
  "reporte-inventario": `
    <div class="content-header">
        <h1>Reporte de Inventario</h1>
        <p>Reportes detallados de inventario</p>
    </div>
    <div class="grid-container">
        <button class="grid-item rep-1">Inventario Actual</button>
        <button class="grid-item rep-2">Histórico de Inventario</button>
        <button class="grid-item rep-3">Stock Crítico</button>
        <button class="grid-item rep-4">Exportar a Excel</button>
    </div>
  `,
  "reporte-ventas": `
    <div class="content-header">
        <h1>Reporte de Ventas</h1>
        <p>Análisis y reportes de ventas</p>
    </div>
    <div class="grid-container">
        <button class="grid-item rep-1">Ventas del Día</button>
        <button class="grid-item rep-2">Ventas Mensuales</button>
        <button class="grid-item rep-3">Top Productos Vendidos</button>
        <button class="grid-item rep-4">Exportar a PDF</button>
    </div>
  `,
  "reporte-compras": `
    <div class="content-header">
        <h1>Reporte de Compras</h1>
        <p>Reportes de compras y adquisiciones</p>
    </div>
    <div class="grid-container">
        <button class="grid-item rep-1">Compras Recientes</button>
        <button class="grid-item rep-2">Compras por Proveedor</button>
        <button class="grid-item rep-3">Compras por Categoría</button>
        <button class="grid-item rep-4">Exportar a Excel</button>
    </div>
  `,
  "reporte-usuarios": `
    <div class="content-header">
        <h1>Reporte de Usuarios</h1>
        <p>Reportes de actividad de usuarios</p>
    </div>
    <div class="grid-container">
        <button class="grid-item rep-1">Usuarios Activos</button>
        <button class="grid-item rep-2">Historial de Sesiones</button>
        <button class="grid-item rep-3">Roles y Permisos</button>
        <button class="grid-item rep-4">Exportar Informe</button>
    </div>
  `,
  // === Soporte ===
  "ayuda": `
    <div class="content-header">
        <h1>Centro de Ayuda</h1>
        <p>Recursos de ayuda y soporte</p>
    </div>
    <div class="grid-container">
        <button class="grid-item sop-1">Preguntas Frecuentes</button>
        <button class="grid-item sop-2">Tutoriales</button>
        <button class="grid-item sop-3">Guías de Usuario</button>
        <button class="grid-item sop-4">Manual del Sistema</button>
    </div>
  `,
  "contacto-soporte": `
    <div class="content-header">
        <h1>Contactar Soporte</h1>
        <p>Canales de comunicación con soporte</p>
    </div>
    <div class="grid-container">
        <button class="grid-item sop-1">Enviar Ticket</button>
        <button class="grid-item sop-2">Chat en Línea</button>
        <button class="grid-item sop-3">Correo de Soporte</button>
        <button class="grid-item sop-4">Llamada Telefónica</button>
    </div>
  `,
  acerca: `
    <div class="content-header">
        <h1>Acerca de JoyeriaPOS</h1>
        <p>Sistema de gestión especializado para joyerías y orfebrerías</p>
    </div>
    <div class="about-section">
        <div class="about-card">
            <h2>Nuestra Misión</h2>
            <p>Proporcionar herramientas especializadas para la gestión eficiente de joyerías, 
            optimizando procesos de inventario, ventas y producción artesanal.</p>
        </div>
        <div class="about-card">
            <h2>Características Principales</h2>
            <ul>
                <li>Gestión de metales preciosos y piedras</li>
                <li>Control de inventario especializado</li>
                <li>Reportes financieros detallados</li>
                <li>Seguimiento de pedidos personalizados</li>
            </ul>
        </div>
        <div class="about-card">
            <h2>Versión</h2>
            <p>Dedumsoft v1.0  - 2023</p>
            <p>© 2025 Joyas Van. Todos los derechos reservados.</p>
        </div>
    </div>
  `
};

document.addEventListener("DOMContentLoaded", () => {
    const mainContent = document.querySelector(".main-content");

    document.querySelectorAll("[data-section]").forEach(link => {
        link.addEventListener("click", e => {
            e.preventDefault();
            let section = link.getAttribute("data-section");
            mainContent.innerHTML = content[section] || `
                <div class="content-header">
                    <h1>${section}</h1>
                    <p>Sección en desarrollo</p>
                </div>
                <div class="coming-soon">
                    <h2>Próximamente...</h2>
                    <p>Esta funcionalidad estará disponible en próximas actualizaciones.</p>
                </div>
            `;
        });
    });
});

// Cerrar menús al hacer clic fuera de ellos
document.addEventListener('click', function(event) {
    const isDropdownButton = event.target.matches(".dropdown-btn") || 
                             event.target.closest(".dropdown-btn");
    const isDropdown = event.target.closest(".dropdown-container");
    
    if (!isDropdownButton && !isDropdown) {
        document.querySelectorAll(".dropdown-container").forEach(dropdown => {
            dropdown.style.display = "none";
        });
        document.querySelectorAll(".dropdown-btn").forEach(btn => {
            btn.classList.remove("active");
        });
    }
});