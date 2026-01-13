<?php
// test_horarios.php - COLOQUE NA RAIZ DO PROJETO PARA TESTAR
require_once '../includes/db_connect.php';

echo "<h2>Teste de Sistema de Horários</h2>";

// 1. TESTAR CONFIGURAÇÕES
echo "<h3>1. Configurações</h3>";
$config = $pdo->query("SELECT * FROM configuracoes LIMIT 1")->fetch();
echo "<pre>";
print_r($config);
echo "</pre>";

// 2. TESTAR PROFISSIONAIS
echo "<h3>2. Profissionais Ativos</h3>";
$profs = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo = 'profissional' AND ativo = 1")->fetchAll();
echo "<pre>";
print_r($profs);
echo "</pre>";

// 3. TESTAR SERVIÇOS
echo "<h3>3. Serviços Disponíveis</h3>";
$servicos = $pdo->query("SELECT id, nome, duracao_min, preco FROM servicos WHERE ativo = 1")->fetchAll();
echo "<pre>";
print_r($servicos);
echo "</pre>";

// 4. TESTAR AGENDAMENTOS
echo "<h3>4. Agendamentos Existentes</h3>";
$ags = $pdo->query("SELECT id, profissional_id, data, hora_inicio, status FROM agendamentos ORDER BY data DESC LIMIT 5")->fetchAll();
echo "<pre>";
print_r($ags);
echo "</pre>";

// 5. SIMULAR BUSCA DE HORÁRIOS
echo "<h3>5. Simulação de Busca de Horários</h3>";
$data_teste = date('Y-m-d', strtotime('+1 day'));
$duracao_teste = 60;
$prof_teste = $profs[0]['id'] ?? 1;

echo "<p><strong>Parâmetros:</strong></p>";
echo "<ul>";
echo "<li>Data: $data_teste</li>";
echo "<li>Duração: $duracao_teste minutos</li>";
echo "<li>Profissional ID: $prof_teste</li>";
echo "</ul>";

$url_teste = "../api/get_horarios_disponiveis.php?data=$data_teste&duracao=$duracao_teste&profissional_id=$prof_teste";
echo "<p><strong>URL de teste:</strong> <a href='$url_teste' target='_blank'>$url_teste</a></p>";

// Fazer requisição interna
echo "<h4>Resposta da API:</h4>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/$url_teste");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$resposta = curl_exec($ch);
curl_close($ch);

echo "<pre>";
echo htmlspecialchars($resposta);
echo "</pre>";

$json = json_decode($resposta, true);
if ($json) {
    echo "<h4>JSON Decodificado:</h4>";
    echo "<pre>";
    print_r($json);
    echo "</pre>";
}

// 6. VERIFICAR TABELAS NECESSÁRIAS
echo "<h3>6. Verificar Estrutura do Banco</h3>";
$tabelas = ['usuarios', 'servicos', 'agendamentos', 'agendamento_itens', 'configuracoes'];
foreach ($tabelas as $tabela) {
    $existe = $pdo->query("SHOW TABLES LIKE '$tabela'")->rowCount() > 0;
    echo "<p>Tabela <strong>$tabela</strong>: " . ($existe ? "✅ Existe" : "❌ NÃO EXISTE") . "</p>";
    
    if ($existe) {
        $colunas = $pdo->query("SHOW COLUMNS FROM $tabela")->fetchAll(PDO::FETCH_COLUMN);
        echo "<ul><li>" . implode("</li><li>", $colunas) . "</li></ul>";
    }
}

echo "<hr>";
echo "<h3>✅ Diagnóstico Completo</h3>";
echo "<p>Use este relatório para identificar problemas.</p>";
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
h2 { color: #0d6efd; border-bottom: 3px solid #0d6efd; padding-bottom: 10px; }
h3 { color: #198754; margin-top: 30px; }
pre { background: #f8f9fa; padding: 15px; border-radius: 8px; overflow-x: auto; border-left: 4px solid #0d6efd; }
ul { line-height: 1.8; }
</style>