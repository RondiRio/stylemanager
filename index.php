<?php
// index.php - Página Pública Inicial
require_once 'includes/db_connect.php';
require_once 'includes/theme.php';
require_once 'includes/utils.php';

// Carregar configurações (inclui logo e tipo de empresa)
$config = $pdo->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();
$tema = carregar_tema();

// Serviços em destaque (os 6 mais agendados ou aleatórios)
$servicos_destaque = $pdo->query("
    SELECT s.id, s.nome, s.preco, s.duracao_min, COUNT(ai.id) AS total
    FROM servicos s
    LEFT JOIN agendamento_itens ai ON ai.servico_id = s.id
    WHERE s.ativo = 1
    GROUP BY s.id
    ORDER BY total DESC, s.nome
    LIMIT 6
")->fetchAll();

// Profissionais em destaque (com avaliações > 4.5 ou aleatórios)
$profissionais_destaque = $pdo->query("
    SELECT u.id, u.nome, u.avatar,
           COALESCE(AVG(r.nota), 0) AS media_nota,
           COUNT(r.id) AS total_avaliacoes
    FROM usuarios u
    LEFT JOIN recomendacoes r ON r.profissional_id = u.id AND r.aprovado = 1
    WHERE u.tipo = 'profissional' AND u.ativo = 1
    GROUP BY u.id
    HAVING media_nota >= 4.5 OR total_avaliacoes = 0
    ORDER BY media_nota DESC, u.nome
    LIMIT 4
")->fetchAll();

// Avaliações aprovadas (mais recentes)
$avaliacoes = $pdo->query("
    SELECT r.nota, r.comentario, r.data_avaliacao,
           c.nome AS cliente_nome,
           p.nome AS profissional_nome,
           s.nome AS servico_nome
    FROM recomendacoes r
    JOIN usuarios c ON c.id = r.cliente_id
    JOIN usuarios p ON p.id = r.profissional_id
    JOIN servicos s ON s.id = r.servico_id
    WHERE r.aprovado = 1
    ORDER BY r.data_avaliacao DESC
    LIMIT 5
")->fetchAll();
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $config['nome_salao'] ?? 'Barbearia JB'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <?php aplicar_tema_css(); ?>
    <style>
        .hero {
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.6)), url('assets/img/hero-bg.jpg') center/cover no-repeat;
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        .servico-card {
            transition: all 0.3s;
            border: 1px solid var(--cor-primaria);
        }
        .servico-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .profissional-card img {
            width: 100px; height: 100px; object-fit: cover; border: 4px solid var(--cor-secundaria);
        }
        .estrelas { color: #ffc107; font-size: 1.2rem; }
        .avaliacao-card {
            background: #f8f9fa;
            border-left: 4px solid var(--cor-secundaria);
        }
    </style>
</head>
<body>

<!-- Navbar Pública -->
<nav class="navbar navbar-expand-lg navbar-dark" style="background: var(--cor-primaria);">
    <div class="container-fluid">
        <?php if ($config['logo'] ?? false): ?>
        <a class="navbar-brand" href="index.php">
            <img src="assets/img/<?php echo $config['logo']; ?>" alt="Logo" height="40">
        </a>
        <?php else: ?>
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-cut"></i> <?php echo $config['nome_salao'] ?? 'Barbearia JB'; ?>
        </a>
        <?php endif; ?>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarPublic">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarPublic">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#servicos">Serviços</a></li>
                <li class="nav-item"><a class="nav-link" href="#profissionais">Equipe</a></li>
                <li class="nav-item"><a class="nav-link" href="#avaliacoes">Avaliações</a></li>
                <li class="nav-item"><a class="nav-link btn btn-outline-light ms-2 px-4" href="login.php">Entrar</a></li>
                <li class="nav-item"><a class="nav-link btn btn-outline-light ms-2 px-4" href="register.php">Cadastrar</a></li>
                <!--<li class="nav-item"><a class="nav-link btn btn-light text-dark ms-2 px-4" href="cliente/agendar.php">Agendar Agora</a></li>-->
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1 class="display-4 fw-bold">Barbearia JB - Sempre o melhor para você!</h1>
        <p class="lead mb-4">Venha nos visitar para um corte perfeito. Profissionais qualificados e ambiente acolhedor.</p>
        <a href="cliente/agendar.php" class="btn btn-lg btn-light fw-bold">
            <i class="fas fa-calendar-check"></i> Entre em Contato
        </a>
    </div>
</section>

<!-- Serviços em Destaque -->
<section id="servicos" class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Nossos Serviços Mais Procurados</h2>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($servicos_destaque as $s): ?>
            <div class="col">
                <div class="card servico-card h-100 text-center">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($s['nome']); ?></h5>
                        <p class="card-text text-muted"><?php echo $s['duracao_min']; ?> minutos</p>
                        <p class="card-text fw-bold text-primary"><?php echo formatar_moeda($s['preco']); ?></p>
                        <!--<a href="cliente/agendar.php#servico-<?php echo $s['id']; ?>" class="btn btn-outline-primary mt-auto">Agendar</a>-->
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="cliente/agendar.php" class="btn btn-primary">Ver Todos os Serviços</a>
        </div>
    </div>
</section>

<!-- Equipe -->
<section id="profissionais" class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Nossa Equipe de Excelência</h2>
        <div class="row row-cols-1 row-cols-md-4 g-4">
            <?php foreach ($profissionais_destaque as $p): ?>
            <div class="col">
                <div class="card profissional-card text-center h-100">
                    <div class="card-body">
                        <?php if ($p['foto'] ?? false): ?>
                        <img src="assets/img/profissionais/<?php echo $p['foto']; ?>" class="rounded-circle mb-3" alt="<?php echo htmlspecialchars($p['nome']); ?>">
                        <?php else: ?>
                        <div class="bg-secondary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center text-white" style="width:100px;height:100px;">
                            <i class="fas fa-user fa-2x"></i>
                        </div>
                        <?php endif; ?>
                        <h5 class="card-title"><?php echo htmlspecialchars($p['nome']); ?></h5>
                        <p class="estrelas"><?php echo str_repeat('★', round($p['media_nota'])); ?> <?php echo number_format($p['media_nota'], 1); ?></p>
                        <small class="text-muted"><?php echo $p['total_avaliacoes']; ?> avaliações</small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Avaliações -->
<section id="avaliacoes" class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">O que nossos clientes dizem</h2>
        <div class="row g-4">
            <?php foreach ($avaliacoes as $a): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card avaliacao-card h-100">
                    <div class="card-body">
                        <p class="estrelas mb-2"><?php echo str_repeat('★', $a['nota']); ?></p>
                        <p class="card-text">"<?php echo htmlspecialchars($a['comentario']); ?>"</p>
                        <footer class="text-muted small">
                            — <strong><?php echo htmlspecialchars($a['cliente_nome']); ?></strong><br>
                            sobre <em><?php echo htmlspecialchars($a['servico_nome']); ?></em> com <?php echo htmlspecialchars($a['profissional_nome']); ?>
                            <br><small><?php echo formatar_data(substr($a['data_avaliacao'], 0, 10)); ?></small>
                        </footer>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <p>&copy; <?php echo date('Y'); ?> <?php echo $config['nome_salao'] ?? 'Beleza & Estética'; ?>. Todos os direitos reservados.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                <a href="#" class="text-white me-3"><i class="fab fa-facebook"></i></a>
                <a href="#" class="text-white"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>