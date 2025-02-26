<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FComprobanteSunatDetalle extends Model
{
    use HasFactory;

    protected $fillable = [
        "codProducto",
        "unidad",
        "cantidad",
        "descripcion",
        "mtoBaseIgv",
        "porcentajeIgv",
        "igv",
        "totalImpuestos",
        "tipAfeIgv",
        "mtoValorVenta",
        "mtoValorUnitario",
        "mtoValorGratuito",
        "mtoPrecioUnitario",
        "factorIcbper",
        "icbper",
        "mtoBaseIsc",
        "tipSisIsc",
        "porcentajeIsc",
        "isc",
        "f_comprobante_sunat_id",
        "ref_producto_lista_precio",
        "ref_producto_precio_cajon",
        "ref_producto_cantidad_cajon",
        "ref_producto_cant_vendida",
    ];

    public function comprobante()
    {
        return $this->belongsTo(FComprobanteSunat::class, 'f_comprobante_sunat_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'codProducto');
    }
}
