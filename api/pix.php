<?php
// Carregar configuração com o access token
$config = require_once '../config.php';
$accesstoken = $config['accesstoken'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Valores fixos para nome e email
    $nome = "FESTFY";
    $email = "festfy@example.com";

    // Dados que o usuário envia no formulário
    $cpf = $_POST['cpf'];
    $descricao = $_POST['descricao'];

    // Gerar um UUID único para o cabeçalho X-Idempotency-Key
    $idempotency_key = uniqid('idempotency_', true);

    // Dados do pagamento no formato JSON
    $data = [
        "description" => $descricao,
        "external_reference" => "MP0001",
        "payer" => [
            "email" => $email,
            "identification" => [
                "type" => "CPF",
                "number" => $cpf
            ],
            "first_name" => $nome
        ],
        "payment_method_id" => "pix",
        "transaction_amount" => 0.01
    ];

    // Iniciar o cURL
    $curl = curl_init();

    // Configurar cURL
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.mercadopago.com/v1/payments',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accesstoken,
            'X-Idempotency-Key: ' . $idempotency_key
        ],
    ]);

    // Executar a requisição e capturar a resposta
    $response = curl_exec($curl);

    // Verificar erros no cURL
    if (curl_errno($curl)) {
        echo "Erro no cURL: " . curl_error($curl);
        curl_close($curl);
        exit;
    }

    // Fechar o cURL
    curl_close($curl);

    // Decodificar a resposta JSON
    $obj = json_decode($response);

    // Verificar se o pagamento foi gerado com sucesso
    if (isset($obj->id) && isset($obj->point_of_interaction)) {
        // Capturar o link externo de pagamento
        $ticket_url = $obj->point_of_interaction->transaction_data->ticket_url ?? null;

        // Redirecionar para a página do Pix diretamente
        if ($ticket_url) {
            header("Location: $ticket_url");
            exit;
        } else {
            echo "Link externo não disponível.<br/>";
        }
    } else {
        echo "Erro: Não foi possível gerar o pagamento PIX. Verifique a resposta da API.";
    }
} else {
    // Exibir o formulário apenas com CPF e descrição
    echo '<div class="form-container">
            <h2>Pagamento via PIX</h2>
            <form method="POST" action="">
                <label for="cpf">CPF:</label>
                <input type="text" id="cpf" name="cpf" required placeholder="Seu CPF"><br><br>

           <label for="descricao">Nome completo + telefone:</label>
<textarea id="descricao" name="descricao" required placeholder="Ex: João da Silva 11999998888" rows="2" cols="40"></textarea><br><br>

                <input type="submit" value="Realizar Pagamento">
            </form>
        </div>';
}
?>

<style>
body {
    font-family: 'Arial', sans-serif;
    background-color: #f0f0f0;
    margin: 0;
    padding: 0;
    text-align: center;
    color: #333;
}

.form-container {
    width: 100%;
    max-width: 600px;
    margin: 50px auto;
    padding: 20px;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

h2 {
    color: #444;
    font-size: 1.8em;
    margin-bottom: 30px;
}

form {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
}

form label {
    font-size: 1.2em;
    color: #555;
    margin-bottom: 5px;
}

form input[type="text"],
form input[type="submit"] {
    width: 100%;
    max-width: 450px;
    padding: 12px;
    font-size: 1em;
    border-radius: 8px;
    border: 1px solid #ccc;
    box-sizing: border-box;
}

form input[type="text"]:focus {
    border-color: #00aaff;
    outline: none;
}

form input[type="submit"] {
    background-color: #007bff;
    color: white;
    font-weight: bold;
    border: none;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

form input[type="submit"]:hover {
    background-color: #0056b3;
}
</style>
<script>
    
</script>