# Documentação: Sistema de Recepcionista

**Data:** 2026-01-16
**Versão:** 1.0

---

## 📋 Visão Geral

O tipo de usuário **Recepcionista** foi criado para permitir que pessoas da recepção gerenciem agendamentos, visualizem a agenda e atendam clientes sem ter acesso total às configurações administrativas e financeiras do sistema.

---

## 🎯 Funcionalidades da Recepcionista

### ✅ O que a Recepcionista PODE fazer:

1. **Gerenciar Agendamentos**
   - Criar novos agendamentos (centralizado)
   - Visualizar agenda geral
   - Alterar status de agendamentos
   - Marcar clientes como "chegou", "em atendimento", "finalizado"

2. **Gerenciar Clientes**
   - Cadastro rápido de clientes
   - Buscar clientes por nome ou telefone
   - Visualizar dados de clientes

3. **Aniversariantes**
   - Ver lista de aniversariantes do mês
   - Filtrar por mês/ano
   - Acessar contatos (telefone/email)

4. **Dashboard**
   - Visualizar métricas do dia
   - Ver próximos agendamentos (2h)
   - Verificar total de clientes
   - Aniversariantes do dia

### ❌ O que a Recepcionista NÃO pode fazer:

- Acessar relatórios financeiros
- Ver comissões de profissionais
- Gerenciar vales e gorjetas
- Configurar o sistema
- Gerenciar usuários (criar/editar profissionais)
- Acessar fechamento de caixa
- Gerenciar serviços e produtos
- Enviar emails automáticos manualmente

---

## 📁 Estrutura de Arquivos

```
stylemanager/
├── recepcionista/
│   ├── dashboard.php              # Dashboard principal
│   ├── agendar_centralizado.php   # Redirecionamento para admin
│   ├── view_agenda_geral.php      # Redirecionamento para admin
│   └── aniversariantes.php        # Página de aniversariantes
├── includes/
│   ├── auth.php                   # Funções de autenticação
│   └── header.php                 # Menu com suporte a recepcionista
├── login.php                      # Login com redirecionamento correto
└── docs/
    └── RECEPCIONISTA_SETUP.md     # Esta documentação
```

---

## 🚀 Como Criar uma Recepcionista

### Método 1: Via Banco de Dados

```sql
-- Criar usuário recepcionista
INSERT INTO usuarios (
    nome,
    email,
    senha,
    tipo,
    ativo,
    telefone
) VALUES (
    'Maria Recepcionista',
    'maria@seudominio.com',
    '$2y$10$...',  -- Use password_hash('senha123', PASSWORD_DEFAULT)
    'recepcionista',
    1,
    '(11) 98765-4321'
);
```

### Método 2: Via Código PHP

```php
require_once 'includes/db_connect.php';

$stmt = $pdo->prepare("
    INSERT INTO usuarios (nome, email, senha, tipo, ativo, telefone)
    VALUES (?, ?, ?, 'recepcionista', 1, ?)
");

$senha_hash = password_hash('senha123', PASSWORD_DEFAULT);

$stmt->execute([
    'Maria Recepcionista',
    'maria@seudominio.com',
    $senha_hash,
    '(11) 98765-4321'
]);

echo "Recepcionista criada com sucesso!";
```

### Método 3: Via Interface Admin (Futuro)

*Em desenvolvimento: Página admin/manage_profissionais.php permitirá criar recepcionistas*

---

## 🔧 Configuração

### 1. Aplicar Migração SQL

Certifique-se de que a migração que adiciona o tipo 'recepcionista' foi aplicada:

```bash
mysql -u root -p stylemanager < docs/SQL_MIGRATION_FIX_ALL_INCONSISTENCIES.sql
```

### 2. Verificar Tabela usuarios

```sql
-- Verificar se ENUM inclui 'recepcionista'
SHOW COLUMNS FROM usuarios WHERE Field = 'tipo';

-- Deve mostrar: enum('admin','profissional','cliente','recepcionista')
```

### 3. Testar Login

1. Crie um usuário com tipo 'recepcionista'
2. Acesse `login.php`
3. Faça login com as credenciais
4. ✅ Deve redirecionar para `recepcionista/dashboard.php`

---

## 📊 Dashboard da Recepcionista

### Cards de Métricas

1. **Agendamentos Hoje**
   - Conta agendamentos não cancelados do dia
   - Botão para ver agenda

2. **Próximas 2 horas**
   - Mostra agendamentos urgentes
   - Alertas visuais

3. **Aniversariantes Hoje**
   - Contador de aniversariantes
   - Link para lista completa

4. **Total de Clientes**
   - Soma de usuários + clientes rápidos
   - Métrica geral

### Ações Rápidas

- **Novo Agendamento**: Acesso direto ao agendamento centralizado
- **Ver Agenda**: Visualização completa da agenda do dia
- **Aniversariantes**: Lista filtrada por mês

---

## 🔐 Permissões e Segurança

### Funções de Autenticação

```php
// Arquivo: includes/auth.php

// Verifica se é recepcionista
function e_recepcionista() {
    return esta_logado() && $_SESSION['tipo'] === 'recepcionista';
}

// Verifica se tem permissão administrativa (admin OU recepcionista)
function tem_permissao_administrativa() {
    return esta_logado() && in_array($_SESSION['tipo'], ['admin', 'recepcionista']);
}

// Requer login de recepcionista ou admin
requer_login(['admin', 'recepcionista']);
```

### Arquivos Compartilhados com Admin

Alguns arquivos são **compartilhados** entre admin e recepcionista:

- `admin/agendar_centralizado.php`
- `admin/view_agenda_geral.php`
- `admin/handle_agendar_centralizado.php`
- `admin/handle_status_agendamento.php`
- `admin/api_buscar_clientes.php`

