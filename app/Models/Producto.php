<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'cantidad',
        'sub_cantidad',
        'peso',
        'tipo',
        'empresa_id',
        'marca_id',
        'categoria_id',
        'f_tipo_afectacion_id',
        'porcentaje_igv',
        'tipo_unidad',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function tipoAfectacion()
    {
        return $this->belongsTo(FTipoAfectacion::class, 'f_tipo_afectacion_id');
    }

    public function listaPrecios()
    {
        return $this->belongsToMany(ListaPrecio::class, 'producto_lista_precios')
            ->withPivot('precio')
            ->withTimestamps();
    }

    public function componentes()
    {
        return $this->hasMany(ProductoComponent::class);
    }

    public function componentProducts()
    {
        return $this->belongsToMany(Producto::class, 'producto_components', 'producto_id', 'component_id')
                    ->withPivot('cantidad', 'subcantidad', 'cantidad_total');
    }

    public function almacenProductos()
    {
        return $this->hasMany(AlmacenProducto::class);
    }
}
