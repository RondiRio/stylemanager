# Guia do Sistema de Agendamento Centralizado

## 📋 Resumo das Funcionalidades Implementadas

Todas as funcionalidades solicitadas foram implementadas com sucesso! O sistema agora suporta:

✅ **Agendamento centralizado** para admin/recepcionista
✅ **Tipo de usuário Recepcionista** com acesso limitado
✅ **Cadastro rápido de clientes** (apenas nome e telefone)
✅ **Busca inteligente** de clientes por nome ou telefone
✅ **Preço customizado** por serviço/atendimento
✅ **Sistema de aniversários** com lembretes
✅ **Controles de rede social** (cadastro de clientes, landing page, etc.)
✅ **Todas as funcionalidades atuais mantidas**

---

## 🚀 Como Começar

### Passo 1: Aplicar Migração do Banco de Dados

1. Faça login como **admin**
2. Acesse **Configurações**
3. Clique no botão **"Aplicar Migração"** (alerta amarelo no topo da página)
4. Leia as informações e marque o checkbox de confirmação
5. Clique em **"Aplicar Migração"**
6. Aguarde a conclusão (todos os comandos devem aparecer como "OK")

**Importante:** Faça backup do banco de dados antes de aplicar a migração!

### Passo 2: Configurar o Sistema

Após aplicar a migração, configure as novas funcionalidades:

1. Acesse **Admin → Configurações**
2. Role até a seção **"Rede Social e Agenda Centralizada"**
3. Configure conforme sua necessidade:
   - ☑️ **Permitir cadastro de clientes**: Desmarque se quiser que apenas admin/recepcionista cadastrem clientes
   - ☑️ **Mostrar landing page**: Desmarque se não quiser exibir página inicial
   - ☑️ **Ativar agenda centralizada**: Ative para usar agendamento centralizado
   - ☑️ **Lembretes de aniversário**: Ative para ver aniversariantes
4. Clique em **"Salvar Configurações"**

---

## 👥 Criar Usuário Recepcionista

Para criar um usuário do tipo recepcionista:

1. Acesse **Admin → Gerenciar → Profissionais** (ou use a interface de cadastro de usuários)
2. Crie um novo usuário
3. **IMPORTANTE**: No banco de dados, altere o campo `tipo` para `'recepcionista'`
   ```sql
   UPDATE usuarios SET tipo = 'recepcionista' WHERE id = <ID_DO_USUARIO>;
   ```
4. O recepcionista poderá fazer login normalmente

**Permissões do Recepcionista:**
- ✅ Ver agenda geral
- ✅ Criar novos agendamentos
- ✅ Buscar e cadastrar clientes rápidos
- ✅ Ver aniversariantes
- ❌ NÃO tem acesso a: configurações, relatórios financeiros, gestão de profissionais/serviços

---

## 📅 Como Usar o Agendamento Centralizado

### Acessar a Interface

**Admin:**
- Menu: **Agenda → Novo Agendamento**

**Recepcionista:**
- Menu: **Agenda → Novo Agendamento**
- Ou: Dashboard → Botão **"Novo Agendamento"**

### Processo de Agendamento

#### 1️⃣ Selecionar Cliente

**Opção A: Cliente Existente**
1. Digite nome ou telefone na barra de busca
2. Aguarde os resultados aparecerem
3. Clique no cliente desejado
4. Os dados serão preenchidos automaticamente

**Opção B: Novo Cliente Rápido**
1. Clique em **"Novo Cliente Rápido"**
2. Preencha:
   - Nome completo (obrigatório)
   - Telefone (obrigatório) - apenas números
   - Data de nascimento (opcional, mas recomendado para aniversários)
   - Observações (opcional)
3. Clique em **"Cadastrar"**
4. O cliente será selecionado automaticamente

#### 2️⃣ Selecionar Profissional

- Escolha o profissional no dropdown
- Apenas profissionais ativos aparecerão

#### 3️⃣ Selecionar Data e Hora

- Escolha a data (não pode ser passada)
- Escolha o horário
- O sistema validará se o profissional está disponível

#### 4️⃣ Selecionar Serviços

1. Marque os serviços desejados
2. O total e duração são calculados automaticamente
3. **Preço Customizado** (opcional):
   - Clique em **"Definir preço customizado"** abaixo do serviço
   - Digite o novo valor
   - O total será recalculado

