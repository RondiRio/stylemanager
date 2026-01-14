<?php
// termos.php - Termos de Uso e Serviço
$titulo = "Termos de Uso";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $titulo; ?> - Sistema de Beleza</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <link href="assets/css/components.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card-glass">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="fas fa-file-contract fa-4x text-primary mb-3"></i>
                            <h1 class="display-6">Termos de Uso e Serviço</h1>
                            <p class="text-muted">Última atualização: <?php echo date('d/m/Y'); ?></p>
                        </div>

                        <hr class="my-4">

                        <h4>1. Aceitação dos Termos</h4>
                        <p>Ao acessar e usar este sistema de agendamento e gestão de salão de beleza, você concorda em cumprir e ficar vinculado aos seguintes termos e condições de uso.</p>

                        <h4>2. Descrição do Serviço</h4>
                        <p>Nosso sistema fornece uma plataforma para:</p>
                        <ul>
                            <li>Agendamento online de serviços de beleza</li>
                            <li>Gestão de profissionais e serviços</li>
                            <li>Controle de atendimentos e comissões</li>
                            <li>Comunicação entre clientes e profissionais</li>
                        </ul>

                        <h4>3. Cadastro e Conta de Usuário</h4>
                        <p><strong>3.1.</strong> Para utilizar os serviços, você deve criar uma conta fornecendo informações precisas e completas.</p>
                        <p><strong>3.2.</strong> Você é responsável por manter a confidencialidade de sua senha e conta.</p>
                        <p><strong>3.3.</strong> Você concorda em notificar imediatamente sobre qualquer uso não autorizado de sua conta.</p>

                        <h4>4. Uso Permitido</h4>
                        <p>Você concorda em usar o serviço apenas para fins legítimos e de acordo com estes Termos. É proibido:</p>
                        <ul>
                            <li>Usar o serviço para qualquer finalidade ilegal</li>
                            <li>Tentar obter acesso não autorizado ao sistema</li>
                            <li>Interferir ou interromper a integridade ou desempenho do serviço</li>
                            <li>Enviar spam ou conteúdo malicioso</li>
                            <li>Violar direitos de propriedade intelectual</li>
                        </ul>

                        <h4>5. Agendamentos</h4>
                        <p><strong>5.1.</strong> Os agendamentos estão sujeitos à disponibilidade.</p>
                        <p><strong>5.2.</strong> Cancelamentos devem ser feitos com antecedência mínima conforme política do estabelecimento.</p>
                        <p><strong>5.3.</strong> Cancelamentos tardios podem resultar em cobranças.</p>
                        <p><strong>5.4.</strong> O estabelecimento reserva-se o direito de cancelar ou reagendar compromissos em circunstâncias excepcionais.</p>

                        <h4>6. Pagamentos e Reembolsos</h4>
                        <p><strong>6.1.</strong> Os preços dos serviços são determinados pelo estabelecimento.</p>
                        <p><strong>6.2.</strong> Pagamentos são processados no momento do serviço ou conforme acordado.</p>
                        <p><strong>6.3.</strong> Reembolsos estão sujeitos à política do estabelecimento.</p>

                        <h4>7. Propriedade Intelectual</h4>
                        <p>Todo o conteúdo do sistema, incluindo textos, gráficos, logos, ícones, imagens e software, é propriedade do desenvolvedor ou de seus fornecedores de conteúdo e é protegido por leis de direitos autorais.</p>

                        <h4>8. Limitação de Responsabilidade</h4>
                        <p>O sistema é fornecido "como está" sem garantias de qualquer tipo. Não garantimos que o serviço será ininterrupto, seguro ou livre de erros.</p>

                        <h4>9. Modificações dos Termos</h4>
                        <p>Reservamo-nos o direito de modificar estes termos a qualquer momento. Alterações significativas serão notificadas aos usuários.</p>

                        <h4>10. Encerramento</h4>
                        <p>Podemos encerrar ou suspender sua conta imediatamente, sem aviso prévio, se você violar estes Termos.</p>

                        <h4>11. Lei Aplicável</h4>
                        <p>Estes Termos serão regidos e interpretados de acordo com as leis do Brasil.</p>

                        <h4>12. Contato</h4>
                        <p>Para questões sobre estes Termos, entre em contato com o estabelecimento através dos canais de atendimento disponibilizados.</p>

                        <hr class="my-4">

                        <div class="text-center">
                            <a href="javascript:history.back()" class="btn btn-primary me-2">
                                <i class="fas fa-arrow-left me-1"></i>Voltar
                            </a>
                            <a href="privacidade.php" class="btn btn-outline-secondary">
                                <i class="fas fa-shield-alt me-1"></i>Política de Privacidade
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
