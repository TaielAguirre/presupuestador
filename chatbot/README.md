# NetLoom ChatBot - Plataforma de Análisis Predictivo

NetLoom ChatBot es una plataforma integral para el análisis predictivo de datos empresariales, que permite gestionar fuentes de datos, crear modelos predictivos y generar informes visuales.

![Dashboard de NetLoom ChatBot](./README_files/dashboard.png)

## Características principales

- **Análisis de datos**: Carga, visualización y análisis de múltiples fuentes de datos.
- **Modelos predictivos**: Creación y entrenamiento de modelos para predicción de ventas, optimización de inventario y más.
- **Informes personalizados**: Generación de informes visuales con gráficos interactivos.
- **Interfaz conversacional**: Asistente virtual para consultas de datos mediante chat.
- **Diagnóstico del sistema**: Herramienta de diagnóstico para verificar la configuración y conexión.

## Requisitos previos

- Node.js (v14.0.0 o superior)
- MongoDB (v4.4.0 o superior)
- NPM (v6.0.0 o superior)

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/tuusuario/chatbot.git
cd chatbot
```

### 2. Instalar dependencias

```bash
# Instalar dependencias del servidor
cd server
npm install

# Instalar dependencias del cliente
cd ../client
npm install
```

### 3. Configuración del entorno

#### Servidor (.env)

Crea un archivo `.env` en la carpeta `/server` con la siguiente configuración:

```
NODE_ENV=development
PORT=3001

MONGO_URI=mongodb://localhost:27017/chatbot
MONGO_URI_PROD=mongodb://localhost:27017/chatbot

JWT_SECRET=tuclavesecreatajwt
JWT_EXPIRE=30d
JWT_COOKIE_EXPIRE=30

CLIENT_URL=http://localhost:3000

SMTP_HOST=smtp.tuproveedor.com
SMTP_PORT=2525
SMTP_EMAIL=tuemail@tudominio.com
SMTP_PASSWORD=tupassword
FROM_EMAIL=noreply@tudominio.com
FROM_NAME=ChatBot

FILE_UPLOAD_PATH=./public/uploads
MAX_FILE_UPLOAD=10000000
```

#### Cliente (.env)

Crea un archivo `.env` en la carpeta `/client` con la siguiente configuración:

```
REACT_APP_API_URL=http://localhost:3001/api
REACT_APP_ENV=development
```

### 4. Cargar datos de prueba (opcional)

```bash
cd server
npm run seed
```

## Ejecución

### Iniciar el servidor de desarrollo

```bash
# Desde la carpeta servidor
cd server
npm run dev
```

### Iniciar el cliente de desarrollo

```bash
# Desde la carpeta cliente
cd client
npm start
```

La aplicación estará disponible en http://localhost:3000

## Herramienta de diagnóstico

ChatBot incluye una herramienta de diagnóstico integrada para identificar y resolver problemas de configuración y conexión.

### Acceder a la herramienta de diagnóstico

1. Inicia sesión en la aplicación
2. Haz clic en "Diagnóstico" en el menú lateral
3. O navega directamente a http://localhost:3000/diagnostics

### Funcionalidades de diagnóstico

- Verificación de la configuración del cliente
- Prueba de conexión con el servidor
- Validación del token de autenticación
- Comprobación de endpoints de API
- Sugerencias para resolver problemas detectados

![Herramienta de diagnóstico](./README_files/diagnostico.png)

### Diagnóstico del servidor

También puedes ejecutar una herramienta de diagnóstico específica para el servidor:

```bash
cd server
node diagnose.js
```

Esta herramienta verificará:
- Conexión a MongoDB
- Disponibilidad de puertos
- Procesos de Node en ejecución
- Conectividad de red

## Estructura del proyecto

```