# Análise de Inconsistências do Banco de Dados

**Data da Análise:** 2026-01-16
**Analista:** Claude Code

## Resumo Executivo

Durante a análise completa do banco de dados do StyleManager, foram identificadas **10 categorias principais** de inconsistências que podem causar erros em produção. Este documento detalha cada problema e propõe soluções.

---

## 1. Tabela `agendamentos` - Inconsistência Crítica de Colunas

### Problema
A tabela tem nomes de colunas conflitantes entre o schema base e as queries em produção:

**Schema Base (stylemanager_BD.sql):**
```sql
data DATE NOT NULL,
hora_inicio TIME NOT NULL,
-- Falta: hora_fim
```

**Usado em view_agenda_geral.php:**
```sql
a.data, a.hora_inicio, a.hora_fim  -- hora_fim NÃO EXISTE!
```

**Usado em view_minha_agenda.php:**
```sql
a.data_agendamento, a.hora_agendamento  -- Colunas DIFERENTES!
```

### Impacto
- **CRÍTICO**: Queries podem falhar com erro "Unknown column"
- Dados inconsistentes entre diferentes partes do sistema

### Solução Proposta
Padronizar para: `data_agendamento`, `hora_agendamento`, `hora_fim`

---

## 2. Tabela `usuarios` - Tipo 'recepcionista' Não Existe no Schema

### Problema
**Schema Base:**
```sql
tipo ENUM('cliente','profissional','admin')
```

**Migration SQL_MIGRATION_CENTRALIZED_SCHEDULING.sql:**
```sql
tipo ENUM('admin', 'profissional', 'cliente', 'recepcionista')
```

**Código em Produção:**
- `auth.php` verifica `$_SESSION['tipo'] === 'recepcionista'`
- `agendar_centralizado.php` usa `requer_login(['admin', 'recepcionista'])`

### Impacto
- **ALTO**: Sistema não permite login de recepcionistas
- Verificações de permissão falham silenciosamente

### Solução Proposta
Aplicar ALTER TABLE para adicionar 'recepcionista' ao ENUM

---

## 3. Tabela `gorjetas` - Valores de Status Inconsistentes

### Problema
**Schema Base:**
```sql
status ENUM('pendente','aprovada','negada')
```

**Migration SQL_MIGRATION_FECHAMENTO_CAIXA.sql:**
```sql
status ENUM('pendente', 'aprovado', 'negado')
```

**Código em Produção:**
- Usa: `'aprovado'`, `'negado'`
- Schema espera: `'aprovada'`, `'negada'`

### Impacto
- **ALTO**: Atualizações de status falham silenciosamente
- Queries filtram incorretamente

### Solução Proposta
Padronizar para: `'pendente'`, `'aprovado'`, `'negado'`

---

## 4. Tabela `vales` - Campos de Aprovação Ausentes

### Problema
**Schema Base:**
```sql
CREATE TABLE vales (
  id INT,
  profissional_id INT,
  valor DECIMAL(8,2),
  motivo TEXT,
  data_vale DATE
)
```

**Usado em aprovar_vales.php:**
```sql
SELECT v.status, v.aprovado_por, v.data_aprovacao  -- Colunas NÃO EXISTEM!
```

### Impacto
- **CRÍTICO**: Sistema de aprovação de vales não funciona
- Erros SQL em páginas de aprovação

### Solução Proposta
Adicionar: `status`, `aprovado_por`, `data_aprovacao`

---

## 5. Tabela `configuracoes` - Múltiplos Campos Ausentes

### Problema
O schema base não inclui campos adicionados em 3 migrations diferentes:

**Campos Ausentes:**
1. **De SQL_MIGRATION_CENTRALIZED_SCHEDULING.sql:**
   - `permitir_cadastro_cliente`
   - `mostrar_landing_page`
   - `agenda_centralizada_ativa`
   - `lembrar_aniversarios`
   - `agendamento_sem_profissional`
   - `profissional_ve_propria_agenda`

2. **De SQL_MIGRATION_FECHAMENTO_CAIXA.sql:**
   - `tipo_fechamento`
   - `gorjetas_requerem_aprovacao`

### Impacto
- **MÉDIO-ALTO**: Funcionalidades inteiras não operam corretamente
- Páginas de configuração salvam mas não persistem dados

### Solução Proposta
Adicionar todos os campos faltantes

---

## 6. Tabela `servicos_realizados` - Campo `nome_servico` vs `nome`

