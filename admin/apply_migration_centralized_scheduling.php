<?php
/**
 * Script de Migração: Sistema de Agendamento Centralizado
 * Aplica mudanças no banco de dados para suportar:
 * - Tipo de usuário 'recepcionista'
 * - Data de nascimento e busca de clientes
 * - Clientes rápidos (não cadastrados)
 * - Agenda centralizada
 * - Controles de rede social
 */

require_once '../includes/db_connect.php';
require_once '../includes/auth.php';

// Apenas admin pode executar migrações
requer_login('admin');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migração - Agendamento Centralizado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-database me-2"></i>Migração do Banco de Dados</h4>
                <small>Sistema de Agendamento Centralizado</small>
            </div>
            <div class="card-body">
                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
                    echo '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Aplicando migração...</div>';

                    $sql_file = __DIR__ . '/../docs/SQL_MIGRATION_CENTRALIZED_SCHEDULING.sql';

                    if (!file_exists($sql_file)) {
                        echo '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Arquivo SQL não encontrado!</div>';
                        exit;
                    }

                    $sql_content = file_get_contents($sql_file);

                    // Remover comentários e dividir em statements
                    $sql_content = preg_replace('/--.*$/m', '', $sql_content);
                    $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);

                    // Dividir por ; mas não dentro de CREATE VIEW
                    $statements = [];
                    $current = '';
                    $in_view = false;

                    foreach (explode("\n", $sql_content) as $line) {
                        $line = trim($line);
                        if (empty($line)) continue;

                        if (stripos($line, 'CREATE OR REPLACE VIEW') !== false || stripos($line, 'CREATE VIEW') !== false) {
                            $in_view = true;
                        }

                        $current .= $line . ' ';

                        if (substr($line, -1) === ';') {
                            if (!$in_view || stripos($line, ';') !== false) {
                                $statements[] = trim($current);
                                $current = '';
                                $in_view = false;
                            }
                        }
                    }

                    $success_count = 0;
                    $error_count = 0;
                    $errors = [];

                    echo '<div class="mt-3">';
                    echo '<h5>Executando comandos SQL:</h5>';
                    echo '<div class="list-group">';

                    foreach ($statements as $index => $statement) {
                        $statement = trim($statement);
                        if (empty($statement) || $statement === ';') continue;

                        // Mostrar preview do comando
                        $preview = substr($statement, 0, 100);
                        if (strlen($statement) > 100) $preview .= '...';

                        echo '<div class="list-group-item">';
                        echo '<div class="d-flex justify-content-between align-items-start">';
                        echo '<div class="flex-grow-1">';
                        echo '<small class="text-muted font-monospace">' . htmlspecialchars($preview) . '</small>';
                        echo '</div>';

                        try {
                            $pdo->exec($statement);
                            echo '<span class="badge bg-success ms-2"><i class="fas fa-check"></i> OK</span>';
                            $success_count++;
                        } catch (PDOException $e) {
                            // Ignorar erros de "já existe" ou "coluna duplicada"
                            $error_msg = $e->getMessage();
                            if (
                                stripos($error_msg, 'Duplicate column') !== false ||
                                stripos($error_msg, 'already exists') !== false ||
                                stripos($error_msg, 'duplicate key') !== false ||
                                stripos($error_msg, "Multiple primary key") !== false
                            ) {
                                echo '<span class="badge bg-warning ms-2"><i class="fas fa-exclamation-triangle"></i> Já existe</span>';
                            } else {
                                echo '<span class="badge bg-danger ms-2"><i class="fas fa-times"></i> ERRO</span>';
                                $errors[] = ['statement' => $preview, 'error' => $error_msg];
                                $error_count++;
                            }
                        }

                        echo '</div></div>';
                    }

                    echo '</div>'; // list-group
                    echo '</div>'; // mt-3

                    // Resumo
                    echo '<div class="alert alert-' . ($error_count > 0 ? 'warning' : 'success') . ' mt-4">';
                    echo '<h5><i class="fas fa-check-circle me-2"></i>Migração Concluída!</h5>';
                    echo '<p class="mb-0">';
                    echo '<strong>Comandos executados:</strong> ' . $success_count . '<br>';
                    if ($error_count > 0) {
                        echo '<strong>Erros:</strong> ' . $error_count;
                    }
                    echo '</p>';
                    echo '</div>';

                    // Mostrar erros detalhados se houver
                    if (!empty($errors)) {
                        echo '<div class="alert alert-danger mt-3">';
                        echo '<h6>Erros Detalhados:</h6>';
                        foreach ($errors as $error) {
                            echo '<div class="mb-2">';
                            echo '<strong>Comando:</strong> <code>' . htmlspecialchars($error['statement']) . '</code><br>';
                            echo '<strong>Erro:</strong> ' . htmlspecialchars($error['error']);
                            echo '</div><hr>';
                        }
                        echo '</div>';
                    }

                    echo '<a href="configuracoes.php" class="btn btn-primary mt-3">';
                    echo '<i class="fas fa-arrow-left me-2"></i>Voltar para Configurações';
                    echo '</a>';

                } else {
                    // Formulário de confirmação
                    ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Atenção!</strong> Esta migração irá modificar a estrutura do banco de dados.
                    </div>

                    <h5>O que será adicionado:</h5>
                    <ul>
                        <li><i class="fas fa-user-tie text-primary me-2"></i><strong>Tipo de usuário "Recepcionista"</strong> - Novo perfil com acesso à agenda centralizada</li>
                        <li><i class="fas fa-birthday-cake text-success me-2"></i><strong>Data de nascimento</strong> - Campo para lembrar aniversários dos clientes</li>
                        <li><i class="fas fa-search text-info me-2"></i><strong>Sistema de busca</strong> - Busca rápida por nome ou telefone</li>
                        <li><i class="fas fa-user-plus text-warning me-2"></i><strong>Clientes rápidos</strong> - Cadastro simplificado apenas com nome e telefone</li>
                        <li><i class="fas fa-calendar-alt text-danger me-2"></i><strong>Agenda centralizada</strong> - Interface de agendamento para admin/recepcionista</li>
                        <li><i class="fas fa-dollar-sign text-success me-2"></i><strong>Preço customizado</strong> - Permite override de preço em serviços específicos</li>
                        <li><i class="fas fa-toggle-on text-primary me-2"></i><strong>Controles sociais</strong> - Toggle para cadastro de clientes e landing page</li>
                    </ul>

                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Nota:</strong> Todas as funcionalidades atuais serão mantidas. Esta migração apenas adiciona novos recursos.
                    </div>

                    <form method="POST" class="mt-4">
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="confirmar" name="confirmar" required>
                            <label class="form-check-label" for="confirmar">
                                Confirmo que entendo as mudanças e desejo aplicar a migração
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-play me-2"></i>Aplicar Migração
                        </button>
                        <a href="configuracoes.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                    </form>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
