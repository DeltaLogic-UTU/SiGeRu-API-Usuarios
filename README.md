# SiGeRu - API de Usuarios

API de usuarios y autenticación del proyecto **SiGeRu - Sistema de Gestión de Residuos Urbanos**.

Este repositorio contiene el backend desarrollado con **Laravel** encargado de gestionar el acceso al sistema, autenticación, perfiles de usuario, solicitudes de acceso y recuperación de contraseña.

La API es consumida principalmente por el frontend:

[SiGeRu-Website](https://github.com/DeltaLogic-UTU/SiGeRu-Website)

## Funcionalidades

Actualmente la API permite gestionar:

- inicio y cierre de sesión;
- autenticación de usuarios;
- registro y solicitudes de acceso;
- aprobación y rechazo de solicitudes;
- perfiles de usuario;
- consulta y modificación de datos personales;
- recuperación y cambio de contraseña;
- sesiones de usuario;
- validación de roles y perfiles.

Los perfiles contemplados para las solicitudes de acceso son:

- Vecino
- Cuadrilla
- Operario
- Chofer

## Tecnologías utilizadas

- PHP
- Laravel
- Composer
- MariaDB / MySQL
- JSON
- Sesiones de Laravel
- Protección CSRF

## Organización del proyecto

```text
SiGeRu-API-Usuarios/

├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── AuthController.php
│   │       ├── UsuarioController.php
│   │       ├── SolicitudAccesoController.php
│   │       └── RecuperacionPasswordController.php
│   │
│   └── Models/
│
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
│   └── web.php
│
├── storage/
├── tests/
├── .env.example
├── .gitignore
├── artisan
├── composer.json
├── composer.lock
└── README.md
```

## Base de datos

La API utiliza una base de datos MariaDB/MySQL llamada:

```text
sigeru
```

El SQL completo del proyecto se encuentra en:

[SiGeRu-DumpSQL](https://github.com/DeltaLogic-UTU/SiGeRu-DumpSQL)

Archivo principal:

```text
SQL_SiGeRU.sql
```

Este archivo contiene la estructura, relaciones y datos necesarios para recrear la base utilizada por SiGeRu.

## Configuración del entorno

El proyecto incluye:

```text
.env.example
```

como configuración base.

Después de clonar el proyecto se debe crear el archivo `.env`:

```powershell
copy .env.example .env
```

Luego generar una clave propia para Laravel:

```powershell
php artisan key:generate
```

La configuración principal utilizada por el proyecto es:

```env
APP_NAME="SiGeRu"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigeru
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
SESSION_COOKIE=sigeru_usuarios_session
SESSION_LIFETIME=120
SESSION_PATH=/
SESSION_DOMAIN=null

SIGERU_FRONTEND_URL=http://localhost/SiGeRu-Website
```

Si la instalación local de MySQL/MariaDB utiliza otro usuario o contraseña, estos valores deben modificarse en el archivo `.env`.

La variable:

```env
SIGERU_FRONTEND_URL=http://localhost/SiGeRu-Website
```

indica la dirección local utilizada para comunicarse con el frontend de SiGeRu.

## APP_KEY

Cada instalación de la API debe generar su propia `APP_KEY`.

El archivo `.env.example` mantiene:

```env
APP_KEY=
```

y después de crear el `.env` se ejecuta:

```powershell
php artisan key:generate
```

La clave generada queda únicamente en el archivo `.env` local y no debe subirse a GitHub.

## CSRF y sesiones

La API utiliza sesiones de Laravel y protección CSRF.

El frontend obtiene el token mediante:

```text
GET /csrf-token
```

y posteriormente lo envía en las solicitudes que modifican información mediante el encabezado:

```text
X-CSRF-TOKEN
```

Los tokens CSRF se generan automáticamente durante la ejecución del sistema y no deben almacenarse en `.env` ni en el repositorio.

La API utiliza una cookie de sesión propia:

```env
SESSION_COOKIE=sigeru_usuarios_session
```

Esto permite mantener separada la sesión de esta API respecto de la API de Gestión.

## Autenticación

Cuando un usuario inicia sesión correctamente, la API almacena en la sesión información necesaria para identificarlo, incluyendo:

- identificador de usuario;
- nombre;
- apellido;
- correo electrónico;
- rol.

Esta información es utilizada posteriormente para validar el acceso a las distintas funciones del sistema.

## Solicitudes de acceso

Los nuevos usuarios pueden solicitar acceso al sistema seleccionando uno de los perfiles habilitados:

```text
vecino
cuadrilla
operario
chofer
```

Las solicitudes pueden ser revisadas y posteriormente aprobadas o rechazadas.

Las validaciones de los datos se realizan desde Laravel antes de registrar la solicitud.

## Recuperación de contraseña

La API incluye el proceso de recuperación de contraseña.

El sistema genera un token temporal para permitir el cambio de contraseña y controla su período de validez.

Las contraseñas se almacenan de forma segura mediante el sistema de hash utilizado por Laravel.

Aunque se pretende que sea funcional luego de implementar servicios reales.

## Rutas

Las rutas se encuentran definidas en:

```text
routes/web.php
```

La API funciona durante el desarrollo en:

```text
http://127.0.0.1:8000
```

Las rutas incluyen las operaciones necesarias para:

- autenticación;
- cierre de sesión;
- gestión de perfiles;
- solicitudes de acceso;
- recuperación de contraseña;
- actualización de datos personales;
- obtención del token CSRF.

Las rutas completas y actualizadas deben consultarse directamente en:

```text
routes/web.php
```

## Ejecutar el proyecto localmente

Se necesitan:

- PHP
- Composer
- MySQL/MariaDB
- XAMPP o un entorno equivalente

### 1. Clonar el repositorio

```powershell
git clone https://github.com/DeltaLogic-UTU/SiGeRu-API-Usuarios.git
cd SiGeRu-API-Usuarios
```

El código completo de la API se encuentra en la rama:

```text
develop
```

Cambiar a ella:

```powershell
git switch develop
```

### 2. Instalar dependencias

```powershell
composer install
```

### 3. Crear el archivo de entorno

```powershell
copy .env.example .env
```

### 4. Generar la clave de Laravel

```powershell
php artisan key:generate
```

### 5. Preparar la base de datos

Iniciar MySQL/MariaDB.

Crear la base:

```text
sigeru
```

e importar:

```text
SQL_SiGeRU.sql
```

desde el repositorio `SiGeRu-DumpSQL`.

### 6. Iniciar la API

```powershell
php artisan serve --port=8000
```

La API quedará disponible en:

```text
http://127.0.0.1:8000
```

## Integración con el frontend

El frontend utiliza como dirección de la API de Usuarios:

```javascript
const API_USUARIOS = 'http://127.0.0.1:8000';
```

Las solicitudes se realizan mediante JavaScript utilizando `fetch`.

Las peticiones utilizan las credenciales de sesión correspondientes y, cuando es necesario, el token CSRF generado por Laravel.

## Ramas

El repositorio utiliza la siguiente organización:

```text
main
└── documentación principal

develop
└── código completo de la API
```

La rama `main` es la rama predeterminada del repositorio y contiene la documentación principal.

La rama `develop` contiene el código completo de la API y se utiliza para la integración del desarrollo.

Las nuevas funcionalidades se desarrollan en ramas:

```text
feature/nombre-funcionalidad
```

y posteriormente integrarse en `develop`.

## Repositorios de SiGeRu

El proyecto se encuentra separado en cuatro repositorios.

### Frontend

[SiGeRu-Website](https://github.com/DeltaLogic-UTU/SiGeRu-Website)

Contiene la interfaz web desarrollada con HTML, CSS, JavaScript y Bootstrap.

### API de Usuarios

[SiGeRu-API-Usuarios](https://github.com/DeltaLogic-UTU/SiGeRu-API-Usuarios)

Responsable de autenticación, usuarios, perfiles, solicitudes de acceso y recuperación de contraseña.

### API de Gestión

[SiGeRu-API-Gestion](https://github.com/DeltaLogic-UTU/SiGeRu-API-Gestion)

Responsable de la gestión operativa del sistema.

### Base de datos

[SiGeRu-DumpSQL](https://github.com/DeltaLogic-UTU/SiGeRu-DumpSQL)

Contiene el dump SQL utilizado por el proyecto.

## Proyecto académico 2026

Proyecto desarrollado por:

**Delta-Logic**
