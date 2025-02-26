<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FTipoComprobante extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_comprobante',
        'name',
        'estado',
    ];

    // Si quieres que el campo 'estado' sea siempre un booleano, puedes agregar este cast:
    protected $casts = [
        'estado' => 'boolean',
    ];
}
