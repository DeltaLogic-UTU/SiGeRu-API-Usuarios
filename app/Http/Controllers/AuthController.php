<?php

namespace App\Http\Controllers;

use App\Models\Dato;
use App\Models\SolicitudAcceso;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate(
            [
                'usuario' => [
                    'required',
                    'email'
                ],

                'password' => [
                    'required',
                    'string'
                ]
            ],
            [
                'usuario.required' =>
                    'El correo electrónico es obligatorio.',

                'usuario.email' =>
                    'Ingresá un correo electrónico válido.',

                'password.required' =>
                    'La contraseña es obligatoria.',

                'password.string' =>
                    'La contraseña ingresada no es válida.'
            ]
        );

        $dato = Dato::with('usuario.rol')
            ->where(
                'email',
                strtolower(
                    trim($request->usuario)
                )
            )
            ->first();

        if (
            !$dato ||
            !Hash::check(
                $request->password,
                $dato->password
            )
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Usuario o contraseña incorrectos.',
                'data' => null
            ], 401);
        }

        $usuario = $dato->usuario;

        if (
            $usuario->estado !==
            'activo'
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'El usuario se encuentra inactivo.',
                'data' => null
            ], 403);
        }

        $request
            ->session()
            ->regenerate();

        $request
            ->session()
            ->put(
                'usuario',
                [
                    'id_usuario' =>
                        $usuario->id_usuario,

                    'nombre' =>
                        $usuario->nombre,

                    'apellido' =>
                        $usuario->apellido,

                    'email' =>
                        $dato->email,

                    'rol' =>
                        $usuario->rol->nombre
                ]
            );

        return response()->json([
            'success' => true,

            'message' =>
                'Inicio de sesión correcto.',

            'data' => [
                'id_usuario' =>
                    $usuario->id_usuario,

                'nombre' =>
                    $usuario->nombre .
                    ' ' .
                    $usuario->apellido,

                'email' =>
                    $dato->email
            ],

            'rol' =>
                $usuario->rol->nombre
        ]);
    }


    public function session(Request $request)
    {
        $usuario =
            $request
                ->session()
                ->get('usuario');

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No hay una sesión activa.'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'usuario' => $usuario
        ]);
    }


    public function logout(Request $request)
    {
        $request
            ->session()
            ->forget('usuario');

        $request
            ->session()
            ->invalidate();

        $request
            ->session()
            ->regenerateToken();

        return response()->json([
            'success' => true,
            'message' =>
                'Sesión cerrada correctamente.'
        ]);
    }


    public function registro(Request $request)
    {
        $datos =
            $request->validate(
                [
                    'perfil' => [
                        'required',
                        'in:vecino,cuadrilla,operario,chofer'
                    ],

                    'nombre' => [
                        'required',
                        'string',
                        'max:80'
                    ],

                    'apellido' => [
                        'required',
                        'string',
                        'max:80'
                    ],

                    'documento' => [
                        'required',
                        'string',
                        'regex:/^\d\.\d{3}\.\d{3}-\d$/'
                    ],

                    'telefono' => [
                        'required',
                        'string',
                        'regex:/^09\d\s?\d{3}\s?\d{3}$/'
                    ],

                    'email' => [
                        'required',
                        'email',
                        'max:120'
                    ],

                    'password' => [
                        'required',
                        'string',
                        'min:8',
                        'max:255'
                    ],

                    'licencia' => [
                        'nullable',
                        'string',
                        'max:50',
                        'required_if:perfil,chofer'
                    ],

                    'motivo' => [
                        'required',
                        'string',
                        'max:500'
                    ]
                ],
                [
                    'perfil.required' =>
                        'Debés seleccionar un perfil.',

                    'perfil.in' =>
                        'El perfil seleccionado no es válido.',


                    'nombre.required' =>
                        'El nombre es obligatorio.',

                    'nombre.string' =>
                        'El nombre ingresado no es válido.',

                    'nombre.max' =>
                        'El nombre no puede superar los 80 caracteres.',


                    'apellido.required' =>
                        'El apellido es obligatorio.',

                    'apellido.string' =>
                        'El apellido ingresado no es válido.',

                    'apellido.max' =>
                        'El apellido no puede superar los 80 caracteres.',


                    'documento.required' =>
                        'La cédula es obligatoria.',

                    'documento.string' =>
                        'La cédula ingresada no es válida.',

                    'documento.regex' =>
                        'La cédula debe tener el formato 5.000.000-0.',


                    'telefono.required' =>
                        'El teléfono es obligatorio.',

                    'telefono.string' =>
                        'El teléfono ingresado no es válido.',

                    'telefono.regex' =>
                        'El teléfono debe tener el formato 09X XXX XXX.',


                    'email.required' =>
                        'El correo electrónico es obligatorio.',

                    'email.email' =>
                        'Ingresá un correo electrónico válido.',

                    'email.max' =>
                        'El correo electrónico no puede superar los 120 caracteres.',


                    'password.required' =>
                        'La contraseña es obligatoria.',

                    'password.string' =>
                        'La contraseña ingresada no es válida.',

                    'password.min' =>
                        'La contraseña debe tener al menos 8 caracteres.',

                    'password.max' =>
                        'La contraseña no puede superar los 255 caracteres.',


                    'licencia.required_if' =>
                        'La licencia es obligatoria para el perfil de chofer.',

                    'licencia.string' =>
                        'La licencia ingresada no es válida.',

                    'licencia.max' =>
                        'La licencia no puede superar los 50 caracteres.',


                    'motivo.required' =>
                        'El motivo de la solicitud es obligatorio.',

                    'motivo.string' =>
                        'El motivo ingresado no es válido.',

                    'motivo.max' =>
                        'El motivo no puede superar los 500 caracteres.'
                ]
            );

        $email =
            strtolower(
                trim($datos['email'])
            );

        $documento =
            trim(
                $datos['documento']
            );

        if (
            Dato::where(
                'email',
                $email
            )->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Ya existe un usuario registrado con ese correo.'
            ], 409);
        }

        if (
            Dato::where(
                'documento',
                $documento
            )->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Ya existe un usuario registrado con esa cédula.'
            ], 409);
        }

        if (
            SolicitudAcceso::where(
                'email',
                $email
            )
                ->where(
                    'estado',
                    'pendiente'
                )
                ->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Ya existe una solicitud pendiente con ese correo.'
            ], 409);
        }

        $solicitud =
            SolicitudAcceso::create([
                'nombre' =>
                    trim(
                        $datos['nombre']
                    ),

                'apellido' =>
                    trim(
                        $datos['apellido']
                    ),

                'documento' =>
                    $documento,

                'telefono' =>
                    trim(
                        $datos['telefono']
                    ),

                'email' =>
                    $email,

                'password' =>
                    Hash::make(
                        $datos['password']
                    ),

                'perfil' =>
                    $datos['perfil'],

                'licencia' =>
                    $datos['licencia']
                    ?? null,

                'motivo' =>
                    trim(
                        $datos['motivo']
                    ),

                'estado' =>
                    'pendiente'
            ]);

        return response()->json([
            'success' => true,

            'message' =>
                'Solicitud registrada correctamente.',

            'data' => [
                'id_solicitud' =>
                    $solicitud->id_solicitud,

                'estado' =>
                    $solicitud->estado
            ]
        ], 201);
    }


    public function perfil(Request $request)
    {
        $sesion =
            $request
                ->session()
                ->get('usuario');

        if (!$sesion) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No hay una sesión activa.'
            ], 401);
        }

        $usuario =
            Usuario::with([
                'rol',
                'datos'
            ])
                ->find(
                    $sesion['id_usuario']
                );

        if (
            !$usuario ||
            !$usuario->datos
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Usuario no encontrado.'
            ], 404);
        }

        return response()->json([
            'success' => true,

            'data' => [
                'id_usuario' =>
                    $usuario->id_usuario,

                'nombre' =>
                    $usuario->nombre,

                'apellido' =>
                    $usuario->apellido,

                'documento' =>
                    $usuario->datos->documento,

                'telefono' =>
                    $usuario->datos->telefono,

                'email' =>
                    $usuario->datos->email,

                'rol' =>
                    $usuario->rol->nombre,

                'estado' =>
                    $usuario->estado
            ]
        ]);
    }


    public function actualizarPerfil(Request $request)
    {
        $sesion =
            $request
                ->session()
                ->get('usuario');

        if (!$sesion) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No hay una sesión activa.'
            ], 401);
        }

        $usuario =
            Usuario::with('datos')
                ->find(
                    $sesion['id_usuario']
                );

        if (
            !$usuario ||
            !$usuario->datos
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Usuario no encontrado.'
            ], 404);
        }

        $datos =
            $request->validate(
                [
                    'nombre' => [
                        'required',
                        'string',
                        'max:80'
                    ],

                    'apellido' => [
                        'required',
                        'string',
                        'max:80'
                    ],

                    'documento' => [
                        'required',
                        'string',
                        'regex:/^\d\.\d{3}\.\d{3}-\d$/'
                    ],

                    'telefono' => [
                        'required',
                        'string',
                        'regex:/^09\d\s?\d{3}\s?\d{3}$/'
                    ],

                    'email' => [
                        'required',
                        'email',
                        'max:120'
                    ],

                    'password' => [
                        'nullable',
                        'string',
                        'min:8',
                        'max:255'
                    ],

                    'password_confirm' => [
                        'nullable',
                        'string',
                        'same:password'
                    ]
                ],
                [
                    'nombre.required' =>
                        'El nombre es obligatorio.',

                    'nombre.string' =>
                        'El nombre ingresado no es válido.',

                    'nombre.max' =>
                        'El nombre no puede superar los 80 caracteres.',


                    'apellido.required' =>
                        'El apellido es obligatorio.',

                    'apellido.string' =>
                        'El apellido ingresado no es válido.',

                    'apellido.max' =>
                        'El apellido no puede superar los 80 caracteres.',


                    'documento.required' =>
                        'La cédula es obligatoria.',

                    'documento.string' =>
                        'La cédula ingresada no es válida.',

                    'documento.regex' =>
                        'La cédula debe tener el formato 5.000.000-0.',


                    'telefono.required' =>
                        'El teléfono es obligatorio.',

                    'telefono.string' =>
                        'El teléfono ingresado no es válido.',

                    'telefono.regex' =>
                        'El teléfono debe tener el formato 09X XXX XXX.',


                    'email.required' =>
                        'El correo electrónico es obligatorio.',

                    'email.email' =>
                        'Ingresá un correo electrónico válido.',

                    'email.max' =>
                        'El correo electrónico no puede superar los 120 caracteres.',


                    'password.string' =>
                        'La contraseña ingresada no es válida.',

                    'password.min' =>
                        'La contraseña debe tener al menos 8 caracteres.',

                    'password.max' =>
                        'La contraseña no puede superar los 255 caracteres.',


                    'password_confirm.string' =>
                        'La confirmación de contraseña no es válida.',

                    'password_confirm.same' =>
                        'Las contraseñas no coinciden.'
                ]
            );

        $email =
            strtolower(
                trim($datos['email'])
            );

        $documento =
            trim(
                $datos['documento']
            );

        if (
            Dato::where(
                'email',
                $email
            )
                ->where(
                    'id_datos',
                    '!=',
                    $usuario->datos->id_datos
                )
                ->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'El correo ya está registrado.'
            ], 409);
        }

        if (
            Dato::where(
                'documento',
                $documento
            )
                ->where(
                    'id_datos',
                    '!=',
                    $usuario->datos->id_datos
                )
                ->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'La cédula ya está registrada.'
            ], 409);
        }

        DB::transaction(
            function () use (
                $usuario,
                $datos,
                $email,
                $documento
            ) {
                $usuario->update([
                    'nombre' =>
                        trim(
                            $datos['nombre']
                        ),

                    'apellido' =>
                        trim(
                            $datos['apellido']
                        )
                ]);

                $actualizacion = [
                    'documento' =>
                        $documento,

                    'telefono' =>
                        trim(
                            $datos['telefono']
                        ),

                    'email' =>
                        $email
                ];

                if (
                    !empty(
                        $datos['password']
                    )
                ) {
                    $actualizacion[
                        'password'
                    ] =
                        Hash::make(
                            $datos['password']
                        );
                }

                $usuario
                    ->datos
                    ->update(
                        $actualizacion
                    );
            }
        );

        $request
            ->session()
            ->put(
                'usuario',
                [
                    'id_usuario' =>
                        $usuario->id_usuario,

                    'nombre' =>
                        trim(
                            $datos['nombre']
                        ),

                    'apellido' =>
                        trim(
                            $datos['apellido']
                        ),

                    'email' =>
                        $email,

                    'rol' =>
                        $sesion['rol']
                ]
            );

        return response()->json([
            'success' => true,
            'message' =>
                'Perfil actualizado correctamente.'
        ]);
    }
}