<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SolicitudAccesoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\RecuperacionPasswordController;

Route::get('/csrf-token', function () {
    return response()->json([
        'token' => csrf_token()
    ]);
});

Route::post('/api/login', [AuthController::class, 'login']);
Route::get('/api/session', [AuthController::class, 'session']);
Route::post('/api/logout', [AuthController::class, 'logout']);
Route::post('/api/registro', [AuthController::class, 'registro']);

Route::get('/api/perfil', [AuthController::class, 'perfil']);
Route::put('/api/perfil', [AuthController::class, 'actualizarPerfil']);

Route::get('/api/solicitudes', [SolicitudAccesoController::class, 'index']);
Route::post('/api/solicitudes/{id}/aprobar', [SolicitudAccesoController::class, 'aprobar']);
Route::post('/api/solicitudes/{id}/rechazar', [SolicitudAccesoController::class, 'rechazar']);

Route::get('/api/usuarios', [UsuarioController::class, 'index']);
Route::post('/api/usuarios', [UsuarioController::class, 'store']);
Route::put('/api/usuarios/{id}', [UsuarioController::class, 'update']);
Route::delete('/api/usuarios/{id}', [UsuarioController::class, 'destroy']);

Route::post('/api/recuperar-password',[RecuperacionPasswordController::class,'solicitar']);
Route::post('/api/restablecer-password',[RecuperacionPasswordController::class,'restablecer']);