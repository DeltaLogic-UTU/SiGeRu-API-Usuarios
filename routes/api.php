<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SolicitudAccesoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\RecuperacionPasswordController;


// Ruta pública de comprobación 
Route::get('health', fn () => response()->json([
    'success' => true,
    'message' => 'API Usuarios running',
]));

// Rutas públicas de autenticación con prefijo /api/auth/* 
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});

// Rutas públicas del sistema SiGeRu (login directo, solicitud de acceso y recuperación de contraseña)
Route::post('login', [AuthController::class, 'login']);
Route::post('registro', [AuthController::class, 'registro']);
Route::post('recuperar-password', [RecuperacionPasswordController::class, 'solicitar']);
Route::post('restablecer-password', [RecuperacionPasswordController::class, 'restablecer']);
// Logout público para permitir limpiar sesión incluso si el token ya venció
Route::post('logout', [AuthController::class, 'logout']);


// =========================================================================
// 2. RUTAS PROTEGIDAS CON MIDDLEWARE auth:api
//  Todas las rutas dentro de este grupo
// exigen enviar el header "Authorization: Bearer <token>".
// Si el token no está, fue modificado o venció, Laravel responde 401 Unauthorized.
// =========================================================================
Route::middleware('auth:api')->group(function () {
    // Consultar usuario autenticado 
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::get('session', [AuthController::class, 'session']);

    // Perfil del usuario autenticado
    Route::get('perfil', [AuthController::class, 'perfil']);
    Route::put('perfil', [AuthController::class, 'actualizarPerfil']);

    // Gestión de solicitudes de acceso (Administrador / Supervisor)
    Route::get('solicitudes', [SolicitudAccesoController::class, 'index']);
    Route::post('solicitudes/{id}/aprobar', [SolicitudAccesoController::class, 'aprobar']);
    Route::post('solicitudes/{id}/rechazar', [SolicitudAccesoController::class, 'rechazar']);

    // Administración de usuarios / personal (Administrador / Supervisor)
    Route::get('usuarios', [UsuarioController::class, 'index']);
    Route::post('usuarios', [UsuarioController::class, 'store']);
    Route::put('usuarios/{id}', [UsuarioController::class, 'update']);
    Route::delete('usuarios/{id}', [UsuarioController::class, 'destroy']);
});


