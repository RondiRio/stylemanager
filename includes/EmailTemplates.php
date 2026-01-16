<?php
/**
 * Templates de Email com Cores da Marca
 */

class EmailTemplates {
    private $config;
    private $cor_primaria;
    private $cor_secundaria;
    private $cor_fundo;
    private $logo;

    public function __construct($pdo) {
        $stmt = $pdo->query("SELECT * FROM configuracoes WHERE id = 1");
        $this->config = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->cor_primaria = $this->config['cor_primaria'] ?? '#667eea';
        $this->cor_secundaria = $this->config['cor_secundaria'] ?? '#764ba2';
        $this->cor_fundo = $this->config['cor_fundo'] ?? '#f5f7fa';
        $this->logo = $this->config['logo'] ?? '';
    }

    /**
     * Template base para todos os emails
     */
    private function getBaseTemplate($titulo, $conteudo) {
        $logo_html = '';
        if ($this->logo) {
            $logo_url = 'https://' . $_SERVER['HTTP_HOST'] . '/assets/img/' . $this->logo;
            $logo_html = '<img src="' . $logo_url . '" alt="Logo" style="max-height: 60px; margin-bottom: 20px;">';
        }

        return '
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($titulo) . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: ' . $this->cor_fundo . ';">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: ' . $this->cor_fundo . '; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, ' . $this->cor_primaria . ' 0%, ' . $this->cor_secundaria . ' 100%); padding: 40px 20px; text-align: center;">
                            ' . $logo_html . '
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">' . htmlspecialchars($titulo) . '</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            ' . $conteudo . '
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 2px solid ' . $this->cor_primaria . ';">
                            <p style="margin: 0; color: #666; font-size: 12px;">
                                Este é um email automático, por favor não responda.<br>
                                © ' . date('Y') . ' - Todos os direitos reservados
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    /**
     * Email de Aniversário
     */
    public function emailAniversario($nome, $idade = null) {
        $conteudo = '
<div style="text-align: center;">
    <div style="font-size: 60px; margin-bottom: 20px;">🎂🎉</div>
    <h2 style="color: ' . $this->cor_primaria . '; margin-bottom: 20px;">
        Feliz Aniversário, ' . htmlspecialchars($nome) . '!
    </h2>
</div>

<p style="color: #333; font-size: 16px; line-height: 1.6; text-align: center;">
    ' . ($idade ? 'Parabéns pelos seus <strong style="color: ' . $this->cor_primaria . ';">' . $idade . ' anos</strong>!' : 'Parabéns pelo seu dia especial!') . '<br>
    Desejamos muita saúde, felicidade e realizações!
</p>

<div style="background: linear-gradient(135deg, ' . $this->cor_primaria . ', ' . $this->cor_secundaria . ');
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            text-align: center;">
    <p style="color: #ffffff; margin: 0; font-size: 18px; font-weight: bold;">
        🎁 Presente Especial!
    </p>
    <p style="color: #ffffff; margin: 10px 0 0 0; font-size: 14px;">
        Aproveite seu dia e não deixe de nos visitar!<br>
        Temos uma surpresa especial esperando por você! 😊
    </p>
</div>

<p style="color: #666; font-size: 14px; text-align: center; margin-top: 30px;">
    <em>Obrigado por fazer parte da nossa família!</em><br>
    <strong>Equipe ' . htmlspecialchars($this->config['tipo_empresa'] ?? 'Salão') . '</strong>
</p>';

        return $this->getBaseTemplate('Feliz Aniversário! 🎂', $conteudo);
    }

    /**
     * Email de Lembrete de Agendamento
     */
    public function emailLembreteAgendamento($nome, $data, $hora, $profissional, $servicos) {
        $servicos_lista = '';
        if (is_array($servicos)) {
            foreach ($servicos as $servico) {
                $servicos_lista .= '<li style="margin-bottom: 5px;">' . htmlspecialchars($servico) . '</li>';
            }
        } else {
            $servicos_lista = '<li>' . htmlspecialchars($servicos) . '</li>';
        }

        $conteudo = '
<div style="text-align: center; margin-bottom: 30px;">
    <div style="font-size: 50px; margin-bottom: 15px;">📅</div>
    <h2 style="color: ' . $this->cor_primaria . '; margin: 0;">
        Lembrete de Agendamento
    </h2>
</div>

<p style="color: #333; font-size: 16px;">
    Olá, <strong>' . htmlspecialchars($nome) . '</strong>!
</p>

<p style="color: #333; font-size: 16px; line-height: 1.6;">
    Este é um lembrete do seu agendamento conosco.
</p>

<div style="background-color: ' . $this->cor_fundo . ';
            border-left: 4px solid ' . $this->cor_primaria . ';
            border-radius: 5px;
            padding: 20px;
            margin: 25px 0;">
    <table width="100%" cellpadding="5">
        <tr>
            <td style="color: #666; font-weight: bold; width: 100px;">📅 Data:</td>
            <td style="color: #333; font-size: 18px;"><strong>' . htmlspecialchars($data) . '</strong></td>
        </tr>
        <tr>
            <td style="color: #666; font-weight: bold;">🕐 Horário:</td>
            <td style="color: #333; font-size: 18px;"><strong>' . htmlspecialchars($hora) . '</strong></td>
        </tr>
        <tr>
            <td style="color: #666; font-weight: bold;">👤 Profissional:</td>
            <td style="color: #333;">' . htmlspecialchars($profissional) . '</td>
        </tr>
    </table>
</div>

<div style="margin: 20px 0;">
    <p style="color: #666; font-weight: bold; margin-bottom: 10px;">✂️ Serviços:</p>
    <ul style="color: #333; line-height: 1.8; margin: 0;">
        ' . $servicos_lista . '
    </ul>
</div>

<div style="background: linear-gradient(135deg, ' . $this->cor_primaria . ', ' . $this->cor_secundaria . ');
            border-radius: 8px;
            padding: 15px;
            margin: 25px 0;
            text-align: center;">
    <p style="color: #ffffff; margin: 0; font-size: 14px;">
        ⏰ <strong>Importante:</strong> Por favor, chegue 5-10 minutos antes do horário agendado.
    </p>
</div>

<p style="color: #666; font-size: 14px; line-height: 1.6;">
    <strong>Precisa cancelar ou reagendar?</strong><br>
    Entre em contato conosco o mais breve possível.
</p>

<p style="color: #333; font-size: 16px; margin-top: 30px;">
    Aguardamos você!<br>
    <strong>Equipe ' . htmlspecialchars($this->config['tipo_empresa'] ?? 'Salão') . '</strong>
</p>';

        return $this->getBaseTemplate('Lembrete de Agendamento', $conteudo);
    }

    /**
     * Email de Confirmação de Agendamento
     */
    public function emailConfirmacaoAgendamento($nome, $data, $hora, $profissional, $servicos, $total = null) {
        $servicos_lista = '';
        if (is_array($servicos)) {
            foreach ($servicos as $servico) {
                $servicos_lista .= '<li style="margin-bottom: 5px;">' . htmlspecialchars($servico) . '</li>';
            }
        } else {
            $servicos_lista = '<li>' . htmlspecialchars($servicos) . '</li>';
        }

        $total_html = '';
        if ($total) {
            $total_html = '
<div style="background-color: #f0f9ff;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;">
    <p style="color: #666; margin: 0 0 5px 0; font-size: 14px;">Valor Estimado</p>
    <p style="color: ' . $this->cor_primaria . '; margin: 0; font-size: 24px; font-weight: bold;">
        ' . htmlspecialchars($total) . '
    </p>
</div>';
        }

        $conteudo = '
<div style="text-align: center; margin-bottom: 30px;">
    <div style="font-size: 50px; margin-bottom: 15px;">✅</div>
    <h2 style="color: ' . $this->cor_primaria . '; margin: 0;">
        Agendamento Confirmado!
    </h2>
</div>

<p style="color: #333; font-size: 16px;">
    Olá, <strong>' . htmlspecialchars($nome) . '</strong>!
</p>

<p style="color: #333; font-size: 16px; line-height: 1.6;">
    Seu agendamento foi confirmado com sucesso! 🎉
</p>

<div style="background-color: ' . $this->cor_fundo . ';
            border-left: 4px solid ' . $this->cor_primaria . ';
            border-radius: 5px;
            padding: 20px;
            margin: 25px 0;">
    <table width="100%" cellpadding="5">
        <tr>
            <td style="color: #666; font-weight: bold; width: 100px;">📅 Data:</td>
            <td style="color: #333; font-size: 18px;"><strong>' . htmlspecialchars($data) . '</strong></td>
        </tr>
        <tr>
            <td style="color: #666; font-weight: bold;">🕐 Horário:</td>
            <td style="color: #333; font-size: 18px;"><strong>' . htmlspecialchars($hora) . '</strong></td>
        </tr>
        <tr>
            <td style="color: #666; font-weight: bold;">👤 Profissional:</td>
            <td style="color: #333;">' . htmlspecialchars($profissional) . '</td>
        </tr>
    </table>
</div>

<div style="margin: 20px 0;">
    <p style="color: #666; font-weight: bold; margin-bottom: 10px;">✂️ Serviços:</p>
    <ul style="color: #333; line-height: 1.8; margin: 0;">
        ' . $servicos_lista . '
    </ul>
</div>

' . $total_html . '

<p style="color: #333; font-size: 16px; margin-top: 30px;">
    Estamos ansiosos para atendê-lo(a)!<br>
    <strong>Equipe ' . htmlspecialchars($this->config['tipo_empresa'] ?? 'Salão') . '</strong>
</p>';

        return $this->getBaseTemplate('Agendamento Confirmado', $conteudo);
    }
}