### Problema
**Schema Base:**
```sql
nome VARCHAR(100) DEFAULT NULL
```

**Usado em FechamentoPDF.php:**
```sql
sr.nome_servico  -- Coluna DIFERENTE!
```

### Impacto
- **MÉDIO**: PDFs de fechamento podem falhar
- Inconsistência no nome do campo

### Solução Proposta
Verificar qual é o correto e padronizar

---

## 7. Tabelas Completamente Ausentes

### Problema
Três tabelas são criadas em migrations mas não existem no schema base:

1. **`clientes_rapidos`**
   - Usado em: `api_buscar_clientes.php`, `agendar_centralizado.php`
   - Referenciado em: `vw_clientes_unificado`

2. **`lembretes_aniversario`**
   - Sistema de aniversários depende desta tabela

3. **`fechamentos_caixa`**
   - Usado em: `fechamento_caixa.php`, `FechamentoPDF.php`
   - Sistema de fechamento completamente não funcional sem ela

### Impacto
- **CRÍTICO**: Funcionalidades inteiras crasham
- Erros SQL em múltiplas páginas

### Solução Proposta
Executar os CREATE TABLE statements das migrations

---

## 8. View `vw_clientes_unificado` Ausente

### Problema
View criada em SQL_MIGRATION_CENTRALIZED_SCHEDULING.sql não existe:

```sql
CREATE OR REPLACE VIEW vw_clientes_unificado AS ...
```

**Usado em:**
- `api_buscar_clientes.php`
- Qualquer busca de clientes no sistema centralizado

### Impacto
- **ALTO**: Busca de clientes falha completamente
- Sistema de agendamento centralizado não funciona

### Solução Proposta
Criar a view

---

## 9. Campos Adicionados em `agendamentos` Não Aplicados

### Problema
Migration adiciona campos que não existem no schema base:

```sql
ALTER TABLE agendamentos
ADD COLUMN cliente_rapido_id INT NULL,
ADD COLUMN cliente_nome VARCHAR(150) NULL,
ADD COLUMN cliente_telefone VARCHAR(20) NULL
```

**Usado em:**
- `view_agenda_geral.php`
- Qualquer exibição de agenda

### Impacto
- **ALTO**: Agendamentos de "clientes rápidos" não funcionam
- Dados de contato não aparecem

### Solução Proposta
Aplicar os ALTER TABLE statements

---

## 10. Índices Ausentes para Performance

### Problema
Migrations adicionam índices importantes que não existem:

```sql
-- Em usuarios
ADD INDEX idx_nome (nome),
ADD INDEX idx_telefone (telefone),
ADD INDEX idx_data_nascimento (data_nascimento)

-- Em gorjetas/vales
ADD INDEX idx_status (status),
ADD INDEX idx_profissional_status (profissional_id, status)
```

### Impacto
- **MÉDIO**: Performance degradada em buscas
- Queries lentas em tabelas grandes

### Solução Proposta
Criar todos os índices faltantes

---

## Prioridade de Correção

### 🔴 PRIORIDADE CRÍTICA (Quebra Funcionalidades)
1. ✅ Padronizar colunas da tabela `agendamentos`
2. ✅ Criar tabelas ausentes (`clientes_rapidos`, `fechamentos_caixa`, `lembretes_aniversario`)
3. ✅ Adicionar campos de status em `vales` e `gorjetas`
4. ✅ Criar view `vw_clientes_unificado`

### 🟡 PRIORIDADE ALTA (Funcionalidades Parcialmente Quebradas)
5. ✅ Adicionar tipo 'recepcionista' ao ENUM de `usuarios`
6. ✅ Adicionar campos faltantes em `configuracoes`
7. ✅ Adicionar campos em `agendamentos` para clientes rápidos

### 🟢 PRIORIDADE MÉDIA (Performance e Consistência)
8. ✅ Criar índices para performance
9. ✅ Padronizar nome do campo em `servicos_realizados`

---

## Próximos Passos

1. ✅ Criar migration SQL consolidada com todas as correções
2. ✅ Testar migration em ambiente de desenvolvimento
3. ✅ Aplicar em produção com backup prévio
4. ✅ Validar todas as funcionalidades após aplicação
5. ✅ Atualizar documentação do schema

---

## Notas Adicionais

- Todas as migrations SQL existentes devem ser consolidadas em um único schema atualizado
- Recomenda-se criar um dump do schema atualizado após correções
- Implementar verificação automática de schema em futuras atualizações
