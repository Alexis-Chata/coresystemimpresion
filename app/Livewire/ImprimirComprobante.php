<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Mike42\Escpos\Printer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Luecano\NumeroALetras\NumeroALetras;
use Illuminate\Http\Client\ConnectionException;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

class ImprimirComprobante extends Component
{
    public function render()
    {
        return view('livewire.imprimir-comprobante');
    }

    public $series = [];
    public $impresoras = [];
    public $readyToLoad = false; // Bandera para carga asíncrona

    public function mount()
    {
        $this->impresoras = ['POS-80C-1', 'POS-80C-2', 'EPSON-TM-U220-Receipt'];
    }

    /**
     * Este método se ejecuta automáticamente desde la vista cuando está lista.
     * Evita que la pantalla se congele al entrar a la página.
     */
    public function loadSeries()
    {
        $sede_id = auth_user()->f_sede_id ?? null;

        // Cacheamos las series por 5 minutos para evitar golpear la API en cada F5
        $this->series = Cache::remember("series_sede_{$sede_id}", now()->addMinutes(5), function () use ($sede_id) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.core_api.token'),
                    'Accept' => 'application/json',
                ])
                    ->connectTimeout(5) // Tiempo corto para no hacer esperar al usuario
                    ->timeout(10)
                    ->withOptions(['verify' => false]) // SOLO en local
                    ->get(config('services.core_api.url') . '/api/series', [
                        'sede_id' => $sede_id,
                        'tipos'   => '1,2,3'
                    ]);

