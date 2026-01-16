# Configuração de Emails Automáticos

**Data:** 2026-01-16
**Versão:** 1.0

Este guia explica como configurar e usar o sistema de emails automáticos para aniversários e lembretes de agendamento.

---

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Pré-requisitos](#pré-requisitos)
3. [Instalação](#instalação)
4. [Configuração do SMTP](#configuração-do-smtp)
5. [Cron Jobs](#cron-jobs)
6. [Testes](#testes)
7. [Troubleshooting](#troubleshooting)

---

## 🎯 Visão Geral

O sistema de emails automáticos oferece:

### 1. Emails de Aniversário 🎂
- Envia emails automáticos para clientes aniversariantes
- Execução diária (sugestão: 08:00)
- Pode ser ativado/desativado nas configurações
- Templates personalizados com cores da marca

### 2. Lembretes de Agendamento 📅
- Envia lembretes 24h antes dos agendamentos
- Execução diária (sugestão: 18:00)
- Inclui detalhes do agendamento (data, hora, profissional, serviços)
- Sempre ativo (não precisa configurar)

---

## ✅ Pré-requisitos

### 1. Banco de Dados
- MySQL/MariaDB configurado
- Acesso para criar tabelas

### 2. Servidor de Email (SMTP)
Você precisa de um dos seguintes:

- **Gmail** (recomendado para testes)
  - Requer "Senha de App" (não use sua senha pessoal)
  - [Como criar senha de app](https://support.google.com/accounts/answer/185833)

- **Outlook/Hotmail**
  - Servidor: smtp.office365.com
  - Porta: 587 (TLS)

- **SendGrid/Mailgun/Amazon SES**
  - Para envios em produção (maior limite)

### 3. PHP
- Versão 7.4 ou superior
- Extensões: `mbstring`, `openssl`
- PHPMailer (já incluído no projeto)

### 4. Acesso ao Servidor
- SSH ou painel de controle com Cron Jobs
- Permissão para executar scripts PHP via CLI

---

## 📦 Instalação

### Passo 1: Aplicar Migração SQL

Execute o script de migração para criar as tabelas necessárias:

```bash
mysql -u root -p stylemanager < docs/SQL_MIGRATION_EMAILS_AUTOMATICOS.sql
```

**OU** via phpMyAdmin:
1. Acesse phpMyAdmin
2. Selecione o banco `stylemanager`
3. Vá em "SQL"
4. Cole o conteúdo de `SQL_MIGRATION_EMAILS_AUTOMATICOS.sql`
5. Clique em "Executar"

**O que isso cria:**
- ✅ Tabela `logs_email` (histórico de envios)
- ✅ Campos em `configuracoes_email`
- ✅ Campo `lembrar_aniversarios` em `configuracoes`
- ✅ Índices para performance

### Passo 2: Verificar Estrutura de Arquivos

Certifique-se de que os seguintes arquivos existem:

```
stylemanager/
├── cron/
│   ├── cron_aniversarios.php
│   └── cron_lembretes_agendamento.php
├── includes/
│   ├── email_sender.php
│   └── EmailTemplates.php
├── admin/
│   ├── configuracoes_email.php
│   └── gerenciar_emails_automaticos.php
└── logs/
    └── (será criado automaticamente)
```

### Passo 3: Configurar Permissões

```bash
# Dar permissão de escrita na pasta logs
chmod 755 logs/
chmod 644 logs/*.log

# Dar permissão de execução nos crons
chmod +x cron/cron_aniversarios.php
chmod +x cron/cron_lembretes_agendamento.php
```

---

## 🔧 Configuração do SMTP

### Opção 1: Via Interface Web (Recomendado)

1. Acesse: `http://seusite.com/admin/configuracoes_email.php`
2. Preencha os dados do servidor SMTP:
   - **Provedor Comum:** Selecione "Gmail", "Outlook", etc. (preenche automaticamente)
   - **Servidor SMTP:** smtp.gmail.com
   - **Porta:** 587
   - **Segurança:** TLS
   - **Usuário:** seu-email@gmail.com
   - **Senha:** Sua senha de app (Gmail) ou senha normal
3. Marque "Ativar Envio de E-mails"
4. Clique em "Salvar Configurações"
5. Use o botão "Enviar E-mail de Teste" para verificar

### Opção 2: Diretamente no Banco de Dados

```sql
INSERT INTO configuracoes_email (
    smtp_host,
    smtp_porta,
    smtp_usuario,
    smtp_senha,
    smtp_seguranca,
    smtp_remetente,
    smtp_nome_remetente,
    smtp_ativo
) VALUES (
    'smtp.gmail.com',
    587,
    'seu-email@gmail.com',
    'sua-senha-de-app',
    'tls',
    'noreply@barbearia.com',
    'Barbearia Premium',
    1
);
```

### Exemplo de Configuração Gmail

1. Acesse: https://myaccount.google.com/security
2. Ative "Verificação em duas etapas"
3. Vá em "Senhas de app"
4. Selecione "E-mail" e "Outro (nome personalizado)"
5. Digite "Sistema Barbearia"
6. Copie a senha gerada (16 caracteres)
7. Use essa senha no campo "Senha" das configurações

---

## ⏰ Cron Jobs

### O que são Cron Jobs?

Cron jobs são tarefas agendadas que executam automaticamente em horários específicos.

### Configurar via Terminal (Linux)

1. Abra o editor de cron:
```bash
crontab -e
```

2. Adicione as seguintes linhas:
```bash
# Enviar emails de aniversário diariamente às 08:00
0 8 * * * php /caminho/completo/para/cron/cron_aniversarios.php >> /caminho/para/logs/cron.log 2>&1

# Enviar lembretes de agendamento diariamente às 18:00
0 18 * * * php /caminho/completo/para/cron/cron_lembretes_agendamento.php >> /caminho/para/logs/cron.log 2>&1
```

3. Salve e feche (Ctrl+X, Y, Enter)

### Configurar via cPanel

1. Acesse o cPanel
2. Vá em "Cron Jobs"
3. Adicione um novo cron job:

**Aniversários:**
- **Minuto:** 0
- **Hora:** 8
- **Dia:** *
- **Mês:** *
- **Dia da Semana:** *
- **Comando:** `php /home/usuario/public_html/cron/cron_aniversarios.php`

**Lembretes:**
- **Minuto:** 0
- **Hora:** 18
- **Dia:** *
- **Mês:** *
- **Dia da Semana:** *
- **Comando:** `php /home/usuario/public_html/cron/cron_lembretes_agendamento.php`

### Configurar via Plesk

1. Acesse o Plesk
2. Vá em "Tarefas Agendadas"
3. Clique em "Adicionar Tarefa"
4. Preencha:
   - **Nome:** Emails de Aniversário
   - **Descrição:** Envia emails para aniversariantes
   - **Comando:** `/usr/bin/php /var/www/vhosts/seusite.com/httpdocs/cron/cron_aniversarios.php`
   - **Executar:** Diariamente às 08:00

### Alternativa: Executar Via URL (Webhook)

Se não tiver acesso a cron jobs, use um serviço como [cron-job.org](https://cron-job.org):

1. Crie uma conta
2. Adicione um novo job:
   - **URL:** `https://seusite.com/cron/cron_aniversarios.php`
   - **Agendamento:** Diariamente às 08:00
3. Repita para lembretes

**⚠️ IMPORTANTE:** Proteja os arquivos de cron contra acesso não autorizado!

---

## 🧪 Testes

### Teste Manual via Interface

1. Acesse: `http://seusite.com/admin/gerenciar_emails_automaticos.php`
2. Clique em "Executar Agora" em qualquer automação
3. Verifique o resultado na tela
4. Clique no ícone de log para ver detalhes

### Teste via Terminal

```bash
# Testar envio de aniversários
php /caminho/para/cron/cron_aniversarios.php

# Testar envio de lembretes
php /caminho/para/cron/cron_lembretes_agendamento.php
```

### Verificar Logs

```bash
# Ver log de aniversários
tail -f logs/cron_aniversarios_2026-01.log

# Ver log de lembretes
tail -f logs/cron_lembretes_2026-01.log
```

### Testar com Dados Reais

**Para Aniversários:**
1. Crie um cliente de teste
2. Defina a data de nascimento como HOJE
3. Adicione um email válido
4. Execute o cron manualmente
5. Verifique se o email chegou

**Para Lembretes:**
1. Crie um agendamento para AMANHÃ
2. Certifique-se de que o cliente tem email
3. Execute o cron manualmente
4. Verifique se o email chegou

---

## 🔍 Troubleshooting

### Problema: Emails não estão sendo enviados

**Verificações:**

1. **SMTP está ativo?**
   ```sql
   SELECT smtp_ativo FROM configuracoes_email WHERE id = 1;
   ```
   Deve retornar `1`.

2. **Credenciais corretas?**
   - Teste em `configuracoes_email.php` → "Enviar E-mail de Teste"
   - Verifique se está usando "Senha de App" (Gmail)

3. **Cron está executando?**
   ```bash
   grep CRON /var/log/syslog
   ```

4. **Erros nos logs?**
   ```bash
   tail -50 logs/cron_aniversarios_*.log
   ```

### Problema: Gmail rejeitando emails

**Solução:**
1. Ative "Verificação em duas etapas"
2. Use "Senha de App" (não sua senha pessoal)
3. Verifique se a conta não está bloqueada

### Problema: Nenhum aniversariante encontrado

**Verificações:**
```sql
-- Ver todos os aniversariantes do dia
SELECT nome, email, data_nascimento
FROM usuarios
WHERE DAY(data_nascimento) = DAY(CURDATE())
  AND MONTH(data_nascimento) = MONTH(CURDATE());
```

### Problema: Cron não executa

**Verificações:**

1. **Caminho do PHP correto?**
   ```bash
   which php
   # Use o caminho retornado no cron
   ```

2. **Permissões corretas?**
   ```bash
   ls -la cron/cron_*.php
   # Deve ter permissão de execução
   ```

3. **Crontab está salvo?**
   ```bash
   crontab -l
   # Deve mostrar seus crons
   ```

### Logs de Debug

Ativar modo debug no SMTP:

```sql
UPDATE configuracoes_email SET smtp_debug = 1 WHERE id = 1;
```

Depois verifique o log de erros do PHP:
```bash
tail -f /var/log/php_errors.log
```

---

## 📊 Monitoramento

### Dashboard de Emails

Acesse: `admin/gerenciar_emails_automaticos.php`

**Informações disponíveis:**
- Status do SMTP
- Últimos 20 emails enviados
- Logs de execução dos crons
- Botões para executar manualmente

### Verificar Estatísticas

```sql
-- Emails enviados hoje
SELECT COUNT(*) as total
FROM logs_email
WHERE DATE(criado_em) = CURDATE();

-- Taxa de sucesso
SELECT
    status,
    COUNT(*) as total,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM logs_email), 2) as percentual
FROM logs_email
GROUP BY status;

-- Emails por tipo (assunto)
SELECT
    CASE
        WHEN assunto LIKE '%Aniversário%' THEN 'Aniversário'
        WHEN assunto LIKE '%Lembrete%' THEN 'Lembrete'
        WHEN assunto LIKE '%Confirmação%' THEN 'Confirmação'
        ELSE 'Outro'
    END as tipo,
    COUNT(*) as total
FROM logs_email
GROUP BY tipo;
```

---

## 🎨 Personalização

### Alterar Templates de Email

Edite: `includes/EmailTemplates.php`

```php
// Exemplo: Adicionar desconto no email de aniversário
$conteudo = '
<div style="background: #ffd700; padding: 20px; text-align: center;">
    <h3 style="margin: 0; color: #333;">
        🎁 GANHE 20% DE DESCONTO!
    </h3>
    <p style="margin: 10px 0 0 0; color: #666;">
        Use o código: <strong>ANIVERSARIO20</strong>
    </p>
</div>
';
```

### Alterar Horários dos Lembretes

**Para enviar 48h antes:**
```php
// Em cron_lembretes_agendamento.php, linha 36:
$data_alvo = date('Y-m-d', strtotime('+2 days'));
```

**Para enviar no mesmo dia (12h antes):**
```php
$data_alvo = date('Y-m-d');
// E adicionar filtro de hora_agendamento > CURTIME() + INTERVAL 12 HOUR
```

---

## 📝 Manutenção

### Limpeza de Logs Antigos

Crie um cron mensal para limpar logs:

```bash
# Executar no dia 1 de cada mês às 03:00
0 3 1 * * find /caminho/para/logs/ -name "*.log" -mtime +90 -delete
```

### Limpeza de Logs de Email

```sql
-- Apagar logs com mais de 6 meses
DELETE FROM logs_email
WHERE criado_em < DATE_SUB(NOW(), INTERVAL 6 MONTH);
```

---

## ✅ Checklist de Implantação

- [ ] Migração SQL aplicada
- [ ] SMTP configurado e testado
- [ ] Cron jobs criados
- [ ] Teste de aniversário enviado com sucesso
- [ ] Teste de lembrete enviado com sucesso
- [ ] Logs sendo gerados corretamente
- [ ] Dashboard acessível e funcionando
- [ ] Documentação lida e compreendida

---

## 🆘 Suporte

Se encontrar problemas:

1. Verifique os logs em `logs/cron_*.log`
2. Verifique o histórico em `admin/gerenciar_emails_automaticos.php`
3. Teste manualmente clicando em "Executar Agora"
4. Consulte a documentação do PHPMailer: https://github.com/PHPMailer/PHPMailer

---

**Status:** ✅ Sistema pronto para produção
**Última atualização:** 2026-01-16
