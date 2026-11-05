<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ConsultaController extends Controller
{
    public function enviar(Request $request)
    {
        $datos = $request->validate([
            'nombre' => [
                'required', 'string', 'min:2', 'max:100',
                'regex:/^[\p{L}\p{M} .\'-]+$/u'
            ],
            'email' => ['required', 'email', 'max:150'],
            'asunto' => [
                'required',
                Rule::in([
                    'Consulta sobre acceso',
                    'Ayuda para realizar un reporte',
                    'Información general',
                ]),
            ],
            'mensaje' => [
                'required', 'string', 'min:5', 'max:3000'
            ],
        ]);

        $destinatario = config('mail.consultas_to');

        if (
            !is_string($destinatario) ||
            !filter_var($destinatario, FILTER_VALIDATE_EMAIL)
        ) {
            Log::warning(
                'No hay una dirección de consultas válida configurada.'
            );

            return response()->json([
                'success' => false,
                'message' => 'El correo de consultas no está configurado.'
            ], 503);
        }

        $nombre = trim($datos['nombre']);
        $email = trim($datos['email']);
        $asunto = $datos['asunto'];
        $texto = trim($datos['mensaje']);

        $cuerpo = "Nueva consulta desde SiGeRu\n\n"
            . "Nombre: {$nombre}\n"
            . "Correo de contacto: {$email}\n"
            . "Categoría: {$asunto}\n\n"
            . "Mensaje:\n{$texto}\n";

        try {
            Mail::raw(
                $cuerpo,
                function ($correo) use (
                    $destinatario,
                    $nombre,
                    $email,
                    $asunto
                ) {
                    $correo
                        ->to($destinatario)
                        ->replyTo($email, $nombre)
                        ->subject($asunto);
                }
            );
        } catch (\Throwable $error) {
            Log::error(
                'Error al enviar una consulta desde SiGeRu.',
                ['detalle' => $error->getMessage()]
            );

            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar la consulta. Intentá nuevamente más tarde.'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Consulta enviada correctamente.'
        ]);
    }
}