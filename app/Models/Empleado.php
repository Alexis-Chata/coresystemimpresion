<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empleado extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'codigo',
        'name',
        'direccion',
        'celular',
        'f_tipo_documento_id',
        'numero_documento',
        'tipo_empleado',
        'numero_brevete',
        'empresa_id',
        'vehiculo_id',
        'f_sede_id',
    ];

    public function tipoDocumento()
    {
        return $this->belongsTo(FTipoDocumento::class, 'f_tipo_documento_id');
    }
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function fSede()
    {
        return $this->belongsTo(FSede::class, 'f_sede_id');
    }
}