#### 5️⃣ Adicionar Observações (Opcional)

- Digite qualquer informação relevante sobre o agendamento

#### 6️⃣ Confirmar

- Clique em **"Confirmar Agendamento"**
- Você será redirecionado para a agenda geral

---

## 🎂 Sistema de Aniversários

### Visualizar Aniversariantes

**Acessar:**
- Menu: **Agenda → Aniversariantes**

**Você verá:**
- 🎉 **Aniversariantes de Hoje** - Cards destacados com idade
- 📆 **Aniversariantes do Mês** - Tabela completa ordenada por dia

### Enviar Parabéns

Cada aniversariante tem um botão **WhatsApp** que:
- Abre conversa no WhatsApp Web/App
- Pré-preenche mensagem de parabéns
- Basta clicar em enviar!

### Coletar Data de Nascimento

**Para clientes existentes:**
- Peça para atualizarem o cadastro
- Ou atualize manualmente no banco de dados

**Para novos clientes:**
- Sempre preencha o campo ao criar "Cliente Rápido"
- Se cadastro de cliente está ativo, eles podem informar na criação da conta

---

## 🔍 Sistema de Busca de Clientes

### Como Funciona

A busca é **unificada** e inteligente:
- Busca em **usuários cadastrados** (tipo: cliente)
- Busca em **clientes rápidos**
- Por **nome** ou **telefone**
- Resultados em tempo real (após digitar 2 caracteres)

### Identificação

Cada cliente tem uma badge:
- 🔵 **Cadastrado**: Cliente tem conta completa no sistema
- 🟠 **Rápido**: Cliente cadastrado apenas com dados básicos

### Tratar Duplicatas

O sistema **impede** cadastrar cliente rápido com telefone já existente:
- Mostra mensagem de erro
- Informa qual cliente já existe
- Sugere buscar o cliente existente

---

## 💰 Preço Customizado

### Quando Usar

Use preço customizado quando:
- Cliente tem desconto especial
- Serviço tem promoção temporária
- Preço negociado é diferente do padrão
- Está testando novo valor

### Como Funciona

1. Selecione o serviço normalmente
2. Clique em **"Definir preço customizado"**
3. Digite o novo valor
4. **Se deixar vazio**: usa preço padrão
5. **Se preencher**: usa preço customizado

**Nota:** O preço customizado é salvo **por atendimento**, não afeta o preço padrão do serviço.

---

## ⚙️ Configurações do Sistema

### Rede Social e Agenda

**Permitir cadastro de clientes** ✅ / ❌
- **Ativo**: Clientes podem criar conta via landing page/registro
- **Inativo**: Apenas admin/recepcionista criam clientes

**Mostrar landing page** ✅ / ❌
- **Ativo**: Visitantes não logados veem página inicial
- **Inativo**: Redireciona direto para login

**Ativar agenda centralizada** ✅ / ❌
- **Ativo**: Admin/recepcionista podem usar agendamento centralizado
- **Inativo**: Link de agendamento some do menu

**Lembretes de aniversário** ✅ / ❌
- **Ativo**: Sistema mostra aniversariantes
- **Inativo**: Link de aniversariantes some do menu

---

## 🎯 Casos de Uso

### Caso 1: Estabelecimento com Recepção Física

**Cenário:** Salão com recepcionista presencial

**Configuração:**
- ❌ Permitir cadastro de clientes (desativado)
- ❌ Mostrar landing page (desativado)
- ✅ Agenda centralizada (ativado)
- ✅ Lembretes de aniversário (ativado)

**Fluxo:**
1. Cliente liga ou chega presencialmente
2. Recepcionista busca cliente por nome/telefone
3. Se não existe, cria "Cliente Rápido"
4. Agenda atendimento com profissional disponível
5. No dia do aniversário, parabeniza via WhatsApp

### Caso 2: Estabelecimento Híbrido

**Cenário:** Barbearia que permite agendamento online e presencial

**Configuração:**
- ✅ Permitir cadastro de clientes (ativado)
- ✅ Mostrar landing page (ativado)
- ✅ Agenda centralizada (ativado)
- ✅ Lembretes de aniversário (ativado)

**Fluxo:**
1. Cliente pode se cadastrar online e agendar pelo site
2. OU pode ligar/ir presencialmente
3. Recepcionista também pode agendar
4. Ambos os tipos de agendamento aparecem na agenda
5. Sistema parabeniza todos os clientes cadastrados

