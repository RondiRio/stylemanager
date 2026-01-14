<?php
ob_start();  // Inicia buffer para capturar outputs acidentais

error_reporting(E_ALL);
ini_set('display_errors', 0);  // Desativa display (use logs em vez)
ini_set('log_errors', 1);  // Ativa logging de erros

// api/get_horarios_disponiveis.php (VERSÃO CORRIGIDA E FUNCIONAL)
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

// Receber parâmetros
$data = $_GET['data'] ?? '';
$duracao = (int)($_GET['duracao'] ?? 0);
$profissional_id = (int)($_GET['profissional_id'] ?? 0);

// Validação básica
if (!$data || !$duracao || !$profissional_id) {
    echo json_encode([
        'sucesso' => false, 
        'erro' => 'Parâmetros incompletos',
        'debug' => [
            'data' => $data,
            'duracao' => $duracao,
            'profissional_id' => $profissional_id
        ]
    ]);
    ob_end_flush();
    exit;
}

// Validar formato da data
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Formato de data inválido']);
    ob_end_flush();
    exit;
}

try {
    // Buscar configurações do horário de funcionamento e dias de funcionamento
    $stmt = $pdo->query("SELECT horario_abertura, horario_fechamento, intervalo_slot, funciona_domingo, funciona_segunda, funciona_terca, funciona_quarta, funciona_quinta, funciona_sexta, funciona_sabado FROM configuracoes LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    $horario_abertura = $config['horario_abertura'] ?? '09:00:00';
    $horario_fechamento = $config['horario_fechamento'] ?? '19:00:00';
    $intervalo_slot = (int)($config['intervalo_slot'] ?? 30);

    // Verificar se a empresa funciona no dia da semana selecionado
    $dia_semana = date('w', strtotime($data)); // 0=Domingo, 1=Segunda, ..., 6=Sábado
    $dias_funcionamento = [
        0 => (int)($config['funciona_domingo'] ?? 0),
        1 => (int)($config['funciona_segunda'] ?? 1),
        2 => (int)($config['funciona_terca'] ?? 1),
        3 => (int)($config['funciona_quarta'] ?? 1),
        4 => (int)($config['funciona_quinta'] ?? 1),
        5 => (int)($config['funciona_sexta'] ?? 1),
        6 => (int)($config['funciona_sabado'] ?? 1)
    ];

    $dias_semana_nomes = [
        0 => 'domingo',
        1 => 'segunda-feira',
        2 => 'terça-feira',
        3 => 'quarta-feira',
        4 => 'quinta-feira',
        5 => 'sexta-feira',
        6 => 'sábado'
    ];

    if (!$dias_funcionamento[$dia_semana]) {
        echo json_encode([
            'sucesso' => false,
            'erro' => 'Não funcionamos às ' . $dias_semana_nomes[$dia_semana] . 's. Por favor, escolha outro dia.',
            'dia_nao_funciona' => true
        ]);
        ob_end_flush();
        exit;
    }

    // PROTEÇÃO: Evitar intervalo inválido para prevenir loop infinito
    if ($intervalo_slot <= 0) {
        $intervalo_slot = 30;  // Default seguro
        // Ou: echo json_encode(['sucesso' => false, 'erro' => 'Intervalo de slot inválido (deve ser maior que 0).']); ob_end_flush(); exit;
    }

    // Buscar agendamentos existentes para o profissional na data
    $stmt = $pdo->prepare("
        SELECT 
            a.hora_inicio,
            TIME_FORMAT(
                ADDTIME(a.hora_inicio, SEC_TO_TIME(COALESCE(SUM(s.duracao_min), 30) * 60)), 
                '%H:%i:%s'
            ) AS hora_fim
        FROM agendamentos a
        LEFT JOIN agendamento_itens ai ON ai.agendamento_id = a.id
        LEFT JOIN servicos s ON s.id = ai.servico_id
        WHERE a.profissional_id = ? 
          AND a.data = ? 
          AND a.status NOT IN ('cancelado')
        GROUP BY a.id, a.hora_inicio
    ");
    $stmt->execute([$profissional_id, $data]);
    $agendamentos_ocupados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Gerar lista de horários disponíveis
    $horarios = [];
    
    // Converter strings de hora para timestamps
    $inicio = strtotime($data . ' ' . $horario_abertura);
    $fim = strtotime($data . ' ' . $horario_fechamento);
    $agora = time();
    
    // Ajustar limite para não ultrapassar horário de fechamento
    $limite_inicio = $fim - ($duracao * 60);

    // Loop para gerar slots de horário
    $slot_atual = $inicio;
    
    // PROTEÇÃO: Limite máximo de iterações para segurança
    $max_iter = 1000;  // Previne loops excessivos
    $iter = 0;

    while ($slot_atual <= $limite_inicio && $iter < $max_iter) {
        $hora_inicio_str = date('H:i:s', $slot_atual);
        $hora_fim_slot = $slot_atual + ($duracao * 60);
        $hora_fim_str = date('H:i:s', $hora_fim_slot);
        
        // Verificar se está no passado (apenas para hoje)
        $disponivel = true;
        if ($data === date('Y-m-d') && $slot_atual <= $agora) {
            $disponivel = false;
        }
        
        // Verificar conflitos com agendamentos existentes
        if ($disponivel) {
            foreach ($agendamentos_ocupados as $ocupado) {
                $ocupado_inicio = strtotime($data . ' ' . $ocupado['hora_inicio']);
                $ocupado_fim = strtotime($data . ' ' . $ocupado['hora_fim']);
                
                // Verifica se há sobreposição
                if (($slot_atual < $ocupado_fim) && ($hora_fim_slot > $ocupado_inicio)) {
                    $disponivel = false;
                    break;
                }
            }
        }
        
        $horarios[] = [
            'hora' => $hora_inicio_str,
            'hora_fim' => $hora_fim_str,
            'disponivel' => $disponivel,
            'label' => date('H:i', $slot_atual)
        ];
        
        // Avançar para o próximo slot
        $slot_atual += ($intervalo_slot * 60);
        $iter++;
    }

    // Checagem pós-loop
    if ($iter >= $max_iter) {
        echo json_encode([
            'sucesso' => false,
            'erro' => 'Loop excessivo detectado - verifique intervalo_slot ou configurações de horário.'
        ]);
        ob_end_flush();
        exit;
    }

    // Retornar resposta
    echo json_encode([
        'sucesso' => true,
        'horarios' => $horarios,
        'data' => $data,
        'duracao_total' => $duracao,
        'profissional_id' => $profissional_id,
        'total_horarios' => count($horarios),
        'config' => [
            'abertura' => $horario_abertura,
            'fechamento' => $horario_fechamento,
            'intervalo' => $intervalo_slot
        ]
    ]);
    ob_end_flush();

} catch (PDOException $e) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Erro no banco de dados: ' . $e->getMessage()
    ]);
    ob_end_flush();
} catch (Exception $e) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Erro ao processar horários: ' . $e->getMessage()
    ]);
    ob_end_flush();
}
?>