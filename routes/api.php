<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SolicitudAccesoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\RecuperacionPasswordController;
use App\Http\Controllers\ConsultaController;


Route::get('health', fn () => response()->json([
    'success' => true,
    'message' => 'API Usuarios running',
]));

Route::post('consultas', [ConsultaController::class, 'enviar'])
    ->middleware('throttle:5,1');

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});

Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('registro', [AuthController::class, 'registro']);
Route::post('recuperar-password', [RecuperacionPasswordController::class, 'solicitar'])
    ->middleware('throttle:3,1');

Route::post('verificar-codigo', [RecuperacionPasswordController::class, 'verificar'])
    ->middleware('throttle:5,1');

Route::post('restablecer-password', [RecuperacionPasswordController::class, 'restablecer'])
    ->middleware('throttle:5,1');
Route::post('logout', [AuthController::class, 'logout']);

Route::middleware('auth:api')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::get('session', [AuthController::class, 'session']);

    Route::get('perfil', [AuthController::class, 'perfil']);
    Route::put('perfil', [AuthController::class, 'actualizarPerfil']);

    Route::get('solicitudes', [SolicitudAccesoController::class, 'index']);
    Route::post('solicitudes/{id}/aprobar', [SolicitudAccesoController::class, 'aprobar']);
    Route::post('solicitudes/{id}/rechazar', [SolicitudAccesoController::class, 'rechazar']);

    Route::get('usuarios', [UsuarioController::class, 'index']);
    Route::post('usuarios', [UsuarioController::class, 'store']);
    Route::put('usuarios/{id}', [UsuarioController::class, 'update']);
    Route::delete('usuarios/{id}', [UsuarioController::class, 'destroy']);
});


