<?php

namespace App\Domains\Payments\Services;

use App\Domains\Payments\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class LibelulaService
{
    private ?string $appkey;

    private string $apiUrl;

    public function __construct()
    {
        $this->appkey = config('libelula.appkey');
        $this->apiUrl = rtrim(config('libelula.api_url', 'https://api.libelula.bo'), '/');

        if (empty($this->appkey)) {
            throw new RuntimeException('La API Key de Libélula (LIBELULA_APPKEY) no está configurada en el archivo .env.');
        }
    }

    /**
     * Registra una deuda en Libélula y persiste el id_transaccion y url_pasarela_pagos
     * en el Invoice. Lanza RuntimeException si la API responde con error.
     */
    public function registrarDeuda(Invoice $invoice): array
    {
        $invoice->loadMissing(['order.patient', 'order.exams']);

        $patient = $invoice->order->patient;
        $order = $invoice->order;

        $lineas = $order->exams->map(fn ($exam) => [
            'concepto' => $exam->name,
            'cantidad' => 1,
            'costo_unitario' => (float) $exam->price,
        ])->values()->all();

        $payload = [
            'appkey' => $this->appkey,
            'email_cliente' => $patient->email,
            'nombre_cliente' => $patient->first_name,
            'apellido_cliente' => $patient->last_name,
            'identificador_deuda' => $invoice->invoice_number,
            'descripcion' => "Pago de orden #{$order->id} - Clínica Norte",
            'callback_url' => route('payments.libelula.callback'),
            'url_retorno' => route('payments.patient.portal'),
            'moneda' => 'BOB',
            'emite_factura' => false,
            'lineas_detalle_deuda' => $lineas,
        ];

        try {
            $response = Http::timeout(20)
                ->post("{$this->apiUrl}/rest/deuda/registrar", $payload);
        } catch (\Throwable $e) {
            Log::error('LibelulaService: timeout/conexión fallida al registrar deuda', [
                'invoice_id' => $invoice->id,
                'message' => $e->getMessage(),
            ]);
            throw new RuntimeException('No se pudo conectar con Libélula. Intente de nuevo o use efectivo.');
        }

        if ($response->failed()) {
            Log::error('LibelulaService: respuesta de error al registrar deuda', [
                'invoice_id' => $invoice->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Libélula rechazó la solicitud (HTTP '.$response->status().'). Intente de nuevo.');
        }

        $data = $response->json();

        $transactionId = $data['id_transaccion'] ?? $data['idTransaccion'] ?? null;
        $paymentUrl = $data['url_pasarela_pagos'] ?? $data['urlPasarelaPagos'] ?? null;

        if (! $transactionId || ! $paymentUrl) {
            Log::error('LibelulaService: respuesta sin id_transaccion o url_pasarela_pagos', [
                'invoice_id' => $invoice->id,
                'data' => $data,
            ]);
            throw new RuntimeException('La respuesta de Libélula no incluye los datos esperados. Contacte soporte.');
        }

        // Si la deuda ya existía en Libélula (existente=1) y no devolvió qr_simple_url,
        // forzamos un nuevo registro con un identificador único para obtener un QR fresco.
        if (($data['existente'] ?? 0) === 1 && empty($data['qr_simple_url'])) {
            Log::info('LibelulaService: deuda existente sin QR, forzando nuevo registro', [
                'invoice_id' => $invoice->id,
                'identificador_previo' => $payload['identificador_deuda'],
            ]);

            $payload['identificador_deuda'] = $invoice->invoice_number.'-'.time();

            try {
                $responseNew = Http::timeout(20)
                    ->post("{$this->apiUrl}/rest/deuda/registrar", $payload);

                if (! $responseNew->failed()) {
                    $dataNew = $responseNew->json();

                    if (! empty($dataNew['qr_simple_url'])) {
                        $data = $dataNew;
                        $transactionId = $dataNew['id_transaccion'] ?? $transactionId;
                        $paymentUrl = $dataNew['url_pasarela_pagos'] ?? $paymentUrl;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('LibelulaService: falló forzar nuevo registro para QR', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $qrSimpleUrl = $data['qr_simple_url'] ?? null;
        $codigoRecaudacion = $data['codigo_recaudacion'] ?? null;

        $invoice->update([
            'libelula_transaction_id' => $transactionId,
            'libelula_payment_url' => $paymentUrl,
            'libelula_status' => 'pendiente',
            'libelula_qr_simple_url' => $qrSimpleUrl,
            'libelula_codigo_recaudacion' => $codigoRecaudacion,
        ]);

        return $data;
    }

    /**
     * Consulta el estado de una deuda en Libélula por su código de recaudación.
     * Devuelve el array crudo de la API.
     */
    public function consultarPago(string $codigoRecaudacion): array
    {
        $payload = [
            'appkey' => $this->appkey,
            'codigo_recaudacion' => $codigoRecaudacion,
        ];

        try {
            $response = Http::timeout(20)
                ->post("{$this->apiUrl}/rest/deuda/consultar_deudas/por_identificador", $payload);
        } catch (\Throwable $e) {
            Log::error('LibelulaService: timeout/conexión fallida al consultar pago', [
                'codigo_recaudacion' => $codigoRecaudacion,
                'message' => $e->getMessage(),
            ]);
            throw new RuntimeException('No se pudo conectar con Libélula al verificar el pago.');
        }

        if ($response->failed()) {
            Log::error('LibelulaService: error HTTP al consultar pago', [
                'codigo_recaudacion' => $codigoRecaudacion,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Libélula devolvió error al consultar el pago (HTTP '.$response->status().').');
        }

        return $response->json() ?? [];
    }

    /**
     * Determina si la respuesta de consultarPago indica pago confirmado.
     * Libélula devuelve: { error: 0, datos: { pagado: true/false, estado: "PAGADO"|... } }
     */
    public function esPagado(array $consultaResponse): bool
    {
        // Estructura real: datos.pagado o datos.estado
        $datos = $consultaResponse['datos'] ?? [];

        if (is_array($datos)) {
            if (($datos['pagado'] ?? false) === true) {
                return true;
            }
            $estado = strtoupper((string) ($datos['estado'] ?? ''));
            if ($estado === 'PAGADO') {
                return true;
            }
        }

        // Variantes planas (respaldo)
        return ($consultaResponse['pagado'] ?? false) === true
            || strtoupper((string) ($consultaResponse['estado'] ?? '')) === 'PAGADO';
    }
}
