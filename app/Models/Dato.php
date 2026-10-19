<?php

namespace App\Models;

//  Se cambia "extends Model" por
// "extends Authenticatable implements JWTSubject" porque la tabla 'datos' es
// donde están guardados el 'email' y el 'password' para iniciar sesión con JWT.
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Dato extends Authenticatable implements JWTSubject
{
    protected $table = 'datos';

    protected $primaryKey = 'id_datos';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'password',
        'documento',
        'telefono',
        'email'
    ];

    protected $hidden = [
        'password'
    ];

    public function usuario()
    {
        return $this->belongsTo(
            Usuario::class,
            'id_usuario',
            'id_usuario'
        );
    }

    //  Devuelve la clave primaria (id_datos)
    // que se guardará dentro del payload del token JWT (en el claim 'sub').
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    //  Permite agregar datos personalizados
    // al payload del JWT. Guardamos id_usuario y rol para que cualquiera de las
    // dos APIs conozca la identidad y el rol sin depender de sesiones.
    public function getJWTCustomClaims(): array
    {
        $this->loadMissing('usuario.rol');

        return [
            'id_usuario' => $this->id_usuario,
            'rol' => $this->usuario?->rol?->nombre,
        ];
    }
}