<?php 

require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
require_once '../includes/email_sender.php';
$titulo = "Configurações de E-mail";
requer_login('admin');

$mensagem = '';
$tipo_mensagem = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    $acao = $_POST['acao'] ?? 'salvar';
    
    if ($acao === 'testar') {
        $email_teste = $_POST['email_teste'] ?? '';
        if (filter_var($email_teste, FILTER_VALIDATE_EMAIL)) {
            $resultado = testar_configuracao_email($email_teste);
            $mensagem = $resultado['sucesso'] ? $resultado['mensagem'] : $resultado['erro'];
            $tipo_mensagem = $resultado['sucesso'] ? 'success' : 'danger';
        } else {
            $mensagem = 'E-mail de teste inválido';
            $tipo_mensagem = 'danger';
        }
    } else {
        // Salvar configurações
        $dados = [
            'smtp_host' => $_POST['smtp_host'] ?? '',
            'smtp_porta' => (int)($_POST['smtp_porta'] ?? 587),
            'smtp_usuario' => $_POST['smtp_usuario'] ?? '',
            'smtp_seguranca' => $_POST['smtp_seguranca'] ?? 'tls',
            'smtp_remetente' => $_POST['smtp_remetente'] ?? '',
            'smtp_nome_remetente' => $_POST['smtp_nome_remetente'] ?? '',
            'smtp_responder_para' => $_POST['smtp_responder_para'] ?? '',
            'smtp_ativo' => isset($_POST['smtp_ativo']) ? 1 : 0,
            'smtp_debug' => isset($_POST['smtp_debug']) ? 1 : 0
        ];
        
        // Só atualizar senha se foi informada
        if (!empty($_POST['smtp_senha'])) {
            $dados['smtp_senha'] = $_POST['smtp_senha'];
        }
        
        // Verificar se já existe configuração
        $existe = $pdo->query("SELECT id FROM configuracoes_email WHERE id = 1")->fetch();
        
        if ($existe) {
            $campos = [];
            $valores = [];
            foreach ($dados as $campo => $valor) {
                $campos[] = "$campo = ?";
                $valores[] = $valor;
            }
            $sql = "UPDATE configuracoes_email SET " . implode(', ', $campos) . " WHERE id = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($valores);
        } else {
            $campos = implode(', ', array_keys($dados));
            $placeholders = implode(', ', array_fill(0, count($dados), '?'));
            $sql = "INSERT INTO configuracoes_email ($campos) VALUES ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($dados));
        }
        
        $mensagem = 'Configurações salvas com sucesso!';
        $tipo_mensagem = 'success';
    }
}

