# SiGeRu — API de Usuarios

**Sistema de Gestión de Residuos Urbanos | Delta-Logic — 2026**

La API de Usuarios es uno de los componentes backend de **SiGeRu**, desarrollada con PHP y Laravel.

Su función es administrar la autenticación, los perfiles de usuario, las solicitudes de acceso, la recuperación de contraseñas y las comunicaciones por correo electrónico relacionadas con los usuarios.

Esta API es consumida principalmente por el frontend [SiGeRu-Website](https://github.com/DeltaLogic-UTU/SiGeRu-Website) y se integra con los demás servicios del proyecto.

## 1. Funcionalidades principales

La API permite:

- Inicio de sesión y autenticación.
- Generación y validación de tokens JWT.
- Cierre de sesión.
- Consulta del perfil autenticado.
- Actualización de datos personales.
- Gestión de usuarios y roles.
- Registro de solicitudes de acceso.
- Aprobación y rechazo de solicitudes.
- Creación de cuentas al aprobar solicitudes.
- Recuperación y cambio de contraseña.
- Envío de correos electrónicos.
- Validación de datos y permisos.

La autenticación permite que las diferentes interfaces de SiGeRu identifiquen al usuario y apliquen las restricciones correspondientes a su rol.

## 2. Tecnologías utilizadas

| Tecnología | Función |
|---|---|
| PHP | Lenguaje del backend |
| Laravel | Framework de desarrollo |
| Composer | Gestión de dependencias |
| MariaDB/MySQL | Almacenamiento de datos |
| JWT | Autenticación mediante tokens |
| JSON | Intercambio de información |
| SMTP | Envío de correos electrónicos |
| XAMPP | Entorno local de desarrollo |

La API utiliza las herramientas de Laravel para validar peticiones, consultar modelos, gestionar transacciones y devolver respuestas HTTP.

## 3. Organización del proyecto

```text
SiGeRu-API-Usuarios/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── AuthController.php
│   │       ├── UsuarioController.php
│   │       ├── SolicitudAccesoController.php
│   │       ├── RecuperacionPasswordController.php
│   │       └── ConsultaController.php
│   └── Models/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
│   ├── api.php
│   └── web.php
├── storage/
├── tests/
├── .env.example
├── .gitignore
├── artisan
├── composer.json
├── composer.lock
└── README.md
```

Los controladores gestionan las peticiones HTTP y se comunican con los modelos y la base de datos.

## 4. Autenticación JWT

SiGeRu utiliza **JSON Web Tokens (JWT)** para autenticar usuarios.

### Funcionamiento

1. El usuario ingresa sus credenciales.
2. La API verifica los datos y el estado de la cuenta.
3. Si son correctos, genera un token JWT.
4. El frontend conserva el token durante la sesión.
5. Las solicitudes protegidas incluyen el token.
6. Laravel identifica al usuario y comprueba su autorización.

Las peticiones autenticadas utilizan el encabezado:

```http
Authorization: Bearer TU_TOKEN_JWT
Accept: application/json
```

### Control de acceso

Los perfiles utilizados por el sistema incluyen:

- Vecino.
- Cuadrilla.
- Barrendero.
- Operario.
- Chofer.
- Administrador.
- Supervisor.

Cada perfil dispone de funcionalidades específicas.

La protección de las páginas del frontend se complementa con las verificaciones de autenticación y autorización del backend.

## 5. Registro y solicitudes de acceso

El sistema dispone de un formulario público desde el cual los interesados pueden solicitar una cuenta.

Los perfiles contemplados para las solicitudes incluyen:

- Vecino.
- Cuadrilla.
- Operario.
- Chofer.

### Proceso de registro

1. El solicitante completa sus datos personales.
2. El frontend envía la información a la API de Usuarios.
3. Laravel valida los campos recibidos.
4. La solicitud queda registrada con estado `pendiente`.
5. Administración o Supervisión revisa la solicitud.
6. La solicitud puede aprobarse o rechazarse.

### Aprobación

Cuando una solicitud es aprobada:

1. Se verifica que no exista una cuenta con el mismo correo o documento.
2. Se comprueba el perfil solicitado.
3. Se crea el usuario correspondiente.
4. Se registran sus datos de acceso y su rol.
5. Se habilita la cuenta.
6. Se registra la resolución de la solicitud.
7. Se intenta enviar un correo de confirmación al solicitante.

El correo se dirige a la dirección registrada en la solicitud, no al buzón de Administración.

Si falla el envío del correo después de aprobarse la cuenta, el sistema registra el error para permitir su diagnóstico.

### Rechazo

Las solicitudes también pueden rechazarse, registrando la resolución sin crear una cuenta habilitada.

## 6. Gestión de perfiles

La API permite obtener información del usuario autenticado y trabajar con sus datos personales.

Entre los datos administrados se encuentran:

- Identificador.
- Nombre y apellido.
- Documento.
- Teléfono.
- Correo electrónico.
- Rol.
- Estado de la cuenta.

El frontend utiliza esta información para identificar al usuario y presentar sus funciones correspondientes.

Por ejemplo, el endpoint autenticado:

```http
GET /api/perfil
```

permite consultar los datos del usuario que inició sesión.

## 7. Recuperación de contraseña

La API incorpora un procedimiento de recuperación mediante correo electrónico.

### Funcionamiento

1. El usuario solicita recuperar el acceso.
2. Introduce su correo electrónico.
3. La API verifica si existe una cuenta activa.
4. Se genera un token temporal de recuperación.
5. Se registra su referencia de forma segura.
6. Se envía un enlace de recuperación al correo registrado.
7. El usuario establece una contraseña nueva.
8. La API valida el token y actualiza la contraseña.

El enlace de recuperación tiene una **validez de una hora** en la implementación revisada.

Los tokens anteriores pendientes pueden invalidarse cuando se genera una nueva solicitud.

Las contraseñas se almacenan utilizando mecanismos de hash, evitando guardar su contenido original en texto plano.

Por seguridad, la solicitud de recuperación no debe revelar si una dirección de correo pertenece o no a un usuario registrado.

## 8. Correos electrónicos

La API utiliza Laravel Mail y un servidor SMTP configurado mediante variables de entorno.

### Correos contemplados

**Confirmación de aprobación:** informa al solicitante que su cuenta fue aprobada y que puede iniciar sesión.

**Recuperación de contraseña:** envía las instrucciones y el enlace temporal para restablecer el acceso.

**Formulario de contacto:** procesa las consultas enviadas desde la Landing Page mediante la funcionalidad de contacto implementada en el sistema.

### Remitente y destinatario

El correo de SiGeRu se utiliza como remitente de los mensajes del sistema.

El destinatario depende de la función:

| Función | Destinatario |
|---|---|
| Aprobación | Correo del solicitante |
| Recuperación | Correo de la cuenta |
| Contacto | Buzón configurado para recibir consultas |

Los parámetros SMTP y las credenciales reales no deben publicarse en GitHub.

## 9. Base de datos

La API utiliza la base compartida:

```text
sigeru
```

La información se almacena en MariaDB/MySQL.

Entre las entidades relacionadas se encuentran:

- Usuarios.
- Roles.
- Datos de autenticación.
- Solicitudes de acceso.
- Perfiles específicos.
- Recuperaciones de contraseña.

Los datos de usuarios también se utilizan desde las demás APIs para identificar personal y relacionar operaciones del sistema.

La estructura de referencia se encuentra en:

[SiGeRu-DumpSQL](https://github.com/DeltaLogic-UTU/SiGeRu-DumpSQL)

El nombre exacto del archivo SQL debe consultarse en ese repositorio.

## 10. Configuración del entorno

El proyecto utiliza un archivo `.env` local.

### Crear el archivo

En PowerShell:

```powershell
Copy-Item .env.example .env
```

### Configuración principal

```dotenv
APP_NAME="SiGeRu"
APP_ENV=local
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

SIGERU_FRONTEND_URL=http://localhost/SiGeRu-Website
```

Estos valores corresponden a un ejemplo de desarrollo local con XAMPP.

La contraseña de MySQL y las demás variables deben adaptarse al equipo donde se ejecute el sistema.

### APP_KEY

Cada instalación debe generar su propia clave de Laravel:

```powershell
php artisan key:generate
```

La clave generada se almacena en el `.env` local.

### JWT

La autenticación JWT también requiere la configuración de sus claves y parámetros de seguridad, según el paquete utilizado por el proyecto.

No deben compartirse secretos JWT ni reutilizarse claves privadas de otros entornos.

### Configuración del correo

Ejemplo:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="SiGeRu"
```

Es necesario completar estos valores con los parámetros reales del proveedor de correo.

Después de modificar la configuración se puede limpiar la caché:

```powershell
php artisan config:clear
```

**Nunca subir el archivo `.env` real a GitHub.**

## 11. Rutas de la API

Las rutas del proyecto se organizan principalmente en:

```text
routes/api.php
routes/web.php
```

Las operaciones de autenticación y gestión de usuarios deben consultarse en la configuración vigente de estas rutas.

### Ejemplo de consulta autenticada

```http
GET http://127.0.0.1:8000/api/perfil
Authorization: Bearer TU_TOKEN_JWT
Accept: application/json
```

### Consultar todas las rutas

Desde la carpeta del proyecto:

```powershell
php artisan route:list
```

Para consultar las rutas bajo el prefijo API:

```powershell
php artisan route:list --path=api
```

Este comando permite comprobar los endpoints y métodos HTTP registrados en la instalación actual.

### Sesiones y CSRF

Laravel dispone de mecanismos de sesiones y protección CSRF para solicitudes web que utilizan cookies.

Las rutas de API autenticadas mediante JWT utilizan el token Bearer y no deben confundirse con el funcionamiento tradicional de las sesiones de navegador.

La necesidad de CSRF depende del middleware y del mecanismo de autenticación utilizado en cada ruta.

## 12. Ejecución local

### Requisitos

- PHP compatible con el proyecto.
- Composer.
- MySQL/MariaDB.
- XAMPP o entorno equivalente.
- Git.

### Paso 1. Clonar el repositorio

```powershell
git clone https://github.com/DeltaLogic-UTU/SiGeRu-API-Usuarios.git

cd SiGeRu-API-Usuarios
```

Se debe utilizar la rama que contenga la versión más reciente y validada del código.

### Paso 2. Instalar dependencias

```powershell
composer install
```

### Paso 3. Crear el entorno

```powershell
Copy-Item .env.example .env

php artisan key:generate
```

Configurar también las variables JWT, MySQL y SMTP necesarias.

### Paso 4. Preparar MySQL

Iniciar MySQL desde XAMPP.

Crear la base de datos si todavía no existe:

```sql
CREATE DATABASE IF NOT EXISTS sigeru
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Importar la estructura SQL correspondiente desde `SiGeRu-DumpSQL`.

No reemplazar una base existente sin realizar previamente una copia de seguridad.

### Paso 5. Iniciar Laravel

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

La API queda disponible en:

```text
http://127.0.0.1:8000
```

### Paso 6. Verificar

Comprobar que:

1. Laravel se inicie sin errores.
2. La conexión a MySQL funcione.
3. Las rutas estén registradas.
4. El inicio de sesión genere correctamente la autenticación.
5. El perfil autenticado pueda consultarse.
6. Las solicitudes de acceso puedan registrarse y procesarse.
7. Los correos SMTP puedan enviarse en el entorno configurado.

## 13. Integración con SiGeRu-Website

El frontend utiliza la API de Usuarios en el puerto `8000`.

Desde JavaScript se realizan solicitudes mediante `fetch`, incluyendo los encabezados correspondientes.

Ejemplo de configuración:

```javascript
const API_SESION_USUARIOS = 'http://127.0.0.1:8000';
```

Las peticiones autenticadas utilizan JWT.

La API de Usuarios comparte información con los módulos del proyecto que requieren identificar al usuario, comprobar su rol o consultar los datos asociados a su cuenta.

## 14. Repositorios relacionados

SiGeRu está dividido en cinco repositorios:

- [SiGeRu-Website](https://github.com/DeltaLogic-UTU/SiGeRu-Website): interfaces del sistema.
- [SiGeRu-API-Usuarios](https://github.com/DeltaLogic-UTU/SiGeRu-API-Usuarios): autenticación, perfiles, solicitudes y correos.
- [SiGeRu-API-Gestion](https://github.com/DeltaLogic-UTU/SiGeRu-API-Gestion): administración y operaciones.
- [SiGeRu-API-Recoleccion](https://github.com/DeltaLogic-UTU/SiGeRu-API-Recoleccion): recolecciones y recorridos.
- [SiGeRu-DumpSQL](https://github.com/DeltaLogic-UTU/SiGeRu-DumpSQL): estructura y datos de referencia.

## 15. Seguridad y mantenimiento

Para mantener la API segura se recomienda:

- Proteger las rutas según autenticación y rol.
- No almacenar contraseñas en texto plano.
- Utilizar secretos JWT seguros.
- No publicar archivos `.env`.
- Validar todas las entradas del usuario.
- Restringir correctamente los orígenes CORS.
- Evitar exponer información sensible en respuestas o registros.
- Proteger los procesos de recuperación.
- Realizar pruebas de autorización.
- Mantener actualizadas las dependencias.
- Realizar respaldos de la base de datos.

Las configuraciones de desarrollo, como `APP_DEBUG=true`, no deben mantenerse habilitadas en producción.

## 16. Pruebas

Se deben verificar especialmente:

- Inicio y cierre de sesión.
- Credenciales inválidas.
- Tokens vencidos o incorrectos.
- Accesos no autorizados.
- Consulta y actualización de perfil.
- Registro de solicitudes.
- Aprobación y rechazo.
- Prevención de usuarios duplicados.
- Recuperación de contraseña.
- Expiración de tokens temporales.
- Envío de correos.
- Conexión con la base de datos.
- Integración con el frontend.

El proyecto debe continuar con sus pruebas funcionales y de integración antes de considerarse preparado para producción.

---

## Proyecto académico 2026

**SiGeRu — Sistema de Gestión de Residuos Urbanos**

**Delta-Logic**
