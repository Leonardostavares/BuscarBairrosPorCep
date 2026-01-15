<?php
set_time_limit(0);

$f_in = fopen('ListaCepsCarnaval.csv', 'r'); 
$f_out = fopen('BairroPorCep.csv', 'w');

fwrite($f_out, "\xEF\xBB\xBF");
fputcsv($f_out, ['Bairro_API', 'CEP'], ";"); // Cabeçalho sem UF

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT      => 'Mozilla/5.0'
]);

$cache_ceps = []; 

echo "Processando...\n";

while ($linha = fgetcsv($f_in, 0, ";")) {
    $cep = preg_replace('/[^0-9]/', '', $linha[0]);
    if (strlen($cep) !== 8) continue;

    // Se NÃO estiver no cache, busca na API
    if (!isset($cache_ceps[$cep])) {
        curl_setopt($ch, CURLOPT_URL, "https://viacep.com.br/ws/$cep/json/");
        $res = json_decode(curl_exec($ch), true);

        if (isset($res['bairro'])) {
            $nome = !empty($res['bairro']) ? $res['bairro'] : $res['localidade'];
            $cache_ceps[$cep] = trim(explode(',', preg_replace('/\s?\(.*?\)/', '', $nome))[0]);
            echo "API: $cep -> {$cache_ceps[$cep]}\n";
            sleep(1); 
        } else {
            $cache_ceps[$cep] = 'NÃO ENCONTRADO';
        }
    }

    // Grava no CSV o que estiver no cache (seja o bairro ou o erro)
    fputcsv($f_out, [$cache_ceps[$cep], $cep], ";");
}

fclose($f_in); fclose($f_out); curl_close($ch);
echo "Finalizado!";