// Buscar configurações atuais
$config = $pdo->query("SELECT * FROM configuracoes_email WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-gradient-primary text-white py-3">
                    <h4 class="mb-0">
                        <i class="fas fa-envelope me-2"></i>Configurações de E-mail SMTP
                    </h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($mensagem): ?>
                    <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show">
                        <?php echo htmlspecialchars($mensagem); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
                        <input type="hidden" name="acao" value="salvar">

                        <!-- Status -->
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" 
                                       id="smtp_ativo" name="smtp_ativo" 
                                       <?php echo ($config['smtp_ativo'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-bold" for="smtp_ativo">
                                    <i class="fas fa-power-off me-1"></i>Ativar Envio de E-mails
                                </label>
                                <div class="form-text">Desative para não enviar e-mails (útil em desenvolvimento)</div>
                            </div>
                        </div>

                        <hr>

                        <!-- Provedor Comum -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Provedor Comum</label>
                            <select class="form-select" id="provedor" onchange="preencherProvedor(this.value)">
                                <option value="">Selecione ou configure manualmente...</option>
                                <option value="gmail">Gmail</option>
                                <option value="outlook">Outlook / Hotmail</option>
                                <option value="yahoo">Yahoo</option>
                                <option value="zoho">Zoho Mail</option>
                                <option value="sendgrid">SendGrid</option>
                            </select>
                        </div>

                        <hr>

                        <!-- Servidor SMTP -->
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Servidor SMTP *</label>
                                <input type="text" class="form-control" name="smtp_host" 
                                       id="smtp_host" required
                                       value="<?php echo htmlspecialchars($config['smtp_host'] ?? ''); ?>"
                                       placeholder="smtp.gmail.com">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Porta *</label>
                                <input type="number" class="form-control" name="smtp_porta" 
                                       id="smtp_porta" required
                                       value="<?php echo $config['smtp_porta'] ?? 587; ?>">
                            </div>
                        </div>

                        <!-- Segurança -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tipo de Segurança *</label>
                            <select class="form-select" name="smtp_seguranca" id="smtp_seguranca" required>
                                <option value="tls" <?php echo ($config['smtp_seguranca'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS (587)</option>
                                <option value="ssl" <?php echo ($config['smtp_seguranca'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL (465)</option>
                            </select>
                        </div>

                        <!-- Credenciais -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Usuário (E-mail) *</label>
                            <input type="email" class="form-control" name="smtp_usuario" required
                                   value="<?php echo htmlspecialchars($config['smtp_usuario'] ?? ''); ?>"
                                   placeholder="seu-email@gmail.com">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Senha / App Password *</label>
                            <input type="password" class="form-control" name="smtp_senha"
                                   placeholder="<?php echo $config ? '••••••••••••' : 'Digite a senha'; ?>">
                            <div class="form-text">
                                <?php if ($config && !empty($config['smtp_senha'])): ?>
                                Deixe em branco para manter a senha atual.
                                <?php endif; ?>
                                <strong>Gmail:</strong> Use "Senha de App" (configure em sua conta Google).
                            </div>
                        </div>

                        <!-- Remetente -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">E-mail Remetente *</label>
                            <input type="email" class="form-control" name="smtp_remetente" required
                                   value="<?php echo htmlspecialchars($config['smtp_remetente'] ?? ''); ?>"
                                   placeholder="noreply@barbearia.com">
                            <div class="form-text">E-mail que aparecerá como remetente</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Nome do Remetente</label>
                            <input type="text" class="form-control" name="smtp_nome_remetente"
                                   value="<?php echo htmlspecialchars($config['smtp_nome_remetente'] ?? 'Sistema Barbearia'); ?>"
                                   placeholder="Barbearia Premium">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">E-mail para Resposta (Reply-To)</label>
                            <input type="email" class="form-control" name="smtp_responder_para"
                                   value="<?php echo htmlspecialchars($config['smtp_responder_para'] ?? ''); ?>"
                                   placeholder="contato@barbearia.com">
                        </div>

                        <!-- Debug -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       id="smtp_debug" name="smtp_debug" 
                                       <?php echo ($config['smtp_debug'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="smtp_debug">
                                    Modo Debug (registrar erros detalhados)
                                </label>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar: Teste e Ajuda -->
        <div class="col-lg-4">
            <!-- Testar E-mail -->
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Testar Configuração</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
                        <input type="hidden" name="acao" value="testar">
                        <div class="mb-3">
                            <label class="form-label">E-mail de Teste</label>
                            <input type="email" class="form-control" name="email_teste" 
                                   placeholder="seu@email.com" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-flask me-2"></i>Enviar E-mail de Teste
                        </button>
                    </form>
                </div>
            </div>

            <!-- Guia Gmail -->
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fab fa-google me-2"></i>Configurar Gmail</h6>
                </div>
                <div class="card-body small">
                    <ol class="mb-0 ps-3">
                        <li>Acesse: <a href="https://myaccount.google.com/security" target="_blank">myaccount.google.com/security</a></li>
                        <li>Ative a "Verificação em duas etapas"</li>
                        <li>Vá em "Senhas de app"</li>
                        <li>Gere uma senha para "E-mail"</li>
                        <li>Use essa senha no campo acima</li>
                    </ol>
                </div>
            </div>

            <!-- Logs -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Últimos Envios</h6>
                </div>
                <div class="card-body p-0">
                    <?php
                    try {
                        $logs = $pdo->query("
                            SELECT destinatario, assunto, status, criado_em 
                            FROM logs_email 
                            ORDER BY id DESC 
                            LIMIT 5
                        ")->fetchAll();
                        
                        if (empty($logs)): ?>
                        <div class="p-3 text-center text-muted small">Nenhum e-mail enviado ainda</div>
                        <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($logs as $log): ?>
                            <li class="list-group-item py-2 px-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="small">
                                        <strong><?php echo htmlspecialchars($log['destinatario']); ?></strong><br>
                                        <span class="text-muted"><?php echo htmlspecialchars(substr($log['assunto'], 0, 30)); ?></span>
                                    </div>
                                    <span class="badge bg-<?php echo $log['status'] === 'enviado' ? 'success' : 'danger'; ?>">
                                        <?php echo $log['status']; ?>
                                    </span>
                                </div>
                                <small class="text-muted"><?php echo date('d/m H:i', strtotime($log['criado_em'])); ?></small>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif;
                    } catch (PDOException $e) {
                        echo '<div class="p-3 text-muted small">Tabela de logs não configurada</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Preencher dados de provedores comuns
function preencherProvedor(provedor) {
    const configs = {
        gmail: {
            host: 'smtp.gmail.com',
            porta: 587,
            seguranca: 'tls'
        },
        outlook: {
            host: 'smtp.office365.com',
            porta: 587,
            seguranca: 'tls'
        },
        yahoo: {
            host: 'smtp.mail.yahoo.com',
            porta: 587,
            seguranca: 'tls'
        },
        zoho: {
            host: 'smtp.zoho.com',
            porta: 587,
            seguranca: 'tls'
        },
        sendgrid: {
            host: 'smtp.sendgrid.net',
            porta: 587,
            seguranca: 'tls'
        }
    };
    
    if (configs[provedor]) {
        document.getElementById('smtp_host').value = configs[provedor].host;
        document.getElementById('smtp_porta').value = configs[provedor].porta;
        document.getElementById('smtp_seguranca').value = configs[provedor].seguranca;
    }
}

// Validação HTML5
(() => {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
}
</style>

<?php include '../includes/footer.php'; ?>