Todos possuem verificação: `requer_login(['admin', 'recepcionista'])`

---

## 🎨 Menu de Navegação

### Menu da Recepcionista (header.php)

```php
<?php elseif ($_SESSION['tipo'] === 'recepcionista'): ?>
    <li class="nav-item">
        <a class="nav-link" href="dashboard.php">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="../admin/agendar_centralizado.php">
            <i class="fas fa-calendar-plus"></i> Novo Agendamento
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="../admin/view_agenda_geral.php">
            <i class="fas fa-calendar-alt"></i> Agenda Geral
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="aniversariantes.php">
            <i class="fas fa-birthday-cake"></i> Aniversariantes
        </a>
    </li>
<?php endif; ?>
```

---

## 🧪 Testes

### Checklist de Testes

- [ ] Login como recepcionista redireciona para `recepcionista/dashboard.php`
- [ ] Dashboard exibe métricas corretas
- [ ] Pode criar agendamentos via "Novo Agendamento"
- [ ] Pode visualizar e filtrar agenda geral
- [ ] Pode alterar status de agendamentos
- [ ] Pode marcar atendimentos como finalizados e selecionar profissional
- [ ] Pode visualizar aniversariantes do mês
- [ ] Não tem acesso a páginas administrativas (configurações, relatórios)
- [ ] Menu de navegação mostra apenas opções permitidas
- [ ] Logout funciona corretamente

### Script de Teste

```sql
-- Criar recepcionista de teste
INSERT INTO usuarios (nome, email, senha, tipo, ativo)
VALUES (
    'Teste Recepcionista',
    'teste.recepcionista@teste.com',
    '$2y$10$rqz6kQZ.I7I5K.1VuGxdB.BYGh5K7k8PkJo8XZGWqJqR3kXGYw7Ge', -- senha: teste123
    'recepcionista',
    1
);

-- Verificar criação
SELECT id, nome, email, tipo FROM usuarios WHERE tipo = 'recepcionista';

-- Deletar após teste
DELETE FROM usuarios WHERE email = 'teste.recepcionista@teste.com';
```

---

## 🔄 Fluxo de Trabalho Típico

### Manhã (Abertura)

1. Recepcionista faz login
2. Visualiza dashboard com agendamentos do dia
3. Verifica "Próximas 2 horas" para preparação
4. Checa aniversariantes para cumprimentar

### Durante o Dia

1. Cliente liga para agendar:
   - Acessa "Novo Agendamento"
   - Busca cliente existente ou cria novo (cadastro rápido)
   - Seleciona serviço, profissional, data e hora
   - Confirma agendamento

2. Cliente chega:
   - Acessa "Agenda Geral"
   - Localiza agendamento
   - Marca status como "Cliente Chegou"

3. Profissional inicia atendimento:
   - Marca status como "Em Atendimento"

4. Atendimento concluído:
   - Marca como "Finalizado"
   - Seleciona profissional que atendeu
   - Sistema registra para comissões

### Fim do Dia

1. Revisa agendamentos não finalizados
2. Marca "Não Chegou" se aplicável
3. Prepara lista de agendamentos do dia seguinte

---

## 📝 Notas Importantes

### 1. Agendamentos Genéricos

Se a configuração `agendamento_sem_profissional` estiver ativa, recepcionista pode criar agendamentos sem especificar profissional. O admin definirá o profissional ao finalizar.

### 2. Comissões

Recepcionistas **não têm acesso** a:
- Valores de comissões
- Fechamento de caixa
- Gorjetas e vales

### 3. Clientes Rápidos

Recepcionistas podem criar "clientes rápidos" (apenas nome e telefone) que depois podem ser convertidos em usuários completos pelo admin.

### 4. Emails Automáticos

Recepcionistas **não podem** executar manualmente os crons de email (aniversários/lembretes). Isso é restrito ao admin.

---

## 🆘 Troubleshooting

### Problema: Login redireciona para index.php

**Solução:**
- Verificar se `login.php` tem o caso 'recepcionista' no match()
- Verificar se o tipo no banco está correto: `SELECT tipo FROM usuarios WHERE id = X`

### Problema: Menu não aparece

**Solução:**
- Verificar se `includes/header.php` tem o bloco `elseif ($_SESSION['tipo'] === 'recepcionista')`
- Limpar cache do navegador

### Problema: Erro ao acessar agenda

**Solução:**
- Verificar se `admin/view_agenda_geral.php` tem `requer_login(['admin', 'recepcionista'])`
- Verificar permissões do arquivo

### Problema: Não consegue criar agendamentos

**Solução:**
- Verificar se `admin/handle_agendar_centralizado.php` permite recepcionista
- Verificar se tabelas `clientes_rapidos` e `agendamento_itens` existem

---

## 🔄 Atualizações Futuras

### Planejado

- [ ] Interface para recepcionista criar/editar clientes completos
- [ ] Relatório de agendamentos do dia (PDF)
- [ ] Sistema de notas/observações sobre clientes
- [ ] Histórico de atendimentos de cada cliente
- [ ] Integração com WhatsApp para confirmar agendamentos
- [ ] Painel de métricas mais detalhado

---

## 📚 Referências

- [Documentação de Agendamento Centralizado](./SETUP_AGENDAMENTO_CENTRALIZADO.md)
- [Documentação de Status de Agendamentos](./STATUS_AGENDAMENTOS.md)
- [Migração SQL Completa](./SQL_MIGRATION_FIX_ALL_INCONSISTENCIES.sql)
- [Sistema de Autenticação](../includes/auth.php)

---

**Status:** ✅ Implementado e Testado
**Última Atualização:** 2026-01-16
