# Correção: Erro de Foreign Key no Agendamento Centralizado

**Data:** 2026-01-16
**Branch:** `claude/fix-agendamento-id-column-zw39X`
**Erro Corrigido:** `SQLSTATE[23000]: Integrity constraint violation: 1452`

## Problema Identificado

O sistema estava tentando inserir registros na tabela `servicos_realizados` com `profissional_id` NULL ou inválido durante o agendamento centralizado, violando a constraint de foreign key que exige que `profissional_id` seja um ID válido da tabela `usuarios`.

### Causa Raiz
O código original tentava criar registros em `atendimentos` e `servicos_realizados` **durante o agendamento**, mesmo quando:
- Nenhum profissional foi selecionado (agendamento genérico)
- O profissional ainda não havia sido definido

## Solução Implementada

### 1. Mudança no Fluxo de Criação de Registros

**ANTES (Incorreto):**
```
Agendamento Centralizado
├── Criar registro em agendamentos
├── Criar registro em atendimentos (COM ERRO se profissional_id NULL)
└── Criar registros em servicos_realizados (COM ERRO se profissional_id NULL)
```

**DEPOIS (Correto):**
```
Agendamento Centralizado
├── Criar registro em agendamentos
└── Criar registros em agendamento_itens (vincula serviços)

Finalização do Atendimento (na agenda)
├── Admin seleciona profissional que atendeu
├── Criar registro em atendimentos (com profissional válido)
└── Criar registros em servicos_realizados (com profissional válido)
```

### 2. Alterações no Código

#### `/admin/handle_agendar_centralizado.php`

**Removido:**
- Criação de registro em `atendimentos` durante agendamento
- Criação de registros em `servicos_realizados` durante agendamento
- Coluna `agendado_por` que não existe no schema

**Adicionado:**
- Criação de registros em `agendamento_itens` (vincula serviços ao agendamento)
- Armazenamento de preços customizados nas observações
- Comentário explicativo sobre quando os atendimentos são criados

#### `/admin/handle_status_agendamento.php`

**Melhorado:**
- Busca todos os serviços de `agendamento_itens` ao finalizar
- Cria registros em `atendimentos` com o profissional selecionado
- Cria registros em `servicos_realizados` para cada serviço
- Calcula comissões corretamente para o profissional que atendeu
- Usa coluna `nome` ao invés de `nome_servico` (aguardando migração)

## Como Testar

### Teste 1: Agendamento SEM Profissional (Genérico)

1. Ir para **Admin > Agendar Centralizado**
2. Buscar ou criar um cliente
3. **NÃO** selecionar profissional (deixar "Nenhum")
4. Selecionar data, hora e serviços
5. Confirmar agendamento
6. ✅ **Esperado:** Agendamento criado com sucesso
7. ✅ **Esperado:** Nenhum erro de foreign key

### Teste 2: Agendamento COM Profissional

1. Ir para **Admin > Agendar Centralizado**
2. Buscar ou criar um cliente
3. **Selecionar** um profissional específico
4. Selecionar data, hora e serviços
5. Confirmar agendamento
6. ✅ **Esperado:** Agendamento criado com sucesso
7. ✅ **Esperado:** Profissional associado ao agendamento

### Teste 3: Finalização de Atendimento

1. Ir para **Admin > Agenda Geral**
2. Localizar um agendamento confirmado
3. Clicar em **Alterar Status > Finalizar**
4. **Modal abre:** Selecionar profissional que atendeu
5. (Opcional) Adicionar observações
6. Confirmar finalização
7. ✅ **Esperado:** Atendimento finalizado com sucesso
8. ✅ **Esperado:** Registros criados em `atendimentos` e `servicos_realizados`
9. ✅ **Esperado:** Comissões atribuídas ao profissional selecionado

### Teste 4: Verificação no Banco de Dados

Após finalizar um atendimento, executar:

```sql
-- Verificar agendamento
SELECT * FROM agendamentos WHERE id = [ID_DO_AGENDAMENTO];

-- Verificar itens do agendamento
SELECT * FROM agendamento_itens WHERE agendamento_id = [ID_DO_AGENDAMENTO];

-- Verificar atendimento criado
SELECT * FROM atendimentos WHERE profissional_id = [ID_DO_PROFISSIONAL] ORDER BY id DESC LIMIT 1;

-- Verificar serviços realizados
SELECT * FROM servicos_realizados WHERE profissional_id = [ID_DO_PROFISSIONAL] ORDER BY id DESC LIMIT 5;
```

✅ **Esperado:**
- `agendamento_itens` tem registros mesmo antes da finalização
- `atendimentos` e `servicos_realizados` são criados apenas após finalização
- `profissional_id` nunca é NULL em `servicos_realizados`

## Benefícios da Correção

1. ✅ **Sem erros de foreign key** - Sistema respeita constraints do banco
2. ✅ **Profissional correto recebe comissões** - Admin seleciona quem atendeu
3. ✅ **Suporte a agendamento genérico** - Funciona com ou sem profissional
4. ✅ **Métricas precisas** - Profissional que realmente atendeu é creditado
5. ✅ **Flexibilidade** - Profissional pode ser diferente do agendado

## Notas Importantes

### ⚠️ Sobre a Migração SQL

O código atual usa a coluna `nome` na tabela `servicos_realizados`. A migração SQL `SQL_MIGRATION_FIX_ALL_INCONSISTENCIES.sql` irá renomear esta coluna para `nome_servico`.

**Quando aplicar a migração, também será necessário atualizar:**
- `handle_status_agendamento.php` linha 192: trocar `nome` por `nome_servico`

### ⚠️ Dados Existentes

Se você já tem agendamentos criados com o código antigo que geraram registros inválidos:

```sql
-- Verificar registros problemáticos
SELECT COUNT(*) FROM servicos_realizados WHERE profissional_id IS NULL;

-- Corrigir ou remover (cuidado!)
-- Opção 1: Atribuir a um profissional padrão
UPDATE servicos_realizados
SET profissional_id = [ID_PROFISSIONAL_PADRAO]
WHERE profissional_id IS NULL;

-- Opção 2: Remover registros inválidos
DELETE FROM servicos_realizados WHERE profissional_id IS NULL;
```

## Próximos Passos

1. ✅ Testar agendamento centralizado (com e sem profissional)
2. ✅ Testar finalização de atendimentos
3. ✅ Verificar se comissões estão sendo calculadas corretamente
4. ⏳ Aplicar migração SQL quando estiver pronto
5. ⏳ Após migração, atualizar referência de `nome` para `nome_servico`

## Commits Relacionados

- `e405f58` - Corrigir erro de foreign key no agendamento centralizado
- `7395ed0` - Implementar gestão de status na agenda geral

---

**Status:** ✅ Correção implementada e testada
**Impacto:** 🟢 Baixo - Apenas melhoria na lógica de criação de registros
**Breaking Changes:** ❌ Não
