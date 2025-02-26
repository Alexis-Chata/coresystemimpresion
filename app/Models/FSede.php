<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FSede extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "telefono",
        "direccion",
        "departamento",
        "provincia",
        "distrito",
        "ubigueo",
        "addresstypecode",
        "empresa_id",
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function almacen()
    {
        return $this->hasOne(Almacen::class);
    }

    public function fSeries()
    {
        return $this->hasMany(FSerie::class);
    }
}
