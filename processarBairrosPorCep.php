    <?php
    set_time_limit(0);
    $f_in = fopen('bairros-carnaval-2026.csv', 'r');
    $f_out = fopen('BairroPorCep.csv', 'w');

    fwrite($f_out, "\xEF\xBB\xBF");
    fputcsv($f_out, ['Bairro', 'UF', 'CEP'], ";");

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0'
    ]);

    echo "Processando...\n";

    while ($linha = fgetcsv($f_in)) {
        $cep = preg_replace('/[^0-9]/', '', $linha[0]);
        if (strlen($cep) !== 8) continue;

        curl_setopt($ch, CURLOPT_URL, "https://viacep.com.br/ws/$cep/json/");
        $res = json_decode(curl_exec($ch), true);

        if (isset($res['bairro'])) {
            
            if (!empty($res['bairro'])) {
                $nome = $res['bairro'];
            } else {
                $nome = $res['localidade'];
            }

            $nome = trim(explode(',', preg_replace('/\s?\(.*?\)/', '', $nome))[0]);

            fputcsv($f_out, [$nome, $res['uf'], $cep], ";");
            echo "$cep -> $nome\n";
        }
        usleep(500000); 
    }

    fclose($f_in);
    fclose($f_out);
    curl_close($ch);
    echo "Finalizado!";