<?php 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// require_once __DIR__ . '/../vendor/autoload.php'; // Se usar Composer
// // OU
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

function enviar_email($para, $assunto, $corpo, $nome_destinatario = '') {
    global $pdo;
    
    try {
        // Buscar configurações de e-mail do banco
        $stmt = $pdo->query("SELECT * FROM configuracoes_email WHERE id = 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Se não houver configuração, usar valores padrão (desenvolvimento)
        if (!$config || !$config['smtp_ativo']) {
            error_log("E-mail não enviado: SMTP desativado ou não configurado para: $para");
            return false;
        }
        
        $mail = new PHPMailer(true);
        
        // Configurações do servidor SMTP
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_usuario'];
        $mail->Password   = $config['smtp_senha'];
        $mail->SMTPSecure = $config['smtp_seguranca'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)$config['smtp_porta'];
        $mail->CharSet    = 'UTF-8';
        
        // Debug (desativar em produção)
        if ($config['smtp_debug'] ?? false) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer Debug ($level): $str");
            };
        }
        
        // Remetente
        $mail->setFrom(
            $config['smtp_remetente'] ?? $config['smtp_usuario'],
            $config['smtp_nome_remetente'] ?? 'Sistema Salão'
        );
        
        // Destinatário
        $mail->addAddress($para, $nome_destinatario);
        
        // E-mail de resposta (opcional)
        if (!empty($config['smtp_responder_para'])) {
            $mail->addReplyTo($config['smtp_responder_para'], $config['smtp_nome_remetente'] ?? '');
        }
        
        // Conteúdo do e-mail
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = $corpo;
        $mail->AltBody = strip_tags($corpo); // Versão texto puro
        
        // Enviar
        $resultado = $mail->send();
        
        // Registrar log de sucesso
        registrar_log_email($para, $assunto, 'enviado', null);
        
        return true;
        
    } catch (Exception $e) {
        $erro = "Erro ao enviar e-mail: {$mail->ErrorInfo}";
        error_log($erro);
        
        // Registrar log de erro
        registrar_log_email($para, $assunto, 'erro', $mail->ErrorInfo);
        
        return false;
    }
}

function registrar_log_email($destinatario, $assunto, $status, $erro = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO logs_email (destinatario, assunto, status, erro, criado_em)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$destinatario, $assunto, $status, $erro]);
    } catch (PDOException $e) {
        // Tabela não existe, ignorar
        error_log("Tabela logs_email não existe: " . $e->getMessage());
    }
}

function testar_configuracao_email($email_teste = null) {
    global $pdo;
    
    $config = $pdo->query("SELECT * FROM configuracoes_email WHERE id = 1")->fetch();
    
    if (!$config) {
        return ['sucesso' => false, 'erro' => 'Configurações de e-mail não encontradas'];
    }
    
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_usuario'];
        $mail->Password   = $config['smtp_senha'];
        $mail->SMTPSecure = $config['smtp_seguranca'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)$config['smtp_porta'];
        $mail->SMTPDebug  = SMTP::DEBUG_CONNECTION;
        $mail->Timeout    = 10;
        
        // Tentar conexão
        $mail->smtpConnect();
        $mail->smtpClose();
        
        // Se chegou aqui, conexão OK
        if ($email_teste) {
            // Enviar e-mail de teste
            return enviar_email(
                $email_teste,
                'Teste de Configuração - Sistema Barbearia',
                '<h2>Teste de E-mail</h2><p>Se você recebeu este e-mail, a configuração está correta!</p>'
            ) ? ['sucesso' => true, 'mensagem' => 'E-mail enviado com sucesso!']
              : ['sucesso' => false, 'erro' => 'Falha ao enviar e-mail de teste'];
        }
        
        return ['sucesso' => true, 'mensagem' => 'Conexão SMTP estabelecida com sucesso!'];
        
    } catch (Exception $e) {
        return ['sucesso' => false, 'erro' => $mail->ErrorInfo];
    }
}
