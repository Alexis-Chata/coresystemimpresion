<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'razon_social',
        'direccion',
        'f_tipo_documento_id',
        'numero_documento',
        'celular',
        'empresa_id',
        'lista_precio_id',
        'ruta_id',
        'ubigeo_inei',
    ];

    protected static function booted()
    {
        static::created(function ($cliente) {
            $ultimaSecuencia = Padron::where('ruta_id', $cliente->ruta_id)
                ->max('nro_secuencia') ?? 0;

            Padron::create([
                'cliente_id' => $cliente->id,
                'ruta_id' => $cliente->ruta_id,
                'nro_secuencia' => $ultimaSecuencia + 1
            ]);
        });

        static::updated(function ($cliente) {
            if ($cliente->wasChanged('ruta_id')) {
                $padron = Padron::where('cliente_id', $cliente->id)->first();

                if ($padron) {
                    $ultimaSecuencia = Padron::where('ruta_id', $cliente->ruta_id)
                        ->max('nro_secuencia') ?? 0;

                    $padron->update([
                        'ruta_id' => $cliente->ruta_id,
                        'nro_secuencia' => $ultimaSecuencia + 1
                    ]);
                }
            }
        });

        static::deleted(function ($cliente) {
            $padron = Padron::where('cliente_id', $cliente->id)->first();

            if ($padron) {
                $secuenciaEliminada = $padron->nro_secuencia;
                $rutaId = $padron->ruta_id;

                $padron->delete();

                Padron::where('ruta_id', $rutaId)
                    ->where('nro_secuencia', '>', $secuenciaEliminada)
                    ->decrement('nro_secuencia');
            }
        });
    }

    public function padron()
    {
        return $this->hasOne(Padron::class);
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(FTipoDocumento::class, 'f_tipo_documento_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function listaPrecio()
    {
        return $this->belongsTo(ListaPrecio::class);
    }

    public function ruta()
    {
        return $this->belongsTo(Ruta::class);
    }
}
