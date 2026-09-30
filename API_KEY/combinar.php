<?php

header("Content-Type: application/json; charset=utf-8");

$apiKey = getenv("GEMINI_API_KEY");

// Recebe os elementos
$elemento1 = $_POST["elemento1"] ?? null;
$elemento2 = $_POST["elemento2"] ?? null;

if (!$elemento1 || !$elemento2) {
    echo json_encode([
        "erro" => "Elementos não fornecidos"
    ]);
    exit;
}

$prompt = "
Você é o sistema de combinações de um jogo inspirado em Infinite Craft.

Sua função é analisar dois elementos e determinar se eles podem gerar um
novo elemento de forma suficientemente plausível.

ELEMENTO 1:
$elemento1

ELEMENTO 2:
$elemento2


REGRAS PARA COMBINAÇÕES:

1. Priorize relações científicas reais.
   Procure primeiro por transformações físicas, químicas ou biológicas
   conhecidas.

2. Quando existir uma transformação científica específica, prefira ela
   em vez de um resultado genérico.

   Exemplo:
   Lava + Água = Obsidiana
   é preferível a:
   Lava + Água = Pedra

3. Se não existir uma relação científica direta, procure uma relação
   lógica, funcional, cultural ou conceitual que seja forte e facilmente
   compreensível.

4. O resultado deve ser um conceito real e reconhecível.

5. O resultado deve ser intuitivo para o jogador.
   O jogador deve conseguir entender razoavelmente por que os dois
   elementos produziram aquele resultado.

6. Não invente palavras, objetos, criaturas ou conceitos apenas para
   produzir uma resposta.

7. Não force uma combinação.
   Se a relação entre os elementos for fraca, extremamente abstrata,
   arbitrária ou não existir, a combinação deve ser NEGADA.

8. NÃO tente criar uma associação apenas para evitar uma resposta negada.
   'denied' é uma resposta válida e esperada.

9. Não use relações extremamente indiretas.
   Por exemplo, não considere que dois elementos combinam apenas porque
   ambos podem estar relacionados a algum terceiro conceito muito distante.

10. Quando houver várias combinações plausíveis, escolha aquela que
    melhor equilibrar:
    - precisão científica;
    - relação entre os elementos;
    - especificidade;
    - intuitividade.

11. Escolha um único resultado.
    Nunca retorne múltiplos resultados.

12. Para uma combinação válida, escolha um único emoji Unicode que
    represente claramente o resultado.

13. Não explique seu raciocínio.


CRITÉRIO DE NEGATIVA:

Se você não conseguir justificar a combinação através de uma relação
científica, física, química, biológica, lógica, funcional, cultural ou
conceitual suficientemente forte, NEGUE a combinação.

NUNCA invente um resultado apenas para preencher a resposta.


FORMATO OBRIGATÓRIO:

Se a combinação for válida:

{
    \"status\": \"success\",
    \"nome\": \"Nome do resultado\",
    \"emoji\": \"Emoji\"
}

Se a combinação não for válida:

{
    \"status\": \"denied\",
    \"nome\": null,
    \"emoji\": null
}


EXEMPLOS:

Fogo + Água
→
{
    \"status\": \"success\",
    \"nome\": \"Vapor\",
    \"emoji\": \"💨\"
}

Lava + Água
→
{
    \"status\": \"success\",
    \"nome\": \"Obsidiana\",
    \"emoji\": \"🖤\"
}

Terra + Água
→
{
    \"status\": \"success\",
    \"nome\": \"Lama\",
    \"emoji\": \"🟤\"
}

Celular + Peixe
→
{
    \"status\": \"denied\",
    \"nome\": null,
    \"emoji\": null
}


RETORNE SOMENTE O JSON.
NÃO ESCREVA NENHUM TEXTO ANTES OU DEPOIS DO JSON.
";

$url = "https://generativelanguage.googleapis.com/v1beta/interactions";

$data = [
    "model" => "gemini-3.5-flash-lite",
    "input" => $prompt
];

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_POST => true,

    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-goog-api-key: " . $apiKey
    ],

    CURLOPT_POSTFIELDS => json_encode($data),

    CURLOPT_RETURNTRANSFER => true
]);

$response = curl_exec($ch);

if ($response === false) {
    echo json_encode([
        "erro" => "Erro cURL: " . curl_error($ch)
    ]);

    exit;
}

$resultado = json_decode($response, true);

if (isset($resultado["error"])) {
    echo json_encode([
        "erro" => $resultado["error"]["message"]
    ]);

    exit;
}

// Procura o texto retornado pela Gemini
$resposta = null;

foreach ($resultado["steps"] ?? [] as $step) {

    if (
        ($step["type"] ?? "") === "model_output" &&
        isset($step["content"])
    ) {

        foreach ($step["content"] as $content) {

            if (($content["type"] ?? "") === "text") {
                $resposta = $content["text"];
            }

        }
    }
}

if (!$resposta) {
    echo json_encode([
        "erro" => "Gemini não retornou resultado"
    ]);

    exit;
}

// Remove possíveis ```json
$resposta = trim($resposta);
$resposta = str_replace("```json", "", $resposta);
$resposta = str_replace("```", "", $resposta);
$resposta = trim($resposta);

$jsonFinal = json_decode($resposta, true);

if (!$jsonFinal) {
    echo json_encode([
        "erro" => "Resposta da Gemini não é um JSON válido",
        "resposta" => $resposta
    ]);

    exit;
}

// Retorna para o Roblox
echo json_encode([
    "nome" => $jsonFinal["nome"],
    "emoji" => $jsonFinal["emoji"]
], JSON_UNESCAPED_UNICODE);