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
        $datos = $request->validate([
            'email' => ['required', 'email', 'max:150'],
        ]);

        $email = strtolower(trim($datos['email']));
        $mensaje = 'Si el correo está registrado y activo, recibirás un código de recuperación.';
        $dato = Dato::where('email', $email)->first();

        if (!$dato) {
            return response()->json([
                'success' => true,
                'message' => $mensaje
            ]);
        }

        $usuario = DB::table('usuarios')
            ->where('id_usuario', $dato->id_usuario)
            ->where('estado', 'activo')
            ->first();

        if (!$usuario) {
            return response()->json([
                'success' => true,
                'message' => $mensaje
            ]);
        }

        $codigo = str_pad(
            (string) random_int(0, 99999999),
            8,
            '0',
            STR_PAD_LEFT
        );

        $codigoHash = hash('sha256', $codigo);
        $idSolicitud = null;

        try {
            $idSolicitud = DB::transaction(function () use ($usuario, $codigoHash) {
                DB::table('rec_password')
                    ->where('id_usuario', $usuario->id_usuario)
                    ->where('estado', 'pendiente')
                    ->update(['estado' => 'reemplazada']);

                return DB::table('rec_password')->insertGetId([
                    'id_usuario' => $usuario->id_usuario,
                    'codigo' => $codigoHash,
                    'estado' => 'pendiente',
                    'fecha_solicitud' => now(),
                    'fecha_expiracion' => now()->addMinutes(15),
                ]);
            });

            $nombre = trim(
                ($usuario->nombre ?? '') . ' ' .
                ($usuario->apellido ?? '')
            );

            Mail::raw(
                "Hola {$nombre}:\n\n" .
                "Solicitaste recuperar tu contraseña de SiGeRu.\n\n" .
                "Tu código de recuperación es: {$codigo}\n\n" .
                "Instrucciones:\n" .
                "1. Abrí la página Recuperar contraseña de SiGeRu.\n" .
                "2. Debajo de Solicitar recuperación, ingresá tu correo y el código recibido.\n" .
                "3. Presioná Verificar código y continuar.\n" .
                "4. En la página siguiente, escribí y confirmá tu nueva contraseña.\n\n" .
                "El código vence en 15 minutos y solo puede utilizarse una vez.\n" .
                "Si no solicitaste este cambio, ignorá este correo.",
                function ($correo) use ($email) {
                    $correo
                        ->to($email)
                        ->subject('Código de recuperación - SiGeRu');
                }
            );

            return response()->json([
                'success' => true,
                'message' => $mensaje
            ]);

        } catch (\Throwable $error) {
            if ($idSolicitud !== null) {
                DB::table('rec_password')
                    ->where('id_solicitud', $idSolicitud)
                    ->update(['estado' => 'error_envio']);
            }

            Log::error(
                'Error al enviar código de recuperación SiGeRu.',
                ['error' => $error->getMessage()]
            );

            return response()->json([
                'success' => false,
                'message' => 'No se pudo procesar la recuperación. Intentá nuevamente.'
            ], 500);
        }
    }

    public function verificar(Request $request)
    {
        $datos = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'codigo' => ['required', 'regex:/^[0-9]{8}$/'],
        ]);

        $email = strtolower(trim($datos['email']));

        if (!$this->codigoVigente($email, $datos['codigo'])) {
            return response()->json([
                'success' => false,
                'message' => 'Código incorrecto, vencido o ya utilizado.'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Código verificado. Ya podés crear una nueva contraseña.'
        ]);
    }

    public function restablecer(Request $request)
    {
        $datos = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'codigo' => ['required', 'regex:/^[0-9]{8}$/'],
            'password' => [
                'required', 'string',
                'min:8', 'max:255', 'confirmed'
            ],
        ]);

        $email = strtolower(trim($datos['email']));
        $hashCodigo = hash('sha256', $datos['codigo']);

        try {
            $actualizado = DB::transaction(
                function () use ($email, $hashCodigo, $datos) {
                    $dato = Dato::where('email', $email)->first();

                    if (!$dato) {
                        return false;
                    }

                    $usuarioActivo = DB::table('usuarios')
                        ->where('id_usuario', $dato->id_usuario)
                        ->where('estado', 'activo')
                        ->exists();

                    if (!$usuarioActivo) {
                        return false;
                    }

                    $solicitud = DB::table('rec_password')
                        ->where('id_usuario', $dato->id_usuario)
                        ->where('codigo', $hashCodigo)
                        ->where('estado', 'pendiente')
                        ->where('fecha_expiracion', '>', now())
                        ->lockForUpdate()
                        ->first();

                    if (!$solicitud) {
                        return false;
                    }

                    DB::table('datos')
                        ->where('id_usuario', $dato->id_usuario)
                        ->update([
                            'password' => Hash::make($datos['password'])
                        ]);

                    DB::table('rec_password')
                        ->where('id_solicitud', $solicitud->id_solicitud)
                        ->update(['estado' => 'utilizada']);

                    DB::table('rec_password')
                        ->where('id_usuario', $dato->id_usuario)
                        ->where('estado', 'pendiente')
                        ->update(['estado' => 'reemplazada']);

                    return true;
                }
            );

            if (!$actualizado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Código incorrecto, vencido o ya utilizado.'
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'La contraseña fue actualizada correctamente. Ya podés iniciar sesión.'
            ]);

        } catch (\Throwable $error) {
            Log::error(
                'Error al restablecer contraseña SiGeRu.',
                ['error' => $error->getMessage()]
            );

            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar la contraseña.'
            ], 500);
        }
    }

    private function codigoVigente(string $email, string $codigo): bool
    {
        $dato = Dato::where('email', $email)->first();

        if (!$dato) {
            return false;
        }

        $usuarioActivo = DB::table('usuarios')
            ->where('id_usuario', $dato->id_usuario)
            ->where('estado', 'activo')
            ->exists();

        if (!$usuarioActivo) {
            return false;
        }

        return DB::table('rec_password')
            ->where('id_usuario', $dato->id_usuario)
            ->where('codigo', hash('sha256', $codigo))
            ->where('estado', 'pendiente')
            ->where('fecha_expiracion', '>', now())
            ->exists();
    }
}