                if ($response->successful()) {
                    return collect($response->json())->keyBy('id')->toArray();
                }
            } catch (ConnectionException $e) {
                Log::error("Timeout obteniendo series: " . $e->getMessage());
            }

            return [];
        });

        if (empty($this->series)) {
            session()->flash('error', 'No se pudieron cargar las series. La API externa no responde.');
        }

        $this->readyToLoad = true;
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
        // Limpiar errores previos acumulados en el estado de Livewire
        $this->resetValidation();

        // Verificar que el ID exista
        if (!isset($this->series[$id])) {
            $this->addError("series.$id", 'No se encontró la serie seleccionada.');
            return;
        }

        $serie = (object) $this->series[$id];

        if (empty($serie->correlativo_desde) || empty($serie->correlativo_hasta) || empty($serie->impresora)) {
            $this->addError("series.$id", 'Todos los campos deben estar completos.');
            return;
        }

        if ($serie->correlativo_desde > $serie->correlativo_hasta) {
            $this->addError("series.$id.correlativo_hasta", 'El correlativo "hasta" debe ser mayor o igual.');
            return;
        }

        $printer = null;

        try {
            // 1. Petición HTTP optimizada (Directamente convertida a Objeto mediante json(null))
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.core_api.token'),
                'Accept' => 'application/json',
            ])
                ->connectTimeout(10)
                ->timeout(45) // Límite prudente para descarga de lotes
                ->withOptions(['verify' => false]) // SOLO en local
                ->get(config('services.core_api.url') . '/api/comprobantes', [
                    'sede_id' => $serie->f_sede_id,
                    'serie'   => $serie->serie,
                    'desde'   => (int) $serie->correlativo_desde,
                    'hasta'   => (int) $serie->correlativo_hasta,
                ]);

            if (!$response->successful()) {
                throw new \Exception('La API externa de comprobantes no respondió correctamente.');
            }

            // Transformamos la respuesta directamente a objetos estándar de PHP
            $comprobantes = $response->object();

            if (empty($comprobantes)) {
                session()->flash('error', 'No se encontraron comprobantes en el rango seleccionado.');
                return;
            }

            // 2. Configuración de la Impresora Térmica
            $font = ($serie->impresora === 'EPSON-TM-U220-Receipt') ? Printer::FONT_B : Printer::FONT_A;
            $connector = new WindowsPrintConnector($serie->impresora);
            $printer = new Printer($connector);
            $formatter = new NumeroALetras();

            // 3. Bucle de Impresión
            foreach ($comprobantes as $comprobante) {
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setTextSize(1, 1);
                // $printer->setLineSpacing(65);
                $printer->setFont($font);

                if (($comprobante->tipoDoc ?? '') === "00") {
                    $printer->feed();
                } else {
                    $printer->text(strtoupper($comprobante->companyRazonSocial ?? ''));
                    $printer->feed();
                    $printer->text("RUC: " . ($comprobante->companyRuc ?? ''));
                    $printer->feed();
                    $printer->text(strtoupper("PUNTO PARTIDA: " . ($comprobante->companyAddressDireccion ?? '')));
                }
                $printer->feed();
                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->feed();
                $printer->text("FECHA : " . (Carbon::parse($comprobante->fechaEmision)->format('d-m-Y')));
                $printer->feed();
                $printer->text(strtoupper(($comprobante->tipoDoc_name ?? '') . " " . ($comprobante->serie ?? '') . "-" . str_pad($comprobante->correlativo ?? 0, 8, "0", STR_PAD_LEFT)));
                $printer->feed();
                $printer->text("--------------------------------");
                $printer->feed();
                $printer->text(strtoupper("COD.CLTE: " . str_pad($comprobante->cliente_id ?? 0, 8, "0", STR_PAD_LEFT) . " " . ($comprobante->tipo_doc->tipo_documento ?? 'DOC') . ": " . ($comprobante->clientNumDoc ?? '')));
                $printer->feed();
                $printer->text("NOMBRE Y APELLIDOS:");
                $printer->feed();
                $printer->text(strtoupper($comprobante->clientRazonSocial ?? ''));
                $printer->feed();
                $printer->text("DOMICILIO DE ENTREGA:");
                $printer->feed();
                $printer->text(strtoupper($comprobante->clientDireccion ?? ''));
                $printer->feed();
                $printer->text(strtoupper("VENDEDOR: " . str_pad($comprobante->vendedor_id ?? 0, 3, "0", STR_PAD_LEFT) . " " . ($comprobante->vendedor->name ?? '')));
                $printer->feed();

                $nro_secuencia = $comprobante->cliente->padron->nro_secuencia ?? 0;
                $printer->text("RUTA: " . str_pad($comprobante->ruta_id ?? 0, 4, "0", STR_PAD_LEFT) . "  SEC.: " . str_pad($nro_secuencia, 5, "0", STR_PAD_LEFT));
                $printer->feed();
                $printer->text("FORMA DE PAGO : CONTADO");
                $printer->feed();
                $printer->text("ARTICULO    CANTIDAD   PRECIO   IMPORTE");
                $printer->feed();
                $printer->text("---------------------------------------");
                $printer->feed();
                $printer->feed();
                $detalles = $comprobante->detalle ?? [];
                foreach ($detalles as $detalle) {
                    $monto_valor = $detalle->mtoValorVenta ?? 0;
                    if (($detalle->tipAfeIgv ?? 0) == 21) {
                        $monto_valor = $detalle->mtoValorUnitario ?? 0;
                    }
                    $printer->text(strtoupper(str_pad($detalle->codProducto ?? 0, 5, "0", STR_PAD_LEFT) . " " . substr($detalle->descripcion ?? '', 0, 34)));
                    $printer->feed();

                    $cant_cajon = $detalle->ref_producto_cantidad_cajon ?? 1;
                    $cant_vendida = $detalle->ref_producto_cant_vendida ?? 0;
                    $precio_cajon = $detalle->ref_producto_precio_cajon ?? 0;
                    $total_impuesto = $detalle->totalImpuestos ?? 0;

                    $printer->text("CAJX" . str_pad($cant_cajon, 2, "0", STR_PAD_LEFT) . "    " . str_pad(number_format($cant_vendida, $this->calcular_digitos($cant_cajon), '.', ''), 6, " ", STR_PAD_LEFT) . " " . str_pad(number_format($precio_cajon, 2), 10, " ", STR_PAD_LEFT) . " " . str_pad(number_format(($monto_valor + $total_impuesto), 2), 12, " ", STR_PAD_LEFT));
                    $printer->feed();
                }

                $printer->text("**SON: " . strtoupper($formatter->toInvoice($comprobante->mtoImpVenta ?? 0, 2, 'SOLES')));
                $printer->feed();
                $printer->text("---------------------------------------");
                $printer->feed();
                $printer->text("NUMERO DE ITEMS = " . count($detalles));
                $printer->feed();
                $printer->text(str_pad("IMPORTE BRUTO: ", 15, " ", STR_PAD_RIGHT) . str_pad(number_format($comprobante->subTotal ?? 0, 2), 12, " ", STR_PAD_LEFT));
                $printer->feed();
                $printer->text(str_pad("DESCUENTOS : ", 15, " ", STR_PAD_RIGHT) . str_pad("0.00", 12, " ", STR_PAD_LEFT));
                $printer->feed();

                if (($comprobante->tipoDoc ?? '') === "01") {
                    $printer->text(str_pad("IMPORTE NETO : ", 15, " ", STR_PAD_RIGHT) . str_pad(number_format($comprobante->valorVenta ?? 0, 2), 12, " ", STR_PAD_LEFT));
                    $printer->feed();
                    $printer->text(str_pad("IMPORTE IGV : ", 15, " ", STR_PAD_RIGHT) . str_pad(number_format($comprobante->totalImpuestos ?? 0, 2), 12, " ", STR_PAD_LEFT));
                    $printer->feed();
                }

                $printer->text(str_pad("IMPORTE TOTAL: ", 15, " ", STR_PAD_RIGHT) . str_pad(number_format($comprobante->mtoImpVenta ?? 0, 2), 12, " ", STR_PAD_LEFT));
                $printer->feed();
                $printer->feed();
                $printer->text(strtoupper("CHOFER: " . str_pad($comprobante->conductor_id ?? 0, 3, "0", STR_PAD_LEFT) . " " . ($comprobante->conductor->name ?? '')));
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
            session()->flash('message', '¡Impresión completada con éxito!');
        } catch (\Exception $e) {
            // Manejo de errores
            if ($printer) {
                $printer->close();
            }
            Log::error("Error en el proceso de impresión: " . $e->getMessage());
            session()->flash('error', 'Error al imprimir: ' . $e->getMessage());
        }
    }
}
