<?php
/**
 * Classe para geração de PDF de Fechamento de Caixa
 * Usa biblioteca FPDF para criar PDFs
 */

class FechamentoPDF {
    private $pdo;
    private $config;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->carregarConfiguracao();
    }

    private function carregarConfiguracao() {
        $stmt = $this->pdo->query("SELECT * FROM configuracoes WHERE id = 1");
        $this->config = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Gera PDF do fechamento de caixa
     */
    public function gerarPDF($fechamento_id) {
        // Buscar dados do fechamento
        $stmt = $this->pdo->prepare("
            SELECT
                f.*,
                u.nome as profissional_nome,
                admin.nome as admin_nome
            FROM fechamentos_caixa f
            JOIN usuarios u ON u.id = f.profissional_id
            LEFT JOIN usuarios admin ON admin.id = f.criado_por
            WHERE f.id = ?
        ");
        $stmt->execute([$fechamento_id]);
        $fechamento = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fechamento) {
            throw new Exception('Fechamento não encontrado');
        }

        // Buscar detalhes de comissões
        $stmt = $this->pdo->prepare("
            SELECT
                a.data_atendimento,
                a.cliente_nome,
                sr.nome_servico,
                sr.preco,
                c.servico as percentual_comissao,
                (sr.preco * c.servico / 100) as valor_comissao
            FROM atendimentos a
            JOIN servicos_realizados sr ON sr.atendimento_id = a.id
            LEFT JOIN comissoes c ON c.profissional_id = a.profissional_id
            WHERE a.profissional_id = ?
              AND a.data_atendimento BETWEEN ? AND ?
              AND a.status = 'concluido'
            ORDER BY a.data_atendimento
        ");
        $stmt->execute([
            $fechamento['profissional_id'],
            $fechamento['data_inicio'],
            $fechamento['data_fim']
        ]);
        $comissoes_detalhes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar gorjetas aprovadas
        $stmt = $this->pdo->prepare("
            SELECT
                data_gorjeta,
                valor,
                observacoes
            FROM gorjetas
            WHERE profissional_id = ?
              AND data_gorjeta BETWEEN ? AND ?
              AND status = 'aprovado'
            ORDER BY data_gorjeta
        ");
        $stmt->execute([
            $fechamento['profissional_id'],
            $fechamento['data_inicio'],
            $fechamento['data_fim']
        ]);
        $gorjetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar vales aprovados
        $stmt = $this->pdo->prepare("
            SELECT
                data_vale,
                valor,
                motivo
            FROM vales
            WHERE profissional_id = ?
              AND data_vale BETWEEN ? AND ?
              AND status = 'aprovado'
            ORDER BY data_vale
        ");
        $stmt->execute([
            $fechamento['profissional_id'],
            $fechamento['data_inicio'],
            $fechamento['data_fim']
        ]);
        $vales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Gerar HTML do PDF
        $html = $this->gerarHTML($fechamento, $comissoes_detalhes, $gorjetas, $vales);

        // Criar diretório se não existe
        $pdf_dir = __DIR__ . '/../assets/pdf/fechamentos';
        if (!is_dir($pdf_dir)) {
            mkdir($pdf_dir, 0755, true);
        }

        // Nome do arquivo
        $filename = 'fechamento_' . $fechamento['id'] . '_' . date('Y-m-d_His') . '.html';
        $filepath = $pdf_dir . '/' . $filename;

        // Salvar HTML
        file_put_contents($filepath, $html);

        // Atualizar caminho do PDF no banco
        $stmt = $this->pdo->prepare("UPDATE fechamentos_caixa SET pdf_path = ? WHERE id = ?");
        $stmt->execute([$filename, $fechamento_id]);

        return $filename;
    }

    private function gerarHTML($fechamento, $comissoes, $gorjetas, $vales) {
        $cor_primaria = $this->config['cor_primaria'] ?? '#667eea';
        $cor_secundaria = $this->config['cor_secundaria'] ?? '#764ba2';
        $logo = $this->config['logo'] ?? '';

        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fechamento de Caixa - <?php echo htmlspecialchars($fechamento['profissional_nome']); ?></title>
    <style>
        @media print {
            @page { margin: 1cm; }
            body { margin: 0; }
            .no-print { display: none !important; }
        }

        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid <?php echo $cor_primaria; ?>;
        }

        .logo {
            max-height: 80px;
            margin-bottom: 10px;
        }

        h1 {
            color: <?php echo $cor_primaria; ?>;
            margin: 10px 0;
        }

        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-box table {
            width: 100%;
        }

        .info-box td {
            padding: 5px;
        }

        .info-box strong {
            color: <?php echo $cor_primaria; ?>;
        }

        .section {
            margin-bottom: 30px;
        }

        .section-title {
            background: linear-gradient(135deg, <?php echo $cor_primaria; ?>, <?php echo $cor_secundaria; ?>);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: bold;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table.data-table th {
            background: <?php echo $cor_primaria; ?>;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 12px;
        }

        table.data-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
            font-size: 12px;
        }

        table.data-table tr:hover {
            background: #f8f9fa;
        }

        .total-box {
            background: <?php echo $cor_primaria; ?>;
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-top: 30px;
        }

        .total-box h2 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }

        .total-box .valor {
            font-size: 36px;
            font-weight: bold;
        }

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 11px;
        }

        .assinatura {
            margin-top: 60px;
            display: flex;
            justify-content: space-around;
        }

        .assinatura div {
            text-align: center;
        }

        .assinatura-linha {
            border-top: 1px solid #333;
            width: 200px;
            margin: 0 auto 5px;
        }

        .no-print {
            margin: 20px 0;
            text-align: center;
        }

        .btn {
            background: <?php echo $cor_primaria; ?>;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }

        .btn:hover {
            background: <?php echo $cor_secundaria; ?>;
        }
    </style>
</head>
<body>
    <!-- Botões de ação (não imprimem) -->
    <div class="no-print">
        <button class="btn" onclick="window.print()">
            🖨️ Imprimir / Salvar PDF
        </button>
        <button class="btn" onclick="window.close()">
            ✖️ Fechar
        </button>
    </div>

    <!-- Cabeçalho -->
    <div class="header">
        <?php if ($logo): ?>
            <img src="../../assets/img/<?php echo $logo; ?>" class="logo" alt="Logo">
        <?php endif; ?>
        <h1>COMPROVANTE DE FECHAMENTO DE CAIXA</h1>
        <p style="margin: 5px 0; color: #666;">Nº <?php echo str_pad($fechamento['id'], 6, '0', STR_PAD_LEFT); ?></p>
    </div>

    <!-- Informações do Fechamento -->
    <div class="info-box">
        <table>
            <tr>
                <td><strong>Profissional:</strong></td>
                <td><?php echo htmlspecialchars($fechamento['profissional_nome']); ?></td>
                <td><strong>Período:</strong></td>
                <td><?php echo date('d/m/Y', strtotime($fechamento['data_inicio'])); ?> a <?php echo date('d/m/Y', strtotime($fechamento['data_fim'])); ?></td>
            </tr>
            <tr>
                <td><strong>Data Fechamento:</strong></td>
                <td><?php echo date('d/m/Y H:i', strtotime($fechamento['created_at'])); ?></td>
                <td><strong>Fechado por:</strong></td>
                <td><?php echo htmlspecialchars($fechamento['admin_nome']); ?></td>
            </tr>
        </table>
    </div>

    <!-- Comissões Detalhadas -->
    <div class="section">
        <div class="section-title">💰 COMISSÕES (<?php echo count($comissoes); ?> atendimentos)</div>
        <?php if (empty($comissoes)): ?>
            <p style="text-align: center; color: #999;">Nenhuma comissão neste período</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Serviço</th>
                        <th>Valor</th>
                        <th>%</th>
                        <th>Comissão</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comissoes as $c): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($c['data_atendimento'])); ?></td>
                        <td><?php echo htmlspecialchars($c['cliente_nome']); ?></td>
                        <td><?php echo htmlspecialchars($c['nome_servico']); ?></td>
                        <td>R$ <?php echo number_format($c['preco'], 2, ',', '.'); ?></td>
                        <td><?php echo number_format($c['percentual_comissao'], 1); ?>%</td>
                        <td><strong>R$ <?php echo number_format($c['valor_comissao'], 2, ',', '.'); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="text-align: right; font-weight: bold;">
                Subtotal Comissões: R$ <?php echo number_format($fechamento['total_comissoes'], 2, ',', '.'); ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Gorjetas -->
    <div class="section">
        <div class="section-title">🎁 GORJETAS APROVADAS (<?php echo count($gorjetas); ?>)</div>
        <?php if (empty($gorjetas)): ?>
            <p style="text-align: center; color: #999;">Nenhuma gorjeta neste período</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Valor</th>
                        <th>Observações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gorjetas as $g): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($g['data_gorjeta'])); ?></td>
                        <td><strong>R$ <?php echo number_format($g['valor'], 2, ',', '.'); ?></strong></td>
                        <td><?php echo htmlspecialchars($g['observacoes'] ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="text-align: right; font-weight: bold;">
                Subtotal Gorjetas: R$ <?php echo number_format($fechamento['total_gorjetas'], 2, ',', '.'); ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Vales -->
    <div class="section">
        <div class="section-title">💸 VALES APROVADOS (<?php echo count($vales); ?>)</div>
        <?php if (empty($vales)): ?>
            <p style="text-align: center; color: #999;">Nenhum vale neste período</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Valor</th>
                        <th>Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vales as $v): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($v['data_vale'])); ?></td>
                        <td><strong>R$ <?php echo number_format($v['valor'], 2, ',', '.'); ?></strong></td>
                        <td><?php echo htmlspecialchars($v['motivo'] ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="text-align: right; font-weight: bold; color: #dc2626;">
                Subtotal Vales: - R$ <?php echo number_format($fechamento['total_vales'], 2, ',', '.'); ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Total Líquido -->
    <div class="total-box">
        <h2>TOTAL LÍQUIDO A RECEBER</h2>
        <div class="valor">R$ <?php echo number_format($fechamento['total_liquido'], 2, ',', '.'); ?></div>
        <p style="margin: 15px 0 0 0; font-size: 14px;">
            (Comissões: R$ <?php echo number_format($fechamento['total_comissoes'], 2, ',', '.'); ?>
            + Gorjetas: R$ <?php echo number_format($fechamento['total_gorjetas'], 2, ',', '.'); ?>
            - Vales: R$ <?php echo number_format($fechamento['total_vales'], 2, ',', '.'); ?>)
        </p>
    </div>

    <?php if ($fechamento['observacoes']): ?>
    <div class="section">
        <div class="section-title">📝 OBSERVAÇÕES</div>
        <p><?php echo nl2br(htmlspecialchars($fechamento['observacoes'])); ?></p>
    </div>
    <?php endif; ?>

    <!-- Assinaturas -->
    <div class="assinatura">
        <div>
            <div class="assinatura-linha"></div>
            <p><strong><?php echo htmlspecialchars($fechamento['profissional_nome']); ?></strong><br>
            <small>Profissional</small></p>
        </div>
        <div>
            <div class="assinatura-linha"></div>
            <p><strong><?php echo htmlspecialchars($fechamento['admin_nome']); ?></strong><br>
            <small>Responsável</small></p>
        </div>
    </div>

    <!-- Rodapé -->
    <div class="footer">
        <p>Documento gerado automaticamente pelo sistema em <?php echo date('d/m/Y \à\s H:i'); ?></p>
        <p>Este documento tem validade legal como comprovante de pagamento.</p>
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }
}
