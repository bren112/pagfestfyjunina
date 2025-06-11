<?php
$config = require_once '../config.php';
$accesstoken = $config['accesstoken'];

$limit = 500; // máximo permitido pela API
$offset = 0;
$pagamentos_aprovados = [];

$data_minima = '2025-06-11 19:10:39';

do {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.mercadopago.com/v1/payments/search?limit=$limit&offset=$offset",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accesstoken
        ],
    ]);

    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        echo "Erro no cURL: " . curl_error($curl);
        curl_close($curl);
        exit;
    }
    curl_close($curl);

    $obj = json_decode($response);
    if (!isset($obj->results) || empty($obj->results)) {
        break;
    }

    foreach ($obj->results as $payment) {
        if (
            $payment->status === 'approved' &&
            strtotime($payment->date_approved) >= strtotime($data_minima) &&
            $payment->transaction_amount >= 0.01 &&
            $payment->transaction_amount <= 405.00
        ) {
            $pagamentos_aprovados[] = $payment;
        }
        
    }

    $offset += $limit;
} while (count($obj->results) === $limit);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Pagamentos Aprovados</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap');

        body {
            font-family: 'Orbitron', sans-serif;
            background-color: #0f0f0f;
            color: #f1f1f1;
            margin: 30px auto;
            max-width: 960px;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 20px #00ff88;
        }
        h2 {
            color: #00ff88;
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 0 0 8px #00ff88;
            font-size: 2rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #161616;
            border: 2px solid #00ff88;
            box-shadow: 0 0 15px #00ff88;
            border-radius: 10px;
            overflow: hidden;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #00ff88;
        }
        th {
            background-color: #121212;
            color: #00ff88;
            font-weight: bold;
            text-shadow: 0 0 5px #00ff88;
        }
        td {
            color: #a0f8b8;
        }
        tr:hover {
            background-color: #1f1f1f;
        }
        .highlight {
        background-color: #003322 !important;
        box-shadow: 0 0 10px #00ff88 inset;
    }
        @media (max-width: 600px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }
            tr {
                margin-bottom: 15px;
                border: 1px solid #00ff88;
                border-radius: 10px;
                padding: 10px;
            }
            th {
                display: none;
            }
            td {
                position: relative;
                padding-left: 50%;
            }
            td::before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                font-weight: bold;
                color: #00ff88;
            }
        }
    </style>
</head>
<body>
    <h2>Pagamentos Aprovados (R$ 0,01 e R$ 5,00)</h2>
    <input type="text" id="searchInput" placeholder="Buscar por nome ou telefone..." style="
    width: 100%;
    padding: 10px;
    margin-bottom: 20px;
    background-color: #1a1a1a;
    border: 2px solid #00ff88;
    border-radius: 8px;
    color: #00ff88;
    font-size: 1rem;
    text-shadow: 0 0 3px #00ff88;
">

<!-- JavaScript para busca e destaque -->
<script>
document.getElementById('searchInput').addEventListener('input', function() {
    const filtro = this.value.toLowerCase();
    const linhas = document.querySelectorAll('tbody tr');

    linhas.forEach(tr => {
        tr.classList.remove('highlight');
        const nome = tr.children[4]?.textContent.toLowerCase();
        const telefone = tr.children[5]?.textContent.toLowerCase();

        if (filtro && (nome.includes(filtro) || telefone.includes(filtro))) {
            tr.classList.add('highlight');
        }
    });
});
</script>
    <?php if (!empty($pagamentos_aprovados)): ?>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th>Meio de Pagamento</th>
                    <th>Nome</th>
                    <th>Telefone</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagamentos_aprovados as $payment): ?>
                    <tr>
                        <td data-label="Data"><?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($payment->date_approved))) ?></td>
                        <td data-label="Valor">R$ <?= number_format($payment->transaction_amount, 2, ',', '.') ?></td>
                        <td data-label="Status"><?= htmlspecialchars($payment->status) ?></td>
                        <td data-label="Meio de Pagamento"><?= htmlspecialchars($payment->payment_method_id) ?></td>
                        <?php
                            if (isset($payment->description)) {
                                $desc = $payment->description;
                                if (preg_match('/^(.*?)(\d+)$/', $desc, $matches)) {
                                    $desc_texto = trim($matches[1]);
                                    $desc_numero = $matches[2];
                                } else {
                                    $desc_texto = $desc;
                                    $desc_numero = '';
                                }
                            } else {
                                $desc_texto = 'Nome não disponível';
                                $desc_numero = '';
                            }
                        ?>
                        <td data-label="Nome"><?= htmlspecialchars($desc_texto) ?></td>
                        <td data-label="Telefone"><?= htmlspecialchars($desc_numero) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align:center; color:#f66;">Nenhum pagamento aprovado encontrado a partir de <?= $data_minima ?> com valores exatos de R$ 0,01 ou R$ 5,00.</p>
    <?php endif; ?>
</body>
</html>
