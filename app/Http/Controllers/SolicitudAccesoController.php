<?php

namespace App\Http\Controllers;

use App\Models\Dato;
use App\Models\Rol;
use App\Models\SolicitudAcceso;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SolicitudAccesoController extends Controller
{
    public function index(Request $request)
    {
        $usuarioSesion =
            $request->session()->get('usuario');

        if (!$usuarioSesion) {
            return response()->json([
                'success' => false,
                'message' => 'No has iniciado sesión.'
            ], 401);
        }

        $rol =
            strtolower(
                $usuarioSesion['rol'] ?? ''
            );

        if (
            !in_array(
                $rol,
                [
                    'administrador',
                    'supervisor'
                ],
                true
            )
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No tienes permisos para ver las solicitudes.'
            ], 403);
        }

        $solicitudes =
            SolicitudAcceso::where(
                'estado',
                'pendiente'
            )
                ->orderBy(
                    'fecha_solicitud',
                    'desc'
                )
                ->get();

        return response()->json([
            'success' => true,
            'data' => $solicitudes
        ]);
    }


    public function aprobar(
        Request $request,
        $id
    ) {
        $usuarioSesion =
            $request->session()->get('usuario');

        if (!$usuarioSesion) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No has iniciado sesión.'
            ], 401);
        }

        $rolSesion =
            strtolower(
                $usuarioSesion['rol'] ?? ''
            );

        if (
            !in_array(
                $rolSesion,
                [
                    'administrador',
                    'supervisor'
                ],
                true
            )
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No tienes permisos para aprobar solicitudes.'
            ], 403);
        }

        $solicitud =
            SolicitudAcceso::find($id);

        if (!$solicitud) {
            return response()->json([
                'success' => false,
                'message' =>
                    'La solicitud no existe.'
            ], 404);
        }

        if (
            $solicitud->estado !==
            'pendiente'
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'La solicitud ya fue procesada.'
            ], 422);
        }

        if (
            Dato::where(
                'email',
                $solicitud->email
            )->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Ya existe un usuario con ese correo electrónico.'
            ], 422);
        }

        if (
            Dato::where(
                'documento',
                $solicitud->documento
            )->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Ya existe un usuario con esa cédula.'
            ], 422);
        }

        $rol =
            Rol::where(
                'nombre',
                $solicitud->perfil
            )->first();

        if (!$rol) {
            return response()->json([
                'success' => false,
                'message' =>
                    'El perfil solicitado no existe.'
            ], 422);
        }

        try {
            DB::transaction(
                function () use (
                    $solicitud,
                    $rol,
                    $usuarioSesion
                ) {
                    $usuario =
                        Usuario::create([
                            'id_rol' =>
                                $rol->id_rol,

                            'nombre' =>
                                $solicitud->nombre,

                            'apellido' =>
                                $solicitud->apellido,

                            'estado' =>
                                'activo'
                        ]);

                    Dato::create([
                        'id_usuario' =>
                            $usuario->id_usuario,

                        'password' =>
                            $solicitud->password,

                        'documento' =>
                            $solicitud->documento,

                        'telefono' =>
                            $solicitud->telefono,

                        'email' =>
                            $solicitud->email
                    ]);

                    if (
                        $solicitud->perfil ===
                        'vecino'
                    ) {
                        DB::table(
                            'vecino'
                        )->insert([
                            'id_usuario' =>
                                $usuario->id_usuario
                        ]);
                    }

                    if (
                        $solicitud->perfil ===
                        'operario'
                    ) {
                        DB::table(
                            'operario'
                        )->insert([
                            'id_usuario' =>
                                $usuario->id_usuario,

                            'fecha_aprobacion' =>
                                now()
                        ]);
                    }

                    if (
                        $solicitud->perfil ===
                        'chofer'
                    ) {
                        DB::table(
                            'chofer'
                        )->insert([
                            'id_usuario' =>
                                $usuario->id_usuario,

                            'licencia' =>
                                $solicitud->licencia,

                            'fecha_aprobacion' =>
                                now()
                        ]);
                    }

                    $solicitud->estado =
                        'aprobada';

                    $solicitud->fecha_resolucion =
                        now();

                    $solicitud->id_supervisor_resuelve =
                        $usuarioSesion['id']
                        ?? null;

                    $solicitud->save();
                }
            );

            return response()->json([
                'success' => true,
                'message' =>
                    'Solicitud aprobada correctamente.'
            ]);

        } catch (\Throwable $error) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No se pudo aprobar la solicitud. Verifica que la cédula, el teléfono y los demás datos sean correctos.'
            ], 500);
        }
    }


    public function rechazar(
        Request $request,
        $id
    ) {
        $usuarioSesion =
            $request->session()->get('usuario');

        if (!$usuarioSesion) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No has iniciado sesión.'
            ], 401);
        }

        $rol =
            strtolower(
                $usuarioSesion['rol'] ?? ''
            );

        if (
            !in_array(
                $rol,
                [
                    'administrador',
                    'supervisor'
                ],
                true
            )
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No tienes permisos para rechazar solicitudes.'
            ], 403);
        }

        $solicitud =
            SolicitudAcceso::find($id);

        if (!$solicitud) {
            return response()->json([
                'success' => false,
                'message' =>
                    'La solicitud no existe.'
            ], 404);
        }

        if (
            $solicitud->estado !==
            'pendiente'
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'La solicitud ya fue procesada.'
            ], 422);
        }

        try {
            $solicitud->estado =
                'rechazada';

            $solicitud->fecha_resolucion =
                now();

            $solicitud->id_supervisor_resuelve =
                $usuarioSesion['id']
                ?? null;

            $solicitud->save();

            return response()->json([
                'success' => true,
                'message' =>
                    'Solicitud rechazada correctamente.'
            ]);

        } catch (\Throwable $error) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No se pudo rechazar la solicitud.'
            ], 500);
        }
    }
}