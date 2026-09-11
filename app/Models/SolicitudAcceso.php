<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudAcceso extends Model
{
    protected $table = 'solicitudes_acceso';

    protected $primaryKey = 'id_solicitud';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'apellido',
        'documento',
        'telefono',
        'email',
        'password',
        'perfil',
        'licencia',
        'motivo',
        'estado',
        'fecha_resolucion',
        'id_supervisor_resuelve'
    ];

    protected $hidden = [
        'password'
    ];

    protected $casts = [
        'fecha_solicitud' => 'datetime',
        'fecha_resolucion' => 'datetime'
    ];
}