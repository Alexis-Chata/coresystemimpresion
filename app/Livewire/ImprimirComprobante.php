<?php

namespace App\Livewire;

use App\Models\FComprobanteSunat;
use App\Models\FSerie;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Luecano\NumeroALetras\NumeroALetras;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

class ImprimirComprobante extends Component
{
    public function render()
    {
        return view('livewire.imprimir-comprobante');
    }

    public $series;
    public $impresoras = [];

    public function mount()
    {
        $sede_id = auth_user()->f_sede_id;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('API_TOKEN'),
            'Accept' => 'application/json',
        ])->get(env('API_URL') . '/api/series', [
            'sede_id' => $sede_id,
            'tipos'   => '1,2,3'
        ]);

        if ($response->successful()) {
            $this->series = collect($response->json())->keyBy('id')->toArray();
        } else {
            $this->series = [];
            session()->flash('error', 'No se pudo obtener las series desde la API externa');
        }

        $this->impresoras = ['POS-80C-1', 'POS-80C-2', 'EPSON-TM-U220-Receipt'];
    }

    public function calcular_digitos($factor): int
    {
        // Asegurar que sea número y al menos 1
        $f = max(1, (int) $factor);

        // Ejemplo: factor=1000 -> maxUnits=999
        $maxUnits = max(0, $f - 1);

        // Contar longitud de los dígitos (mínimo 2)
        $digits = max(2, strlen((string) abs((int) floor($maxUnits))));

        return $digits;
    }

    public function imprimir($id)
    {
        // Verificar que el ID exista
        if (!isset($this->series[$id])) {
            $this->addError("series.$id", 'No se encontró la serie seleccionada.');
            return;
        }

        $serie = $this->series[$id];

        // Validar datos requeridos
        if (empty($serie['correlativo_desde']) || empty($serie['correlativo_hasta']) || empty($serie['impresora'])) {
            $this->addError("series.$id", 'Todos los campos deben estar completos.');
            return;
        }

        if ($serie['correlativo_desde'] > $serie['correlativo_hasta']) {
            $this->addError("series.$id.correlativo_hasta", 'El correlativo hasta debe ser mayor o igual que el correlativo desde.');
            return;
        }

        $serie = (object) $serie;

        try {
            $nombre_impresora_compartida = "POS-80C-1";
            $correlativo_desde = (int)$serie->correlativo_desde;
            $correlativo_hasta = (int)$serie->correlativo_hasta;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('API_TOKEN'),
                'Accept' => 'application/json',
            ])
            ->timeout(60) // aumenta el límite
            ->connectTimeout(20) // evita que quede colgado
            ->withOptions([
                'verify' => false, // SOLO en local
            ])->get(env('API_URL') . '/api/comprobantes', [
                'sede_id' => $serie->f_sede_id,
                'serie' => $serie->serie,
                'desde' => $correlativo_desde,
                'hasta' => $correlativo_hasta,
            ]);

            if ($response->successful()) {
                $data = json_decode(json_encode($response->json()), false); // false = objetos stdClass
                $comprobantes = collect($data)->map(function ($item) {
                    // Convertir también los niveles anidados (detalle, cliente, vendedor, etc.)
                    if (isset($item->detalle) && is_array($item->detalle)) {
                        $item->detalle = collect($item->detalle)->map(function ($d) {
                            return (object) $d;
                        });
                    }
                    if (isset($item->cliente) && is_array($item->cliente)) {
                        $item->cliente = (object) $item->cliente;
                    }
                    if (isset($item->cliente->padron) && is_array($item->cliente->padron)) {
                        $item->cliente->padron = (object) $item->cliente->padron;
                    }
                    if (isset($item->vendedor) && is_array($item->vendedor)) {
                        $item->vendedor = (object) $item->vendedor;
                    }
                    if (isset($item->conductor) && is_array($item->conductor)) {
                        $item->conductor = (object) $item->conductor;
                    }
                    if (isset($item->tipo_doc) && is_array($item->tipo_doc)) {
                        $item->tipo_doc = (object) $item->tipo_doc;
                    }
                    return (object) $item;
                });
            } else {
                $comprobantes = collect();
                session()->flash('error', 'No se pudieron obtener los comprobantes desde la API externa');
                return;
            }

            // $comprobantes = FComprobanteSunat::with(['vendedor', 'tipo_doc', 'cliente.padron' => function ($query) {
            //     $query->withTrashed();
            // }, 'conductor', 'detalle.producto'])->where('sede_id', $serie->f_sede_id)->where('serie', $serie->serie)->whereBetween('correlativo', [$correlativo_desde, $correlativo_hasta])->get();
            //dd($comprobantes);

            $font = Printer::FONT_A;
            if ($serie->impresora == 'EPSON-TM-U220-Receipt') {
                $font = Printer::FONT_B;
            }
            $connector = new WindowsPrintConnector($serie->impresora);
            $printer = new Printer($connector);
            foreach ($comprobantes as $comprobante) {

                $formatter = new NumeroALetras();
                //dd($comprobante->detalle);
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setTextSize(1, 1);
                // $printer->setLineSpacing(65);
                $printer->setFont($font);
                if ($comprobante->tipoDoc === "00") {
                    $printer->feed();
                } else {
                    $printer->text(strtoupper($comprobante->companyRazonSocial));
                    $printer->feed();
                    $printer->text("RUC: " . $comprobante->companyRuc);
                    $printer->feed();
                    $printer->text(strtoupper("PUNTO PARTIDA: " . $comprobante->companyAddressDireccion));
                }
                $printer->feed();
                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->feed();
                $printer->text("FECHA : " . (Carbon::parse($comprobante->fechaEmision)->format('d-m-Y')));
                $printer->feed();
                $printer->text(strtoupper($comprobante->tipoDoc_name . " " . $comprobante->serie . "-" . str_pad($comprobante->correlativo, 8, "0", STR_PAD_LEFT)));
                $printer->feed();
                $printer->text("--------------------------------");
                $printer->feed();
                $printer->text(strtoupper("COD.CLTE: " . str_pad($comprobante->cliente_id, 8, "0", STR_PAD_LEFT) . " " . $comprobante->tipo_doc->tipo_documento . ": " . $comprobante->clientNumDoc));
                $printer->feed();
                $printer->text("NOMBRE Y APELLIDOS:");
                $printer->feed();
                $printer->text(strtoupper($comprobante->clientRazonSocial));
                $printer->feed();
                $printer->text("DOMICILIO DE ENTREGA:");
                $printer->feed();
                $printer->text(strtoupper($comprobante->clientDireccion));
                $printer->feed();
                $printer->text(strtoupper("VENDEDOR: " . str_pad($comprobante->vendedor_id, 3, "0", STR_PAD_LEFT) . " " . $comprobante->vendedor->name));
                $printer->feed();
                logger("imprimir-info", [$comprobante->cliente->id]);
                $printer->text("RUTA: " . str_pad($comprobante->ruta_id, 4, "0", STR_PAD_LEFT) . "  SEC.: " . str_pad($comprobante->cliente->padron->nro_secuencia, 5, "0", STR_PAD_LEFT));
                $printer->feed();
                $printer->text("FORMA DE PAGO : CONTADO");
                $printer->feed();
                $printer->text("ARTICULO    CANTIDAD   PRECIO   IMPORTE");
                $printer->feed();
                $printer->text("---------------------------------------");
                $printer->feed();
                $printer->feed();
                foreach ($comprobante->detalle as $detalle) {
                    $monto_valor = $detalle->mtoValorVenta;
                    if ($detalle->tipAfeIgv == 21) {
                        $monto_valor = $detalle->mtoValorUnitario;
                    }
                    $printer->text(strtoupper(str_pad($detalle->codProducto, 5, "0", STR_PAD_LEFT) . " " . substr($detalle->descripcion, 0, 34)));
                    $printer->feed();
                    $printer->text("CAJX" . str_pad($detalle->ref_producto_cantidad_cajon, 2, "0", STR_PAD_LEFT) . "    " . str_pad(number_format($detalle->ref_producto_cant_vendida, $this->calcular_digitos($detalle->ref_producto_cantidad_cajon), '.', ''), 6, " ", STR_PAD_LEFT) . " " . str_pad(number_format($detalle->ref_producto_precio_cajon, 2), 10, " ", STR_PAD_LEFT) . " " . str_pad(number_format(($monto_valor + $detalle->totalImpuestos), 2), 12, " ", STR_PAD_LEFT));
                    $printer->feed();
                }
                $printer->text("**SON: " . strtoupper($formatter->toInvoice($comprobante->mtoImpVenta, 2, 'SOLES')));
                $printer->feed();
                $printer->text("---------------------------------------");
                $printer->feed();
                $printer->text("NUMERO DE ITEMS = " . $comprobante->detalle->count());
                $printer->feed();
                $printer->text(str_pad("IMPORTE BRUTO: ", 15, " ", STR_PAD_RIGHT) . str_pad(number_format($comprobante->subTotal, 2), 12, " ", STR_PAD_LEFT));
                $printer->feed();
                $printer->text(str_pad("DESCUENTOS : ", 15, " ", STR_PAD_RIGHT) . str_pad("0.00", 12, " ", STR_PAD_LEFT));
                $printer->feed();
                if ($comprobante->tipoDoc === "01") {
                    $printer->text(str_pad("IMPORTE NETO : ", 15, " ", STR_PAD_RIGHT) . str_pad(number_format($comprobante->valorVenta, 2), 12, " ", STR_PAD_LEFT));
                    $printer->feed();
                    $printer->text(str_pad("IMPORTE IGV : ", 15, " ", STR_PAD_RIGHT) . str_pad(number_format($comprobante->totalImpuestos, 2), 12, " ", STR_PAD_LEFT));
                    $printer->feed();
                }
                $printer->text(str_pad("IMPORTE TOTAL: ", 15, " ", STR_PAD_RIGHT) . str_pad(number_format($comprobante->mtoImpVenta, 2), 12, " ", STR_PAD_LEFT));
                $printer->feed();
                $printer->feed();
                $printer->text(strtoupper("CHOFER: " . str_pad($comprobante->conductor_id, 3, "0", STR_PAD_LEFT) . " " . $comprobante->conductor->name));
                $printer->feed();
                $printer->feed();
                $printer->text("REPRESENTACION IMPRESA DE BOLETA ELECTRONICA");
                $printer->feed();
                $printer->text("AUTORIZADO MEDIANTE RESOLUCION");
                $printer->feed();
                $printer->text("NRO.:340-2017/SUNAT");
                $printer->feed();
                $printer->text("VB");
                $printer->feed();

                $printer->feed();
                $printer->feed();
                $printer->cut();
            }

            /*
            Por medio de la impresora mandamos un pulso.
            Esto es útil cuando la tenemos conectada
            por ejemplo a un cajón
            */
            $printer->pulse();

            /*
            Para imprimir realmente, tenemos que "cerrar"
            la conexión con la impresora. Recuerda incluir esto al final de todos los archivos
            */
            $printer->close();
            session()->forget('error');
        } catch (\Exception $e) {
            // Manejo de errores
            if (isset($printer)) {
                $printer->close();
            }
            session()->flash('error', 'Error al imprimir: ' . $e->getMessage());
        }
    }
}
