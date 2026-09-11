<?php

namespace App\Http\Controllers;

use App\Models\Dato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RecuperacionPasswordController extends Controller
{
    public function solicitar(Request $request)
    {
        $datos = $request->validate(
            [
                'email' => [
                    'required',
                    'email',
                    'max:150'
                ]
            ],
            [
                'email.required' =>
                    'El correo electrónico es obligatorio.',

                'email.email' =>
                    'Ingresá un correo electrónico válido.',

                'email.max' =>
                    'El correo electrónico no puede superar los 150 caracteres.'
            ]
        );

        $email =
            strtolower(
                trim($datos['email'])
            );

        $mensaje =
            'Si el correo está registrado, recibirás instrucciones para restablecer tu contraseña.';

        $dato =
            Dato::where(
                'email',
                $email
            )->first();

        if (!$dato) {
            return response()->json([
                'success' => true,
                'message' => $mensaje
            ]);
        }

        $usuario =
            DB::table('usuarios')
                ->where(
                    'id_usuario',
                    $dato->id_usuario
                )
                ->where(
                    'estado',
                    'activo'
                )
                ->first();

        if (!$usuario) {
            return response()->json([
                'success' => true,
                'message' => $mensaje
            ]);
        }

        $token =
            bin2hex(
                random_bytes(32)
            );

        $codigo =
            hash(
                'sha256',
                $token
            );

        try {
            $idSolicitud =
                DB::transaction(
                    function () use (
                        $usuario,
                        $codigo
                    ) {
                        
                        DB::table(
                            'rec_password'
                        )
                            ->where(
                                'id_usuario',
                                $usuario->id_usuario
                            )
                            ->where(
                                'estado',
                                'pendiente'
                            )
                            ->update([
                                'estado' =>
                                    'reemplazada'
                            ]);

                        return DB::table(
                            'rec_password'
                        )->insertGetId([
                            'id_usuario' =>
                                $usuario->id_usuario,

                            'estado' =>
                                'pendiente',

                            'codigo' =>
                                $codigo,

                            'fecha_solicitud' =>
                                now(),

                            'fecha_expiracion' =>
                                now()->addHour()
                        ]);
                    }
                );

            $frontend =
                rtrim(
                    config(
                        'sigeru.frontend_url'
                    ),
                    '/'
                );

            $enlace =
                $frontend .
                '/landing-page/restablecer-password.html?token=' .
                urlencode($token);

            $nombre =
                trim(
                    ($usuario->nombre ?? '') .
                    ' ' .
                    ($usuario->apellido ?? '')
                );

            Mail::raw(
                "Hola {$nombre}.\n\n" .
                "Recibimos una solicitud para cambiar la contraseña de tu cuenta de SiGeRu.\n\n" .
                "Abrí el siguiente enlace para crear una nueva contraseña:\n\n" .
                "{$enlace}\n\n" .
                "El enlace es válido durante 1 hora.\n\n" .
                "Si no solicitaste este cambio, podés ignorar este mensaje.",

                function ($message) use (
                    $email
                ) {
                    $message
                        ->to($email)
                        ->subject(
                            'Recuperación de contraseña - SiGeRu'
                        );
                }
            );

            return response()->json([
                'success' => true,
                'message' => $mensaje
            ]);

        } catch (\Throwable $error) {
            if (
                isset($idSolicitud)
            ) {
                DB::table(
                    'rec_password'
                )
                    ->where(
                        'id_solicitud',
                        $idSolicitud
                    )
                    ->update([
                        'estado' =>
                            'error_envio'
                    ]);
            }

            Log::error(
                'Error al solicitar recuperación de contraseña.',
                [
                    'mensaje' =>
                        $error->getMessage()
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    'No se pudo procesar la recuperación de contraseña. Intentá nuevamente.'
            ], 500);
        }
    }


    public function restablecer(
        Request $request
    ) {
        $datos = $request->validate(
            [
                'token' => [
                    'required',
                    'string',
                    'size:64'
                ],

                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'max:255',
                    'confirmed'
                ]
            ],
            [
                'token.required' =>
                    'El enlace de recuperación no es válido.',

                'token.size' =>
                    'El enlace de recuperación no es válido.',

                'password.required' =>
                    'La nueva contraseña es obligatoria.',

                'password.min' =>
                    'La contraseña debe tener al menos 8 caracteres.',

                'password.max' =>
                    'La contraseña es demasiado larga.',

                'password.confirmed' =>
                    'Las contraseñas no coinciden.'
            ]
        );

        $codigo =
            hash(
                'sha256',
                $datos['token']
            );

        $solicitud =
            DB::table(
                'rec_password'
            )
                ->where(
                    'codigo',
                    $codigo
                )
                ->where(
                    'estado',
                    'pendiente'
                )
                ->where(
                    'fecha_expiracion',
                    '>',
                    now()
                )
                ->orderByDesc(
                    'id_solicitud'
                )
                ->first();

        if (!$solicitud) {
            return response()->json([
                'success' => false,
                'message' =>
                    'El enlace de recuperación no es válido o ya venció.'
            ], 422);
        }

        $usuario =
            DB::table('usuarios')
                ->where(
                    'id_usuario',
                    $solicitud->id_usuario
                )
                ->where(
                    'estado',
                    'activo'
                )
                ->first();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No se pudo restablecer la contraseña.'
            ], 422);
        }

        $dato =
            DB::table('datos')
                ->where(
                    'id_usuario',
                    $usuario->id_usuario
                )
                ->first();

        if (!$dato) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No se encontraron los datos de la cuenta.'
            ], 422);
        }

        try {
            DB::transaction(
                function () use (
                    $datos,
                    $usuario,
                    $solicitud
                ) {
                    DB::table('datos')
                        ->where(
                            'id_usuario',
                            $usuario->id_usuario
                        )
                        ->update([
                            'password' =>
                                Hash::make(
                                    $datos['password']
                                )
                        ]);

                    DB::table(
                        'rec_password'
                    )
                        ->where(
                            'id_solicitud',
                            $solicitud->id_solicitud
                        )
                        ->update([
                            'estado' =>
                                'utilizada'
                        ]);

                
                    DB::table(
                        'rec_password'
                    )
                        ->where(
                            'id_usuario',
                            $usuario->id_usuario
                        )
                        ->where(
                            'estado',
                            'pendiente'
                        )
                        ->update([
                            'estado' =>
                                'reemplazada'
                        ]);
                }
            );

            return response()->json([
                'success' => true,
                'message' =>
                    'La contraseña fue actualizada correctamente.'
            ]);

        } catch (\Throwable $error) {
            Log::error(
                'Error al restablecer contraseña.',
                [
                    'mensaje' =>
                        $error->getMessage()
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    'No se pudo actualizar la contraseña.'
            ], 500);
        }
    }
}