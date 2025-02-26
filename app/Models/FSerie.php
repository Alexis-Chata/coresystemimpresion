<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FSerie extends Model
{
    use HasFactory;

    public $fillable = [
        "serie",
        "correlativo",
        "fechaemision",
        "f_sede_id",
        "f_tipo_comprobante_id",
    ];

    public function fSede()
    {
        return $this->belongsTo(FSede::class, 'f_sede_id');
    }

    public function fTipoComprobante()
    {
        return $this->belongsTo(FTipoComprobante::class, 'f_tipo_comprobante_id');
    }
}
