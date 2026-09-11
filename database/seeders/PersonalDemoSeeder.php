<?php

namespace Database\Seeders;

use App\Models\Dato;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PersonalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $personal = [
            [
                'rol' => 'chofer',
                'nombre' => 'Albert',
                'apellido' => 'Einstein',
                'documento' => '4.111.111-1',
                'telefono' => '099111111',
                'email' => 'albert@sigeru.uy',
                'licencia' => 'CAT-C-3265'
            ],
            [
                'rol' => 'chofer',
                'nombre' => 'Leonardo',
                'apellido' => 'da Vinci',
                'documento' => '4.222.222-2',
                'telefono' => '099222222',
                'email' => 'leonardo@sigeru.uy',
                'licencia' => 'CAT-C-5268'
            ],
            [
                'rol' => 'chofer',
                'nombre' => 'Frida',
                'apellido' => 'Kahlo',
                'documento' => '4.333.333-3',
                'telefono' => '099333333',
                'email' => 'frida@sigeru.uy',
                'licencia' => 'CAT-C-1235'
            ],
            [
                'rol' => 'chofer',
                'nombre' => 'Julio',
                'apellido' => 'César',
                'documento' => '4.666.666-6',
                'telefono' => '099666666',
                'email' => 'julio@sigeru.uy',
                'licencia' => 'CAT-C-5632'
            ],
            [
                'rol' => 'operario',
                'nombre' => 'María',
                'apellido' => 'Pérez',
                'documento' => '4.444.444-4',
                'telefono' => '099444444',
                'email' => 'maria@sigeru.uy'
            ],
            [
                'rol' => 'operario',
                'nombre' => 'Carlos',
                'apellido' => 'Silva',
                'documento' => '4.555.555-5',
                'telefono' => '099555555',
                'email' => 'carlos@sigeru.uy'
            ],
            [
                'rol' => 'operario',
                'nombre' => 'Lucía',
                'apellido' => 'Rodríguez',
                'documento' => '4.777.777-7',
                'telefono' => '099777777',
                'email' => 'lucia@sigeru.uy'
            ],
            [
                'rol' => 'barrendero',
                'nombre' => 'Edinson',
                'apellido' => 'Cavani',
                'documento' => '5.101.201-1',
                'telefono' => '098301401',
                'email' => 'edinson.cavani@sigeru.uy'
            ],
            [
                'rol' => 'barrendero',
                'nombre' => 'Federico',
                'apellido' => 'Valverde',
                'documento' => '5.102.202-2',
                'telefono' => '098302402',
                'email' => 'federico.valverde@sigeru.uy'
            ],
            [
                'rol' => 'barrendero',
                'nombre' => 'Valeria',
                'apellido' => 'Ripoll',
                'documento' => '5.103.203-3',
                'telefono' => '098303403',
                'email' => 'valeria.ripoll@sigeru.uy'
            ],
            [
                'rol' => 'barrendero',
                'nombre' => 'Victoria',
                'apellido' => 'Rodríguez',
                'documento' => '5.104.204-4',
                'telefono' => '098304404',
                'email' => 'victoria.rodriguez@sigeru.uy'
            ],
            [
                'rol' => 'barrendero',
                'nombre' => 'Rodrigo',
                'apellido' => 'Bentancur',
                'documento' => '5.105.205-5',
                'telefono' => '098305405',
                'email' => 'rodrigo.bentancur@sigeru.uy'
            ],
            [
                'rol' => 'barrendero',
                'nombre' => 'José',
                'apellido' => 'María Giménez',
                'documento' => '5.106.206-6',
                'telefono' => '098306406',
                'email' => 'jose.maria.gimenez@sigeru.uy'
            ],
            [
                'rol' => 'barrendero',
                'nombre' => 'Sergio',
                'apellido' => 'Rochet',
                'documento' => '5.107.207-7',
                'telefono' => '098307407',
                'email' => 'sergio.rochet@sigeru.uy'
            ],
            [
                'rol' => 'barrendero',
                'nombre' => 'Nicolás',
                'apellido' => 'de la Cruz',
                'documento' => '5.108.208-8',
                'telefono' => '098308408',
                'email' => 'nicolas.de.la.cruz@sigeru.uy'
            ],
            [
                'rol' => 'barrendero',
                'nombre' => 'Delmira',
                'apellido' => 'Agustini',
                'documento' => '5.109.209-9',
                'telefono' => '098309409',
                'email' => 'delmira.agustini@sigeru.uy'
            ],
            [
                'rol' => 'barrendero',
                'nombre' => 'Idea',
                'apellido' => 'Vilariño',
                'documento' => '5.110.210-0',
                'telefono' => '098310410',
                'email' => 'idea.vilarino@sigeru.uy'
            ],
            [
                'rol' => 'barrendero',
                'nombre' => 'Mario',
                'apellido' => 'Benedetti',
                'documento' => '5.111.211-1',
                'telefono' => '098311411',
                'email' => 'mario.benedetti@sigeru.uy'
            ],
            [
                'rol' => 'barrendero',
                'nombre' => 'Amanda',
                'apellido' => 'Berenguer',
                'documento' => '5.112.212-2',
                'telefono' => '098312412',
                'email' => 'amanda.berenguer@sigeru.uy'
            ],
            [
                'rol' => 'barrendero',
                'nombre' => 'Estela',
                'apellido' => 'Medina',
                'documento' => '5.113.213-3',
                'telefono' => '098313413',
                'email' => 'estela.medina@sigeru.uy'
            ],
            [
                'rol' => 'barrendero',
                'nombre' => 'Antonio',
                'apellido' => 'Larreta',
                'documento' => '5.114.214-4',
                'telefono' => '098314414',
                'email' => 'antonio.larreta@sigeru.uy'
            ],
            [
                'rol' => 'barrendero',
                'nombre' => 'Dahd',
                'apellido' => 'Sfeir',
                'documento' => '5.115.215-5',
                'telefono' => '098315415',
                'email' => 'dahd.sfeir@sigeru.uy'
            ],
            [
                'rol' => 'barrendero',
                'nombre' => 'Walter',
                'apellido' => 'Reyno',
                'documento' => '5.116.216-6',
                'telefono' => '098316416',
                'email' => 'walter.reyno@sigeru.uy'
            ]
        ];

        DB::transaction(function () use ($personal) {
            foreach ($personal as $persona) {
                $rol = Rol::where('nombre', $persona['rol'])->first();

                if (!$rol) {
                    continue;
                }

                $dato = Dato::where('email', $persona['email'])
                    ->orWhere('documento', $persona['documento'])
                    ->first();

                if ($dato) {
                    $usuario = Usuario::find($dato->id_usuario);

                    if (!$usuario) {
                        continue;
                    }

                    $usuario->update([
                        'id_rol' => $rol->id_rol,
                        'nombre' => $persona['nombre'],
                        'apellido' => $persona['apellido'],
                        'estado' => 'activo'
                    ]);

                    $dato->update([
                        'documento' => $persona['documento'],
                        'telefono' => $persona['telefono'],
                        'email' => $persona['email']
                    ]);
                } else {
                    $usuario = Usuario::create([
                        'id_rol' => $rol->id_rol,
                        'nombre' => $persona['nombre'],
                        'apellido' => $persona['apellido'],
                        'estado' => 'activo'
                    ]);

                    Dato::create([
                        'id_usuario' => $usuario->id_usuario,
                        'password' => Hash::make('Sigeru123'),
                        'documento' => $persona['documento'],
                        'telefono' => $persona['telefono'],
                        'email' => $persona['email']
                    ]);
                }

                if ($persona['rol'] === 'chofer') {
                    DB::table('chofer')->updateOrInsert(
                        [
                            'id_usuario' => $usuario->id_usuario
                        ],
                        [
                            'licencia' => $persona['licencia'],
                            'fecha_aprobacion' => now()
                        ]
                    );
                }

                if ($persona['rol'] === 'operario') {
                    DB::table('operario')->updateOrInsert(
                        [
                            'id_usuario' => $usuario->id_usuario
                        ],
                        [
                            'fecha_aprobacion' => now()
                        ]
                    );
                }

                if ($persona['rol'] === 'barrendero') {
                    DB::table('barrendero')->updateOrInsert(
                        [
                            'id_usuario' => $usuario->id_usuario
                        ],
                        [
                            'fecha_aprobacion' => now()
                        ]
                    );
                }
            }
        });
    }
}