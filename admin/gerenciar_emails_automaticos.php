<?php
/**
 * Gerenciamento de Emails Automáticos
 * Interface para testar e visualizar logs dos crons
 */

require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';

$titulo = "Emails Automáticos";
requer_login('admin');

$mensagem = '';
$tipo_mensagem = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'executar_aniversarios') {
        ob_start();
        include __DIR__ . '/../cron/cron_aniversarios.php';
        $resultado = ob_get_clean();

        $resultado_json = json_decode($resultado, true);
        if ($resultado_json && isset($resultado_json['success'])) {
            if ($resultado_json['success']) {
                $mensagem = "Emails de aniversário processados! Enviados: {$resultado_json['enviados']}, Erros: {$resultado_json['erros']}";
                $tipo_mensagem = 'success';
            } else {
                $mensagem = "Erro ao processar aniversários: " . ($resultado_json['error'] ?? 'Erro desconhecido');
                $tipo_mensagem = 'danger';
            }
        } else {
            $mensagem = "Processo executado. Verifique os logs para detalhes.";
            $tipo_mensagem = 'info';
        }
    } elseif ($acao === 'executar_lembretes') {
        ob_start();
        include __DIR__ . '/../cron/cron_lembretes_agendamento.php';
        $resultado = ob_get_clean();

        $resultado_json = json_decode($resultado, true);
        if ($resultado_json && isset($resultado_json['success'])) {
            if ($resultado_json['success']) {
                $mensagem = "Lembretes processados! Enviados: {$resultado_json['enviados']}, Sem email: {$resultado_json['sem_email']}, Erros: {$resultado_json['erros']}";
                $tipo_mensagem = 'success';
            } else {
                $mensagem = "Erro ao processar lembretes: " . ($resultado_json['error'] ?? 'Erro desconhecido');
                $tipo_mensagem = 'danger';
            }
        } else {
            $mensagem = "Processo executado. Verifique os logs para detalhes.";
            $tipo_mensagem = 'info';
        }
    } elseif ($acao === 'alternar_aniversarios') {
        $novo_valor = isset($_POST['ativar']) ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE configuracoes SET lembrar_aniversarios = ? WHERE id = 1");
        $stmt->execute([$novo_valor]);
        $mensagem = $novo_valor ? "Lembretes de aniversário ativados" : "Lembretes de aniversário desativados";
        $tipo_mensagem = 'success';
    }
}

