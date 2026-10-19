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
    //  El login ahora autentica con
    // auth('api')->attempt(...) y devuelve un token JWT firmado (access_token) en vez
    // de guardar datos en $request->session().
    // Acepta el campo 'email' (como en el PDF del profesor) o 'usuario' (como envía
    // actualmente el formulario del frontend de SiGeRu).
    public function login(Request $request)
    {
        if ($request->filled('email') && !$request->filled('usuario')) {
            $request->merge(['usuario' => $request->input('email')]);
        }

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

        $email = strtolower(trim($request->usuario));

        //  auth('api')->attempt() busca el
        // registro por email en el provider 'datos' (modelo Dato), verifica el hash
        // de 'password' y, si coincide, genera y devuelve el token JWT firmado.
        $token = auth('api')->attempt([
            'email' => $email,
            'password' => $request->password,
        ]);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Usuario o contraseña incorrectos.',
                'data' => null
            ], 401);
        }

        /** @var Dato $dato */
        $dato = auth('api')->user();
        $dato->loadMissing('usuario.rol');
        $usuario = $dato->usuario;

        if (
            !$usuario ||
            $usuario->estado !== 'activo'
        ) {
            auth('api')->logout();

            return response()->json([
                'success' => false,
                'message' =>
                    'El usuario se encuentra inactivo.',
                'data' => null
            ], 403);
        }

        return $this->tokenResponse($token, $usuario, $dato);
    }

    //  Construye la respuesta JSON del login/refresh
    // con access_token, token_type ('bearer') y expires_in (en segundos), manteniendo
    // además los datos del usuario y el rol que utiliza la interfaz de SiGeRu.
    private function tokenResponse(string $token, ?Usuario $usuario = null, ?Dato $dato = null)
    {
        if (!$dato) {
            /** @var Dato|null $dato */
            $dato = auth('api')->user();
            $dato?->loadMissing('usuario.rol');
            $usuario = $dato?->usuario;
        }

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión correcto.',
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'id_usuario' => $usuario?->id_usuario,
                'nombre' => trim(($usuario?->nombre ?? '') . ' ' . ($usuario?->apellido ?? '')),
                'email' => $dato?->email,
            ],
            'rol' => $usuario?->rol?->nombre,
        ]);
    }

    //  Obtiene el usuario autenticado a partir
    // del token JWT enviado en el header "Authorization: Bearer <token>".
    public function session(Request $request)
    {
        /** @var Dato|null $dato */
        $dato = auth('api')->user();

        if (!$dato) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No hay una sesión activa.'
            ], 401);
        }

        $dato->loadMissing('usuario.rol');
        $usuario = $dato->usuario;

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No hay una sesión activa.'
            ], 401);
        }

        $datosUsuario = [
            'id_usuario' => $usuario->id_usuario,
            'nombre' => $usuario->nombre,
            'apellido' => $usuario->apellido,
            'email' => $dato->email,
            'rol' => $usuario->rol?->nombre,
        ];

        return response()->json([
            'success' => true,
            'usuario' => $datosUsuario,
            'data' => $datosUsuario,
        ]);
    }

    //  Alias me() igual al PDF del profesor
    // para consultar quién es el usuario autenticado con GET /api/auth/me.
    public function me(Request $request)
    {
        return $this->session($request);
    }

    // Permite renovar el token JWT antes de
    // que venza usando POST /api/auth/refresh.
    public function refresh()
    {
        return $this->tokenResponse(auth('api')->refresh());
    }

    // Invalida el token JWT actual en el servidor
    // (lo agrega a la lista negra de jwt-auth) en lugar de borrar la sesión de cookies.
    public function logout(Request $request)
    {
        try {
            if (auth('api')->check()) {
                auth('api')->logout();
            }
        } catch (\Throwable $e) {
            // Si el token ya había expirado o no fue enviado, igualmente damos por cerrada la sesión.
        }

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


    // : En vez de leer $request->session()->get('usuario'),
    // obtenemos el registro autenticado desde el token JWT con auth('api')->user().
    public function perfil(Request $request)
    {
        /** @var Dato|null $datoAutenticado */
        $datoAutenticado = auth('api')->user();

        if (!$datoAutenticado) {
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
                    $datoAutenticado->id_usuario
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


    //  Se obtiene el usuario desde el token JWT con auth('api')->user()
    // y se elimina la escritura en $request->session()->put('usuario', ...).
    public function actualizarPerfil(Request $request)
    {
        /** @var Dato|null $datoAutenticado */
        $datoAutenticado = auth('api')->user();

        if (!$datoAutenticado) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No hay una sesión activa.'
            ], 401);
        }

        $usuario =
            Usuario::with('datos')
                ->find(
                    $datoAutenticado->id_usuario
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

        //  Ya no se actualiza $request->session()->put('usuario', ...)
        // porque la API es stateless con JWT.

        return response()->json([
            'success' => true,
            'message' =>
                'Perfil actualizado correctamente.'
        ]);
    }
}