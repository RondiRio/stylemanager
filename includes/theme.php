<?php
// includes/theme.php
require_once 'db_connect.php';

function carregar_tema() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM configuracoes WHERE id = 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    // Cores padrão por tipo (fallback)
    $padroes = [
        'barbearia' => ['primaria' => '#1a1a1a', 'secundaria' => '#d4af37', 'fundo' => '#f5f5f5'],
        'salao'     => ['primaria' => '#6d4c41', 'secundaria' => '#f06292', 'fundo' => '#fff8f6'],
        'manicure'  => ['primaria' => '#e91e63', 'secundaria' => '#4a148c', 'fundo' => '#fce4ec'],
        'estetica'  => ['primaria' => '#1de9b6', 'secundaria' => '#004d40', 'fundo' => '#e0f2f1']
    ];

    $tipo = $config['tipo_empresa'] ?? 'barbearia';
    $padrao = $padroes[$tipo];

    return [
        'primaria'   => $config['cor_primaria'] ?? $padrao['primaria'],
        'secundaria' => $config['cor_secundaria'] ?? $padrao['secundaria'],
        'fundo'      => $config['cor_fundo'] ?? $padrao['fundo'],
        'tipo'       => $tipo
    ];
}

// Aplicar no <head>
function aplicar_tema_css() {
    $tema = carregar_tema();
    echo "<style>
        :root {
            --cor-primaria: {$tema['primaria']};
            --cor-secundaria: {$tema['secundaria']};
            --cor-fundo: {$tema['fundo']};
        }
        body { background-color: var(--cor-fundo); }
        .btn-primary, .navbar, .card-header { background-color: var(--cor-primaria); border-color: var(--cor-primaria); }
        .btn-primary:hover { background-color: var(--cor-secundaria); border-color: var(--cor-secundaria); }
        .text-primary { color: var(--cor-primaria) !important; }
        .bg-primary { background-color: var(--cor-primaria) !important; }
        .badge-success { background-color: var(--cor-secundaria); }
    </style>";
}
?>