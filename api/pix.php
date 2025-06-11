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
    <div class="imagem" style="display:flex; justify-content:center">
        <img src="./bandeira.png" alt="" srcset="" style="width:18pc;">
    </div>
    
            <form method="POST" action="">
                <label for="cpf">CPF:</label>
                <input type="text" id="cpf" name="cpf" required placeholder="Seu CPF"><br><br>

           <label for="descricao">Nome Completo & Telefone:</label>
<textarea id="descricao" name="descricao" required placeholder="Ex: João da Silva 11999998888" rows="2" cols="40"></textarea><br><br>

                <input type="submit" value="Realizar Pagamento">
            </form>
        </div>';
}
?>

<style>
<style>
/* Reset básico */
*,
*::before,
*::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: 'Comic Sans MS', cursive, sans-serif;
  background: linear-gradient(to bottom, #fff0c1, #ffdb99);
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 1rem;
  color: #4b2800;
  text-align: center;
  position: relative;
  overflow-x: hidden;
}

/* Bandeirinhas no topo */
body::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 80px;
  background: url('https://cdn.pixabay.com/photo/2017/06/02/21/53/festa-junina-2362727_960_720.png') repeat-x;
  background-size: contain;
  z-index: 1;
}

/* Container do formulário */
.form-container {
  position: relative;
  background: #fff8e1;
  border: 4px dashed #ff9800;
  border-radius: 20px;
  padding: 2rem 1.5rem;
  max-width: 420px;
  width: 100%;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  z-index: 2;
}

h2 {
  font-size: 2rem;
  color: #bf360c;
  margin-bottom: 1rem;
  text-shadow: 1px 1px 0 #fff;
}

form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  text-align: left;
}

label {
  font-weight: bold;
  font-size: 1.1rem;
  color: #5d4037;
}

input[type="text"],
textarea {
  width: 100%;
  padding: 0.9rem;
  font-size: 1rem;
  border-radius: 12px;
  border: 2px solid #ffb74d;
  background: #fffaf0;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

input[type="text"]:focus,
textarea:focus {
  border-color: #ff5722;
  box-shadow: 0 0 10px rgba(255, 87, 34, 0.4);
  outline: none;
}

textarea {
  min-height: 80px;
  resize: vertical;
}

input[type="submit"] {
  background: linear-gradient(90deg, #ff9800, #f57c00);
  color: #fff;
  font-size: 1.2rem;
  padding: 0.9rem;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  font-weight: bold;
  box-shadow: 0 4px 12px rgba(255, 152, 0, 0.5);
  transition: background 0.3s ease, transform 0.2s ease;
}

input[type="submit"]:hover {
  background: linear-gradient(90deg, #e65100, #bf360c);
  transform: scale(1.02);
}

@media (max-width: 480px) {
  .form-container {
    padding: 1.5rem 1rem;
  }

  h2 {
    font-size: 1.6rem;
  }

  input[type="submit"] {
    font-size: 1rem;
  }
}

</style>
<script>
    
</script>