### Caso 3: Apenas Online

**Cenário:** Consultório de estética só com agendamento online

**Configuração:**
- ✅ Permitir cadastro de clientes (ativado)
- ✅ Mostrar landing page (ativado)
- ❌ Agenda centralizada (pode desativar se não usar)
- ✅ Lembretes de aniversário (ativado)

**Fluxo:**
1. Clientes se cadastram e agendam online
2. Admin monitora agenda e agendamentos
3. Sistema parabeniza automaticamente

---

## 🗄️ Estrutura do Banco de Dados

### Novas Tabelas

**clientes_rapidos**
- `id`: ID único
- `nome`: Nome completo
- `telefone`: Telefone (apenas números)
- `data_nascimento`: Data de nascimento (opcional)
- `observacoes`: Observações gerais
- `criado_por`: ID do admin/recepcionista que criou
- `created_at`, `updated_at`: Timestamps

**lembretes_aniversario**
- `id`: ID único
- `usuario_id`: Referência para usuarios (se for cliente cadastrado)
- `cliente_rapido_id`: Referência para clientes_rapidos (se for cliente rápido)
- `nome`: Nome do cliente
- `data_nascimento`: Data de nascimento
- `ultimo_lembrete`: Data do último lembrete enviado

### Novos Campos

**usuarios**
- `tipo`: Agora aceita 'recepcionista' também
- `data_nascimento`: Data de nascimento
- `telefone_principal`: Telefone principal (cadastro com telefone)

**configuracoes**
- `permitir_cadastro_cliente`: Toggle de cadastro
- `mostrar_landing_page`: Toggle de landing page
- `agenda_centralizada_ativa`: Toggle de agenda centralizada
- `lembrar_aniversarios`: Toggle de aniversários

**servicos_realizados**
- `preco_customizado`: Preço override (nullable)
- `usa_preco_customizado`: Flag booleana

**agendamentos**
- `cliente_rapido_id`: Referência para cliente rápido (nullable)
- `cliente_nome`: Nome do cliente (para clientes rápidos)
- `cliente_telefone`: Telefone do cliente (para clientes rápidos)

### View

**vw_clientes_unificado**
- Une usuários tipo 'cliente' com clientes_rapidos
- Permite busca unificada
- Campos: id, tipo, nome, telefone, email, data_nascimento

---

## 📁 Arquivos Criados

### Backend (PHP)

