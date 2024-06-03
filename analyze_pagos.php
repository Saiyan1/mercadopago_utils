<?php
//Importar archivo de variables sensibles
require_once 'variables.php';

// URL de la API de MercadoPago para buscar pagos
$url = 'https://api.mercadopago.com/v1/payments/search?sort=date_created&criteria=desc';


// Inicializa cURL
$ch = curl_init();

// Configura las opciones de cURL
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken
]);

// Ejecuta la solicitud y obtiene la respuesta
$response = curl_exec($ch);

// Verifica si hubo un error
if (curl_errno($ch)) {
    echo 'Error en cURL: ' . curl_error($ch);
    exit;
}

// Cierra la sesión cURL
curl_close($ch);

// Decodifica la respuesta JSON
$data = json_decode($response, true);

// Verifica si la decodificación fue exitosa
if (json_last_error() !== JSON_ERROR_NONE) {
    echo 'Error al decodificar el JSON: ' . json_last_error_msg();
    exit;
}


// Inicia el contenido HTML
$html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles de Pagos</title>
    <style>
        body {
            font-family: Verdana, sans-serif;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        .approved {
            background-color: #c6efce;
        }
        .rejected {
            background-color: #ffc7ce;
        }
        .pending {
            background-color: #ffeb9c;
        }
    </style>
</head>
<body>
<h1>Detalles de Pagos</h1>
<table>
    <tr>
        <th>ESTADO</th>
        <th>Detalle del ESTADO</th>
        <th>Fecha de creación</th>
        <th>Detalles del pago</th>
        <th>Fecha de aprobación</th>
        <th>Email del usuario</th>
        <th>Detalles de la transacción</th>
        <th>External Reference</th>
        <th>Detalles de los cargos</th>
        <th>Forma de pago</th>
        <th>Total de la transacción</th>
    </tr>';

// Procesa los pagos
foreach ($data['results'] as $payment) {
    if ($payment['integrator_id'] === $integrator_id) {
        $status = $payment['status'] ?? 'N/A';
        $statusDetail = $payment['status_detail'] ?? 'N/A';
        $class = '';
        switch ($status) {
            case 'approved':
                $class = 'approved';
                break;
            case 'rejected':
                $class = 'rejected';
                break;
            case 'pending':
                $class = 'pending';
                break;
        }

        $html .= '<tr class="' . $class . '">';

        // Estado
        $html .= '<td>' . $status . '</td>';
        
        // Detalle del estado
        $html .= '<td>' . $statusDetail . '</td>';
        
        // Fecha de creación
        $html .= '<td>' . ($payment['date_created'] ?? 'N/A') . '</td>';

        // Detalles del pago
        $html .= '<td>';
        if (isset($payment['fee_details'])) {
            foreach ($payment['fee_details'] as $fee) {
                $html .= 'Tipo: ' . $fee['type'] . ', Monto: ' . $fee['amount'] . '<br>';
            }
        }
        $html .= '</td>';

        // Fecha de aprobación
        $html .= '<td>' . ($payment['date_approved'] ?? 'N/A') . '</td>';

        // Email del usuario
        $payerEmail = isset($payment['payer']['email']) ? $payment['payer']['email'] : 'N/A';
        $html .= '<td>' . $payerEmail . '</td>';

        // Detalles de la transacción
        $html .= '<td>';
        $html .= 'Total pagado: ' . ($payment['transaction_details']['total_paid_amount'] ?? 'N/A') . '<br>';
        $html .= 'Neto recibido: ' . ($payment['transaction_details']['net_received_amount'] ?? 'N/A');
        $html .= '</td>';

        // External Reference
        $html .= '<td>' . ($payment['external_reference'] ?? 'N/A') . '</td>';

        // Detalles de los cargos
        $html .= '<td>';
        if (isset($payment['charges_details'])) {
            foreach ($payment['charges_details'] as $charge) {
                $html .= 'Monto: ' . $charge['amounts']['original'] . '<br>';
                $html .= 'Desde: ' . $charge['accounts']['from'] . ' Hasta: ' . $charge['accounts']['to'] . '<br>';
            }
        }
        $html .= '</td>';

        // Forma de pago
        $html .= '<td>' . ($payment['payment_type_id'] ?? 'N/A') . '</td>';

        // Total de la transacción
        $html .= '<td>' . ($payment['transaction_amount'] ?? 'N/A') . '</td>';

        $html .= '</tr>';
    }
}

$html .= '</table>
</body>
</html>';

// Guarda el contenido HTML en un archivo
file_put_contents('detalles_pagos.html', $html);

echo 'El archivo detalles_pagos.html ha sido generado correctamente.';