// Buscar configurações
$config = $pdo->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Buscar configurações de email
$config_email = $pdo->query("SELECT * FROM configuracoes_email WHERE id = 1")->fetch();

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-envelope-open-text me-2"></i>Emails Automáticos</h2>
                <a href="configuracoes_email.php" class="btn btn-outline-primary">
                    <i class="fas fa-cog me-2"></i>Configurar SMTP
                </a>
            </div>
        </div>
    </div>

    <?php if ($mensagem): ?>
    <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show">
        <?php echo htmlspecialchars($mensagem); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Status do SMTP -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card <?php echo ($config_email && $config_email['smtp_ativo']) ? 'border-success' : 'border-warning'; ?>">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-server fa-3x <?php echo ($config_email && $config_email['smtp_ativo']) ? 'text-success' : 'text-warning'; ?>"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">Status do Servidor SMTP</h5>
                            <?php if ($config_email && $config_email['smtp_ativo']): ?>
                                <p class="mb-0 text-success">
                                    <i class="fas fa-check-circle me-1"></i>
                                    <strong>Ativo</strong> - Emails serão enviados
                                </p>
                                <small class="text-muted">
                                    Servidor: <?php echo htmlspecialchars($config_email['smtp_host'] ?? 'N/A'); ?>:<?php echo $config_email['smtp_porta'] ?? 'N/A'; ?>
                                </small>
                            <?php else: ?>
                                <p class="mb-0 text-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <strong>Inativo</strong> - Configure o SMTP para enviar emails
                                </p>
                                <a href="configuracoes_email.php" class="btn btn-sm btn-warning mt-2">
                                    Configurar Agora
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de Automações -->
    <div class="row">
        <!-- Aniversários -->
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h5 class="mb-0 text-white">
                        <i class="fas fa-birthday-cake me-2"></i>Aniversários
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Envia emails automáticos para clientes aniversariantes do dia.
                    </p>

                    <div class="mb-3">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
                            <input type="hidden" name="acao" value="alternar_aniversarios">
                            <?php if ($config['lembrar_aniversarios'] ?? 0): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-1"></i> Sistema ativo
                                </div>
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-power-off me-1"></i>Desativar
                                </button>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-pause-circle me-1"></i> Sistema desativado
                                </div>
                                <input type="hidden" name="ativar" value="1">
                                <button type="submit" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-power-off me-1"></i>Ativar
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="d-flex gap-2">
                        <form method="POST" class="flex-fill">
                            <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
                            <input type="hidden" name="acao" value="executar_aniversarios">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-play me-1"></i>Executar Agora
                            </button>
                        </form>
                        <a href="#" onclick="verLog('aniversarios'); return false;" class="btn btn-outline-secondary">
                            <i class="fas fa-file-alt"></i>
                        </a>
                    </div>

                    <hr>

                    <h6 class="text-muted small">Configuração do Cron Job:</h6>
                    <pre class="bg-light p-2 rounded small mb-0"><code>0 8 * * * php <?php echo realpath(__DIR__ . '/../cron/cron_aniversarios.php'); ?></code></pre>
                </div>
            </div>
        </div>

        <!-- Lembretes de Agendamento -->
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <h5 class="mb-0 text-white">
                        <i class="fas fa-calendar-check me-2"></i>Lembretes de Agendamento
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Envia lembretes 24h antes dos agendamentos (para o dia seguinte).
                    </p>

                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-1"></i> Sempre ativo
                    </div>

                    <div class="d-flex gap-2">
                        <form method="POST" class="flex-fill">
                            <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
                            <input type="hidden" name="acao" value="executar_lembretes">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-play me-1"></i>Executar Agora
                            </button>
                        </form>
                        <a href="#" onclick="verLog('lembretes'); return false;" class="btn btn-outline-secondary">
                            <i class="fas fa-file-alt"></i>
                        </a>
                    </div>

                    <hr>

                    <h6 class="text-muted small">Configuração do Cron Job:</h6>
                    <pre class="bg-light p-2 rounded small mb-0"><code>0 18 * * * php <?php echo realpath(__DIR__ . '/../cron/cron_lembretes_agendamento.php'); ?></code></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Histórico de Emails -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Últimos Emails Enviados</h5>
                </div>
                <div class="card-body p-0">
                    <?php
                    try {
                        $logs = $pdo->query("
                            SELECT destinatario, assunto, status, erro, criado_em
                            FROM logs_email
                            ORDER BY id DESC
                            LIMIT 20
                        ")->fetchAll();

                        if (empty($logs)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                            Nenhum email enviado ainda
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th>Destinatário</th>
                                        <th>Assunto</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td class="small">
                                            <?php echo date('d/m/Y H:i', strtotime($log['criado_em'])); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['destinatario']); ?></td>
                                        <td class="small"><?php echo htmlspecialchars(substr($log['assunto'], 0, 50)); ?></td>
                                        <td>
                                            <?php if ($log['status'] === 'enviado'): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Enviado
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger" title="<?php echo htmlspecialchars($log['erro']); ?>">
                                                    <i class="fas fa-times me-1"></i>Erro
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif;
                    } catch (PDOException $e) {
                        echo '<div class="p-4 text-center text-muted">Tabela de logs não configurada</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ver Logs -->
<div class="modal fade" id="modalLog" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-alt me-2"></i>Log de Execução</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="logContent" class="bg-dark text-light p-3 rounded" style="max-height: 400px; overflow-y: auto;">Carregando...</pre>
            </div>
        </div>
    </div>
</div>

<script>
function verLog(tipo) {
    const modal = new bootstrap.Modal(document.getElementById('modalLog'));
    const logContent = document.getElementById('logContent');

    logContent.textContent = 'Carregando log...';
    modal.show();

    const arquivo = tipo === 'aniversarios' ? 'cron_aniversarios' : 'cron_lembretes';
    const mes = new Date().toISOString().slice(0, 7); // YYYY-MM

    fetch(`../logs/${arquivo}_${mes}.log`)
        .then(response => {
            if (!response.ok) throw new Error('Log não encontrado');
            return response.text();
        })
        .then(data => {
            if (data.trim() === '') {
                logContent.textContent = 'Log vazio - nenhuma execução registrada ainda.';
            } else {
                logContent.textContent = data;
                // Scroll para o final (últimas linhas)
                logContent.scrollTop = logContent.scrollHeight;
            }
        })
        .catch(error => {
            logContent.textContent = 'Erro ao carregar log: ' + error.message + '\n\nO log ainda não existe ou não foi executado neste mês.';
        });
}
</script>

<style>
.bg-gradient {
    color: white !important;
}
.bg-gradient h5 {
    color: white !important;
}
</style>

<?php include '../includes/footer.php'; ?>