**admin/**
- `agendar_centralizado.php` - Interface de agendamento (650 linhas)
- `handle_agendar_centralizado.php` - Handler de agendamento (180 linhas)
- `api_buscar_clientes.php` - API de busca (60 linhas)
- `api_cadastrar_cliente_rapido.php` - API de cadastro rápido (120 linhas)
- `aniversariantes.php` - Lista de aniversariantes (240 linhas)
- `apply_migration_centralized_scheduling.php` - Script de migração (220 linhas)

**recepcionista/**
- `dashboard.php` - Dashboard do recepcionista (220 linhas)

**docs/**
- `SQL_MIGRATION_CENTRALIZED_SCHEDULING.sql` - SQL completo da migração (200 linhas)

### Modificados

- `includes/auth.php` - Novas funções de permissão
- `includes/header.php` - Menus para admin e recepcionista
- `admin/configuracoes.php` - Novas configurações

**Total de linhas adicionadas:** ~1.945 linhas
**Total de arquivos novos:** 8
**Total de arquivos modificados:** 3

---

## 🧪 Testando o Sistema

### Checklist de Testes

**1. Migração do Banco**
- [ ] Aplicar migração sem erros
- [ ] Verificar todas as tabelas criadas
- [ ] Verificar view vw_clientes_unificado funciona

**2. Configurações**
- [ ] Salvar configurações de rede social
- [ ] Toggles funcionam corretamente
- [ ] Alertas de configuração aparecem

**3. Recepcionista**
- [ ] Criar usuário recepcionista
- [ ] Login como recepcionista
- [ ] Dashboard carrega corretamente
- [ ] Não tem acesso a áreas restritas

**4. Busca de Clientes**
- [ ] Buscar por nome parcial
- [ ] Buscar por telefone
- [ ] Resultados aparecem em tempo real
- [ ] Seleção de cliente funciona

**5. Cadastro Rápido**
- [ ] Criar cliente apenas com nome e telefone
- [ ] Validação de telefone funciona
- [ ] Impede duplicatas
- [ ] Cliente aparece nas buscas

**6. Agendamento Centralizado**
- [ ] Selecionar cliente existente
- [ ] Criar e selecionar cliente rápido
- [ ] Escolher profissional e data/hora
- [ ] Selecionar múltiplos serviços
- [ ] Preço customizado funciona
- [ ] Total e duração calculados corretamente
- [ ] Validação de horário ocupado
- [ ] Confirmar agendamento

**7. Aniversários**
- [ ] Lista de aniversariantes carrega
- [ ] Aniversariantes do dia destacados
- [ ] Botão WhatsApp funciona
- [ ] Filtro por mês funciona

**8. Integrações**
- [ ] Agendamento aparece na agenda geral
- [ ] Cliente rápido aparece em relatórios (se aplicável)
- [ ] Preço customizado reflete em totais
- [ ] Funcionalidades antigas mantidas

---

## 🐛 Troubleshooting

### Erro: Migração Falhou

**Problema:** Comando SQL deu erro
**Solução:**
1. Verifique se já aplicou a migração antes
2. Alguns erros "already exists" são normais
3. Se erro crítico, restaure backup e tente novamente
4. Verifique logs do MySQL para detalhes

### Erro: Não Consigo Criar Recepcionista

**Problema:** Não há opção "recepcionista" no cadastro
**Solução:**
1. A migração foi aplicada?
2. Use SQL direto para alterar tipo:
   ```sql
   UPDATE usuarios SET tipo = 'recepcionista' WHERE id = X;
   ```

### Erro: Busca de Clientes Não Funciona

**Problema:** Busca não retorna resultados
**Solução:**
1. Verifique se view `vw_clientes_unificado` foi criada
2. Teste query direto no MySQL
3. Verifique permissões da view
4. Console do navegador mostra erro JS?

### Erro: Preço Customizado Não Salva

**Problema:** Preço volta ao padrão
**Solução:**
1. Campos `preco_customizado` e `usa_preco_customizado` foram criados?
2. Verifique se está preenchendo o input corretamente
3. Deixar vazio = usar preço padrão (comportamento esperado)

### Erro: WhatsApp Não Abre

**Problema:** Botão não funciona
**Solução:**
1. Telefone está no formato correto? (apenas números)
2. WhatsApp Web está instalado?
3. Teste URL manualmente: `https://wa.me/5511999999999`

---

## 🚀 Próximos Passos Sugeridos

### Curto Prazo

1. **Testar extensivamente** em ambiente de homologação
2. **Treinar equipe** no uso do agendamento centralizado
3. **Criar usuários recepcionistas** conforme necessário
4. **Configurar toggles** de acordo com modelo de negócio
5. **Importar datas de nascimento** de clientes existentes (se houver)

### Médio Prazo

1. **Notificações automáticas** de aniversários via e-mail/SMS
2. **Dashboard de recepcionista** mais completo com métricas
3. **Histórico de agendamentos** por cliente
4. **Relatórios** de clientes rápidos vs cadastrados
5. **Export** de lista de aniversariantes

### Longo Prazo

1. **App mobile** para recepcionistas
2. **Integração com sistemas de CRM**
3. **Automação de mensagens** de aniversário
4. **Sistema de fidelidade** baseado em aniversários
5. **BI/Analytics** de agendamentos centralizados

---

## 📞 Suporte

Se encontrar problemas ou tiver dúvidas:

1. Consulte este guia
2. Verifique a seção Troubleshooting
3. Revise o código-fonte (bem comentado)
4. Entre em contato com o suporte

---

## ✅ Conclusão

Todas as funcionalidades solicitadas foram implementadas:

✅ Agendamento centralizado em admin/recepcionista
✅ Funcionalidades de cliente mantidas para futuro uso
✅ Registro com nome, telefone e serviço
✅ Opção de valor customizado
✅ Busca por nome ou telefone
✅ Cadastro com telefone (email opcional)
✅ Sistema de lembretes de aniversário
✅ Área de recepcionista com acesso à agenda
✅ Toggle de rede social e cadastro de clientes
✅ Agenda centralizada configurável

O sistema está **pronto para uso** após aplicar a migração do banco de dados!

🎉 **Bom uso do novo sistema de agendamento centralizado!** 🎉
