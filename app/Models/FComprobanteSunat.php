<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FComprobanteSunat extends Model
{
    use HasFactory;

    protected $fillable = [
        'ruta_id',
        'vendedor_id',
        'conductor_id',
        'cliente_id',
        'movimiento_id',
        'pedido_id',
        'pedido_obs',
        'pedido_fecha_factuacion',
        'sede_id',
        'ublVersion',
        'tipoDoc',
        'tipoDoc_name',
        'tipoOperacion',
        'serie',
        'correlativo',
        'fechaEmision',
        'formaPagoTipo',
        'tipoMoneda',
        'companyRuc',
        'companyRazonSocial',
        'companyNombreComercial',
        'companyAddressUbigueo',
        'companyAddressDepartamento',
        'companyAddressProvincia',
        'companyAddressDistrito',
        'companyAddressUrbanizacion',
        'companyAddressDireccion',
        'companyAddressCodLocal',
        'clientTipoDoc',
        'clientNumDoc',
        'clientRazonSocial',
        'clientDireccion',
        'mtoOperGravadas',
        'mtoOperInafectas',
        'mtoOperExoneradas',
        'mtoOperGratuitas',
        'mtoIGV',
        'mtoBaseIsc',
        'mtoISC',
        'icbper',
        'totalImpuestos',
        'valorVenta',
        'subTotal',
        'redondeo',
        'mtoImpVenta',
        'legendsCode',
        'legendsValue',
        'tipDocAfectado',
        'numDocfectado',
        'codMotivo',
        'desMotivo',
        'nombrexml',
        'xmlbase64',
        'hash',
        'cdrxml',
        'cdrbase64',
        'codigo_sunat',
        'mensaje_sunat',
        'obs',
        'estado_reporte',
        'estado_cpe_sunat',
        'empresa_id',
    ];

    public function ruta()
    {
        return $this->belongsTo(Ruta::class);
    }
    public function vendedor()
    {
        return $this->belongsTo(Empleado::class);
    }
    public function conductor()
    {
        return $this->belongsTo(Empleado::class);
    }
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
    public function movimiento()
    {
        return $this->belongsTo(Movimiento::class);
    }
    public function sede()
    {
        return $this->belongsTo(FSede::class);
    }
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
    public function detalle()
    {
        return $this->hasMany(FComprobanteSunatDetalle::class);
    }
    public function tipo_doc()
    {
        return $this->belongsTo(FTipoDocumento::class, "clientTipoDoc", "codigo");
    }
}
