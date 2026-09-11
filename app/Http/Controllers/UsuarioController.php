<?php

namespace App\Http\Controllers;

use App\Models\Dato;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    private function verificarGestion(Request $request)
    {
        $usuarioSesion = $request->session()->get('usuario');

        if (!$usuarioSesion) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.'
            ], 401);
        }

        $rol = strtolower($usuarioSesion['rol'] ?? '');

        if (!in_array($rol, ['administrador', 'supervisor'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tenés permisos para realizar esta acción.'
            ], 403);
        }

        return null;
    }

    public function index(Request $request)
    {
        $error = $this->verificarGestion($request);

        if ($error) {
            return $error;
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

    public function store(Request $request)
    {
        $error = $this->verificarGestion($request);

        if ($error) {
            return $error;
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'documento' => ['required', 'string', 'max:20'],
            'telefono' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:8'],
            'password_confirm' => ['required', 'same:password'],
            'rol' => [
                'required',
                'in:administrador,supervisor,chofer,cuadrilla,barrendero,operario,vecino'
            ],
            'licencia' => ['nullable', 'string', 'max:50']
        ]);

        $email = strtolower(trim($data['email']));
        $documento = trim($data['documento']);

        if (Dato::where('email', $email)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'El correo ya está registrado.'
            ], 422);
        }

        if (Dato::where('documento', $documento)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'El documento ya está registrado.'
            ], 422);
        }

        if (
            $data['rol'] === 'chofer' &&
            empty(trim($data['licencia'] ?? ''))
        ) {
            return response()->json([
                'success' => false,
                'message' => 'La licencia es obligatoria para un chofer.'
            ], 422);
        }

        $rol = Rol::where('nombre', $data['rol'])->first();

        if (!$rol) {
            return response()->json([
                'success' => false,
                'message' => 'El rol seleccionado no existe.'
            ], 422);
        }

        try {
            $usuario = DB::transaction(function () use (
                $data,
                $rol,
                $email,
                $documento
            ) {
                $usuario = Usuario::create([
                    'id_rol' => $rol->id_rol,
                    'nombre' => trim($data['nombre']),
                    'apellido' => trim($data['apellido']),
                    'estado' => 'activo'
                ]);

                Dato::create([
                    'id_usuario' => $usuario->id_usuario,
                    'password' => Hash::make($data['password']),
                    'documento' => $documento,
                    'telefono' => trim($data['telefono']),
                    'email' => $email
                ]);

                if ($data['rol'] === 'administrador') {
                    DB::table('admin_backoffice')->insert([
                        'id_usuario' => $usuario->id_usuario
                    ]);
                }

                if ($data['rol'] === 'supervisor') {
                    DB::table('supervisor')->insert([
                        'id_usuario' => $usuario->id_usuario,
                        'fecha_aprobacion' => now()
                    ]);
                }

                if ($data['rol'] === 'chofer') {
                    DB::table('chofer')->insert([
                        'id_usuario' => $usuario->id_usuario,
                        'licencia' => trim($data['licencia']),
                        'fecha_aprobacion' => now()
                    ]);
                }

                if ($data['rol'] === 'operario') {
                    DB::table('operario')->insert([
                        'id_usuario' => $usuario->id_usuario,
                        'fecha_aprobacion' => now()
                    ]);
                }

                if ($data['rol'] === 'barrendero') {
                    DB::table('barrendero')->insert([
                        'id_usuario' => $usuario->id_usuario,
                        'fecha_aprobacion' => now()
                    ]);
                }

                if ($data['rol'] === 'vecino') {
                    DB::table('vecino')->insert([
                        'id_usuario' => $usuario->id_usuario
                    ]);
                }

                return $usuario;
            });

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado correctamente.',
                'id_usuario' => $usuario->id_usuario
            ], 201);

        } catch (\Throwable $error) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el usuario.'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $error = $this->verificarGestion($request);

        if ($error) {
            return $error;
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

        $email = strtolower(trim($data['email']));
        $documento = trim($data['documento']);

        if (
            Dato::where('email', $email)
                ->where('id_datos', '!=', $dato->id_datos)
                ->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'El correo ya está registrado.'
            ], 422);
        }

        if (
            Dato::where('documento', $documento)
                ->where('id_datos', '!=', $dato->id_datos)
                ->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'El documento ya está registrado.'
            ], 422);
        }

        DB::transaction(function () use (
            $usuario,
            $dato,
            $data,
            $email,
            $documento
        ) {
            $usuario->update([
                'nombre' => trim($data['nombre']),
                'apellido' => trim($data['apellido']),
                'estado' => $data['estado']
            ]);

            $dato->update([
                'documento' => $documento,
                'telefono' => trim($data['telefono']),
                'email' => $email
            ]);

            if ($usuario->rol?->nombre === 'chofer') {
                DB::table('chofer')->updateOrInsert(
                    [
                        'id_usuario' => $usuario->id_usuario
                    ],
                    [
                        'licencia' => trim($data['licencia'] ?? ''),
                        'fecha_aprobacion' => now()
                    ]
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente.'
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $error = $this->verificarGestion($request);

        if ($error) {
            return $error;
        }

        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no existe.'
            ], 404);
        }

        $usuarioSesion = $request->session()->get('usuario');

        if (
            (int) ($usuarioSesion['id'] ?? 0) ===
            (int) $usuario->id_usuario
        ) {
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