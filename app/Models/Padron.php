<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Padron extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cliente_id',
        'ruta_id',
        'nro_secuencia',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function ruta()
    {
        return $this->belongsTo(Ruta::class);
    }
}
