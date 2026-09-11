<?php

namespace App\Http\Controllers;

use App\Models\Dato;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $usuarioSesion = $request->session()->get('usuario');

        if (!$usuarioSesion) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.'
            ], 401);
        }

        $rolSesion = strtolower($usuarioSesion['rol'] ?? '');

        if (!in_array($rolSesion, ['administrador', 'supervisor'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tenés permisos para ver los usuarios.'
            ], 403);
        }

        $usuarios = Usuario::with(['rol', 'datos'])
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get()
            ->map(function ($usuario) {
                $rol = $usuario->rol?->nombre;

                $licencia = null;

                if ($rol === 'chofer') {
                    $licencia = DB::table('chofer')
                        ->where('id_usuario', $usuario->id_usuario)
                        ->value('licencia');
                }

                return [
                    'id_usuario' => $usuario->id_usuario,
                    'nombre' => $usuario->nombre,
                    'apellido' => $usuario->apellido,
                    'documento' => $usuario->datos?->documento,
                    'telefono' => $usuario->datos?->telefono,
                    'email' => $usuario->datos?->email,
                    'rol' => $rol,
                    'estado' => $usuario->estado,
                    'fecha_registro' => $usuario->fecha_registro,
                    'licencia' => $licencia
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $usuarios
        ]);
    }

    public function update(Request $request, $id)
    {
        $usuarioSesion = $request->session()->get('usuario');

        if (!$usuarioSesion) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.'
            ], 401);
        }

        $rolSesion = strtolower($usuarioSesion['rol'] ?? '');

        if (!in_array($rolSesion, ['administrador', 'supervisor'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tenés permisos para modificar usuarios.'
            ], 403);
        }

        $usuario = Usuario::with(['rol', 'datos'])->find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no existe.'
            ], 404);
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'documento' => ['required', 'string', 'max:20'],
            'telefono' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:150'],
            'estado' => ['required', 'in:activo,inactivo'],
            'licencia' => ['nullable', 'string', 'max:50']
        ]);

        $dato = $usuario->datos;

        if (!$dato) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no tiene datos asociados.'
            ], 422);
        }

        $emailExiste = Dato::where('email', strtolower($data['email']))
            ->where('id_datos', '!=', $dato->id_datos)
            ->exists();

        if ($emailExiste) {
            return response()->json([
                'success' => false,
                'message' => 'El correo ya está registrado.'
            ], 422);
        }

        $documentoExiste = Dato::where('documento', $data['documento'])
            ->where('id_datos', '!=', $dato->id_datos)
            ->exists();

        if ($documentoExiste) {
            return response()->json([
                'success' => false,
                'message' => 'El documento ya está registrado.'
            ], 422);
        }

        DB::transaction(function () use ($usuario, $dato, $data) {
            $usuario->update([
                'nombre' => trim($data['nombre']),
                'apellido' => trim($data['apellido']),
                'estado' => $data['estado']
            ]);

            $dato->update([
                'documento' => trim($data['documento']),
                'telefono' => trim($data['telefono']),
                'email' => strtolower(trim($data['email']))
            ]);

            if ($usuario->rol?->nombre === 'chofer') {
                DB::table('chofer')
                    ->where('id_usuario', $usuario->id_usuario)
                    ->update([
                        'licencia' => $data['licencia'] ?? null
                    ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente.'
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $usuarioSesion = $request->session()->get('usuario');

        if (!$usuarioSesion) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.'
            ], 401);
        }

        $rolSesion = strtolower($usuarioSesion['rol'] ?? '');

        if (!in_array($rolSesion, ['administrador', 'supervisor'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tenés permisos para dar de baja usuarios.'
            ], 403);
        }

        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no existe.'
            ], 404);
        }

        if ((int) ($usuarioSesion['id'] ?? 0) === (int) $usuario->id_usuario) {
            return response()->json([
                'success' => false,
                'message' => 'No podés darte de baja a vos mismo.'
            ], 422);
        }

        $usuario->update([
            'estado' => 'inactivo'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario dado de baja correctamente.'
        ]);
    }
}