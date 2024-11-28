# Sistema de Gestión de Presupuestos y Materiales

## 🚀 Descripción
Sistema profesional para la gestión de presupuestos, materiales y proveedores, con manejo avanzado de monedas y exportación a múltiples formatos. Desarrollado con PHP, JavaScript y tecnologías modernas.

## ✨ Características Principales

### Gestión de Presupuestos
- Creación y edición de presupuestos con interfaz tipo Excel
- Manejo automático de conversión de monedas (ARS/USD)
- Sistema de descuentos y costos adicionales
- Exportación a PDF y formato Flexxus
- Historial y versionado de presupuestos

### Gestión de Materiales
- Catálogo completo de materiales
- Actualización masiva de precios
- Historial de cambios de precios
- Validación de códigos Flexxus
- Integración con proveedores

### Características Técnicas
- Frontend moderno con Bootstrap 5
- AG Grid para manejo avanzado de datos
- Sistema de búsqueda en tiempo real
- Validaciones frontend y backend
- API RESTful
- Manejo de permisos y roles
- Sistema de notificaciones

## 🛠️ Tecnologías Utilizadas

### Frontend
- HTML5/CSS3
- JavaScript (ES6+)
- Bootstrap 5
- AG Grid Enterprise
- Select2
- SweetAlert2
- AutoComplete.js

### Backend
- PHP 8.0+
- MySQL/MariaDB
- PDO
- Composer

### Herramientas
- XAMPP
- Git
- Visual Studio Code
- Postman

## 📦 Instalación

1. Clonar el repositorio
```bash
git clone https://github.com/tuusuario/presupuestador.git
```

2. Instalar dependencias
```bash
composer install
```

3. Configurar base de datos
```bash
# Importar schema.sql
mysql -u root -p < database/schema.sql
```

4. Configurar el archivo de entorno
```bash
cp .env.example .env
# Editar .env con tus credenciales
```

5. Iniciar servidor local
```bash
php -S localhost:8000
```

## 📸 Capturas de Pantalla

![Dashboard](docs/images/dashboard.png)
![Presupuestos](docs/images/presupuestos.png)
![Materiales](docs/images/materiales.png)

## 🔧 Estructura del Proyecto

```
presupuestador/
├── api/                # Endpoints de la API
├── assets/            # Recursos estáticos
│   ├── css/
│   ├── js/
│   └── images/
├── includes/          # Clases y funciones PHP
├── database/         # Esquemas y migraciones
├── docs/             # Documentación
└── vendor/           # Dependencias
```

## 🚀 Características Destacadas

### Sistema de Presupuestos
- Interfaz intuitiva tipo Excel
- Cálculos automáticos
- Manejo de múltiples monedas
- Sistema de plantillas
- Exportación personalizada

### Gestión de Materiales
- Importación masiva
- Actualización de precios
- Historial de cambios
- Validación de códigos

### Integración Flexxus
- Exportación compatible
- Validación de códigos
- Sincronización de datos

## 🔐 Seguridad
- Autenticación de usuarios
- Sistema de roles y permisos
- Validación de datos
- Protección contra XSS y SQL Injection
- Logs de actividad

## 📊 Reportes y Estadísticas
- Dashboard interactivo
- Gráficos de tendencias
- Reportes exportables
- Análisis de datos

## 🌟 Mejoras Futuras
- [ ] Integración con APIs de cotización
- [ ] App móvil
- [ ] Sistema de backups automáticos
- [ ] Módulo de facturación
- [ ] Integración con otros ERPs

## 👨‍💻 Autor
**Braian Taiel Aguirre**
- LinkedIn: [Braian Taiel Aguirre](https://www.linkedin.com/in/braian-taiel-aguirre-29496623b)
- Email: braiantaialaguirre@gmail.com

## 📄 Licencia
Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE.md](LICENSE.md) para más detalles.

## 🙏 Agradecimientos
- Bootstrap - Framework CSS
- AG Grid - Grilla de datos avanzada
- Select2 - Selector mejorado
- SweetAlert2 - Notificaciones elegantes
- AutoComplete.js - Búsqueda en tiempo real