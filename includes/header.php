<?php require_once 'auth.php'; require_once 'theme.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="description" content="Sistema completo para gestão de salão de beleza">
    <title><?php echo $titulo ?? 'Sistema de Beleza'; ?></title>
    
    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6.5.0 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS Customizado -->
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="../assets/css/components.css" rel="stylesheet">
    <link href="../assets/css/animations.css" rel="stylesheet">
    <link href="../assets/css/mobile-responsive.css" rel="stylesheet">

    <!-- Tema Dinâmico -->
    <?php aplicar_tema_css(); ?>
    
    <style>
        /* Estilos específicos do header */
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--cor-fundo);
        }
        .logoMobile {
            max-width: 100%;
            height: 40px;
        }
        
        @media (max-width: 576px) {
            .navbar-brand img {
            height: 32px !important;
            }
            
            .navbar-brand span {
            display: none;
            }
        }
        
        @media (max-width: 768px) {
            .navbar-brand {
            font-size: 1.25rem;
            }
            
            .navbar-brand img {
            height: 35px !important;
            }
        }
        
        @media (max-width: 992px) {
            .navbar-brand img {
            height: 40px !important;
            }
        }
        
        .navbar-custom {
            background: linear-gradient(135deg, var(--cor-primaria), var(--cor-secundaria)) !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1030;
            backdrop-filter: blur(10px);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        
        .navbar-brand:hover {
            transform: scale(1.05);
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white !important;
            transform: translateY(-2px);
        }
        
        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white !important;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            border-radius: 12px;
            padding: 0.5rem;
            animation: fadeInDown 0.3s ease;
        }
        
        .dropdown-item {
            border-radius: 8px;
            padding: 0.7rem 1rem;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background: var(--cor-secundaria);
            color: white;
            transform: translateX(5px);
        }
        
        .navbar-text {
            color: rgba(255, 255, 255, 0.95) !important;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .avatar-nav {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: 2px solid white;
            object-fit: cover;
            transition: all 0.3s ease;
        }
        
        .avatar-nav:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .container-main {
            flex: 1;
            padding: 2rem 0;
        }
        
        /* Flash Messages Animadas */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            animation: slideInDown 0.4s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .alert i {
            font-size: 1.5rem;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        
        .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }
        
        .btn-close:hover {
            opacity: 1;
        }
        
        /* Badge de notificações */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            animation: pulseGlow 2s infinite;
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        .loader {
            width: 50px;
            height: 50px;
            border: 4px solid var(--cor-primaria);
            border-top-color: transparent;
            border-radius: 50%;
            animation: rotate 1s linear infinite;
        }
    </style>
</head>
<body>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loader"></div>
</div>

<?php if (esta_logado()): ?>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container-fluid">
        <!-- Logo/Brand -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
            <?php 
            $config_logo = $pdo->query("SELECT logo FROM configuracoes WHERE id = 1")->fetch();
            if (!empty($config_logo['logo'])): 
            ?>
                <img src="../assets/img/<?php echo $config_logo['logo']; ?>" alt="Logo" height="40" class="animate-fade-in logoMobile">
            <?php else: ?>
                <i class="fas fa-cut"></i>
            <?php endif; ?>
            <span></span>
        </a>
        
        <!-- Toggle para mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if ($_SESSION['tipo'] === 'cliente'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="fas fa-home"></i> Feed
                        </a>
                    </li>
                    <?php
                    // Verificar se agendamento está ativo para mostrar links de agendamento
                    $config_cliente_agend = $pdo->query("SELECT agendamento_ativo FROM configuracoes WHERE id = 1")->fetch();
                    if ($config_cliente_agend && $config_cliente_agend['agendamento_ativo']):
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'agendar.php' ? 'active' : ''; ?>" href="agendar.php">
                            <i class="fas fa-calendar-plus"></i> Agendar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'view_agendamentos.php' ? 'active' : ''; ?>" href="view_agendamentos.php">
                            <i class="fas fa-list"></i> Meus Agendamentos
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'configuracoes.php' ? 'active' : ''; ?>" href="configuracoes.php">
                            <i class="fas fa-user-cog"></i> Configurações
                        </a>
                    </li>

                <?php elseif ($_SESSION['tipo'] === 'profissional'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <?php
                    // Verificar se profissional pode ver sua própria agenda
                    $config_prof = $pdo->query("SELECT profissional_ve_propria_agenda FROM configuracoes WHERE id = 1")->fetch();
                    if ($config_prof && ($config_prof['profissional_ve_propria_agenda'] ?? 0)):
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'view_minha_agenda.php' ? 'active' : ''; ?>" href="view_minha_agenda.php">
                            <i class="fas fa-calendar-alt"></i> Minha Agenda
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="feed_profissional.php">
                            <i class="fas fa-images"></i> Feed
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'configuracoes.php' ? 'active' : ''; ?>" href="configuracoes.php">
                            <i class="fas fa-user-cog"></i> Configurações
                        </a>
                    </li>

                <?php elseif ($_SESSION['tipo'] === 'admin'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> Gerenciar
                        </a>
                        <ul class="dropdown-menu animate-fade-in-up">
                            <li><a class="dropdown-item" href="manage_services.php">
                                <i class="fas fa-scissors me-2"></i>Serviços
                            </a></li>
                            <li><a class="dropdown-item" href="manage_profissionais.php">
                                <i class="fas fa-users me-2"></i>Profissionais
                            </a></li>
                            <li><a class="dropdown-item" href="manage_products.php">
                                <i class="fas fa-shopping-bag me-2"></i>Produtos
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="manage_recommendations.php">
                                <i class="fas fa-star me-2"></i>Avaliações
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'feed.php' ? 'active' : ''; ?>" href="feed.php">
                            <i class="fas fa-images"></i> Feed Social
                        </a>
                    </li>
                    <?php
                    // Verificar se agendamento está ativo para mostrar agenda
                    $config_agend = $pdo->query("SELECT agendamento_ativo, agenda_centralizada_ativa, lembrar_aniversarios FROM configuracoes WHERE id = 1")->fetch();
                    if ($config_agend && $config_agend['agendamento_ativo']):
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-calendar-week"></i> Agenda
                        </a>
                        <ul class="dropdown-menu animate-fade-in-up">
                            <li><a class="dropdown-item" href="view_agenda_geral.php">
                                <i class="fas fa-calendar-alt me-2"></i>Agenda Geral
                            </a></li>
                            <?php if ($config_agend['agenda_centralizada_ativa']): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="agendar_centralizado.php">
                                <i class="fas fa-calendar-plus me-2"></i>Novo Agendamento
                            </a></li>
                            <?php endif; ?>
                            <?php if ($config_agend['lembrar_aniversarios'] ?? 1): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="aniversariantes.php">
                                <i class="fas fa-birthday-cake me-2"></i>Aniversariantes
                            </a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="view_reports.php">
                            <i class="fas fa-chart-line"></i> Relatórios
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-money-bill-wave"></i> Financeiro
                        </a>
                        <ul class="dropdown-menu animate-fade-in-up">
                            <li><a class="dropdown-item" href="fechamento_caixa.php">
                                <i class="fas fa-cash-register me-2"></i>Fechamento de Caixa
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="aprovar_gorjetas.php">
                                <i class="fas fa-coins me-2"></i>Aprovar Gorjetas
                            </a></li>
                            <li><a class="dropdown-item" href="aprovar_vales.php">
                                <i class="fas fa-hand-holding-usd me-2"></i>Aprovar Vales
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="configuracoes.php">
                            <i class="fas fa-sliders-h"></i> Configurações
                        </a>
                    </li>
                    <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
        <i class="fas fa-user-circle"></i> Admin
    </a>
    <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
        <li><a class="dropdown-item" href="configuracoes.php">Configurações</a></li>
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item text-primary" href="alterar_senha.php">
                <i class="fas fa-key me-2"></i>Alterar Senha
            </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="../logout.php">Sair</a></li>
    </ul>
                </li>

                <?php elseif ($_SESSION['tipo'] === 'recepcionista'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <?php
                    // Verificar se agendamento está ativo para mostrar agenda
                    $config_recep = $pdo->query("SELECT agendamento_ativo, agenda_centralizada_ativa, lembrar_aniversarios FROM configuracoes WHERE id = 1")->fetch();
                    if ($config_recep && $config_recep['agendamento_ativo']):
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-calendar-week"></i> Agenda
                        </a>
                        <ul class="dropdown-menu animate-fade-in-up">
                            <li><a class="dropdown-item" href="view_agenda_geral.php">
                                <i class="fas fa-calendar-alt me-2"></i>Agenda Geral
                            </a></li>
                            <?php if ($config_recep['agenda_centralizada_ativa']): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="agendar_centralizado.php">
                                <i class="fas fa-calendar-plus me-2"></i>Novo Agendamento
                            </a></li>
                            <?php endif; ?>
                            <?php if ($config_recep['lembrar_aniversarios'] ?? 1): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="aniversariantes.php">
                                <i class="fas fa-birthday-cake me-2"></i>Aniversariantes
                            </a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo explode(' ', $_SESSION['nome'])[0]; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                            <li><a class="dropdown-item" href="configuracoes.php">Meu Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-primary" href="alterar_senha.php">
                                    <i class="fas fa-key me-2"></i>Alterar Senha
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php">Sair</a></li>
                        </ul>
                    </li>

                <?php endif; ?>
            </ul>
            <!-- No menu dropdown do admin, adicione: -->

            <!-- User Menu -->
            <div class="navbar-text me-3 d-flex align-items-center gap-2">
                <img src="../assets/img/avatars/<?php echo $_SESSION['avatar'] ?? 'default.png'; ?>" 
                     alt="Avatar" 
                     class="avatar-nav">
                <span class="d-none d-md-inline">
                    Olá, <strong><?php echo explode(' ', $_SESSION['nome'])[0]; ?></strong>
                </span>
            </div>
            
            <a href="../logout.php" class="btn btn-salao-outline btn-sm">
                <i class="fas fa-sign-out-alt"></i>
                <span class="d-none d-md-inline">Sair</span>
            </a>
        </div>
    </div>
</nav>
<?php endif; ?>

<!-- Container Principal -->
<div class="container container-main animate-fade-in">
    
    <?php if (isset($_SESSION['flash'])): ?>
        <?php 
        $tipo_map = [
            'success' => ['icone' => 'check-circle', 'class' => 'alert-success'],
            'danger' => ['icone' => 'exclamation-circle', 'class' => 'alert-danger'],
            'warning' => ['icone' => 'exclamation-triangle', 'class' => 'alert-warning'],
            'info' => ['icone' => 'info-circle', 'class' => 'alert-info']
        ];
        $flash_tipo = $_SESSION['flash']['tipo'] ?? 'info';
        $flash_config = $tipo_map[$flash_tipo] ?? $tipo_map['info'];
        ?>
        <div class="alert <?php echo $flash_config['class']; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $flash_config['icone']; ?>"></i>
            <div>
                <?php echo $_SESSION['flash']['msg']; ?>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>