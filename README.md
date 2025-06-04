# Sistema de Gerenciamento de Tarefas (To-Do API)

API REST desenvolvida para gerenciar tarefas pessoais com sistema de autenticação.

## 📋 Tecnologia Escolhida

**Laravel** foi escolhido como framework principal por ser a tecnologia com a qual possuo maior familiaridade e experiência. Embora fosse possível desenvolver com NestJS (conforme especificado como preferencial), o tempo de desenvolvimento seria significativamente maior que o disponibilizado para este teste técnico. O Laravel permite uma implementação mais rápida e robusta, garantindo a entrega de todas as funcionalidades solicitadas dentro do prazo estabelecido.

## 🛠️ Setup do Projeto

### Pré-requisitos
- Docker e Docker Compose instalados
- Git
- PHP 8.1 ou superior (para desenvolvimento local)
- Composer (para desenvolvimento local)

### 1. Clonar o repositório
```bash
git clone git@github.com:AndersonNascimentoDosSantos/reis_software_teste_backend.git
cd reis_softwares_api
```

### 2. Instalar dependências PHP
```bash
composer install
```

### 3. Configurar variáveis de ambiente
```bash
cp .env.example .env
```

Edite o arquivo `.env` com as configurações do banco PostgreSQL:
```env
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

### 4. Configurar alias do Sail (Opcional, mas recomendado)
Para facilitar o uso dos comandos, configure o alias:

**Linux/macOS:**
```bash
echo "alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'" >> ~/.bashrc
source ~/.bashrc
```

**Windows (PowerShell):**
```powershell
Set-Alias sail "vendor/bin/sail"
```

### 5. Inicializar o projeto com Docker
```bash
# Com alias configurado:
sail up -d

# Sem alias:
./vendor/bin/sail up -d
```

### 6. Gerar chave da aplicação
```bash
sail artisan key:generate
```

### 7. Executar migrations
```bash
sail artisan migrate
```

### 8. Executar seeders (opcional)
```bash
sail artisan db:seed
```

## 🚀 Comandos Úteis do Sail

### Iniciar o ambiente
```bash
sail up -d
```

### Parar o ambiente
```bash
sail down
```

### Verificar logs
```bash
sail logs
```

### Acessar o shell do container
```bash
sail shell
```

### Executar comandos artisan
```bash
sail artisan [comando]
```

### Executar testes
```bash
sail test
```

## 📚 Documentação da API (Swagger)

Após inicializar o projeto, acesse a documentação Swagger em:
```
http://localhost/api/documentation
```

O Swagger fornece documentação interativa de todos os endpoints da API, permitindo testar diretamente pela interface web.

## 🔐 Autenticação com Laravel Sanctum

O projeto utiliza **Laravel Sanctum** para autenticação via tokens pessoais.

### Endpoints de Autenticação:
- `POST /api/auth/register` - Cadastro de usuário
- `POST /api/auth/login` - Login do usuário

### Como usar:
1. Registre um usuário ou faça login
2. Use o token retornado nas requisições:
```
Authorization: Bearer {seu-token-aqui}
```

## 📊 Banco de Dados PostgreSQL

O projeto utiliza **PostgreSQL** como banco de dados principal, executando via Docker através do Laravel Sail.

### Configurações padrão:
- **Host:** pgsql (container Docker)
- **Porta:** 5432
- **Database:** laravel
- **Username:** sail
- **Password:** password

### Comandos úteis:
```bash
# Acessar o banco via psql
sail psql

# Reset do banco de dados
sail artisan migrate:fresh --seed

# Status das migrations
sail artisan migrate:status
```

## 🎯 Endpoints da API

### Autenticação
- `POST /api/auth/register` - Cadastro
- `POST /api/auth/login` - Login

### Tarefas (protegidas por Sanctum)
- `GET /api/tasks` - Listar tarefas
- `GET /api/tasks?status=completed` - Filtrar por status
- `POST /api/tasks` - Criar tarefa
- `GET /api/tasks/{id}` - Buscar tarefa por ID
- `PUT /api/tasks/{id}` - Atualizar tarefa
- `DELETE /api/tasks/{id}` - Excluir tarefa

## 📝 Decisões Técnicas

### Framework: Laravel
- Maior familiaridade e experiência da equipe
- Ecossistema maduro e bem documentado
- Laravel Sail para ambiente Docker simplificado
- Sanctum para autenticação de API

### Banco de Dados: PostgreSQL
- Robustez e confiabilidade para dados estruturados
- Excelente performance para consultas complexas
- Suporte nativo no Laravel

### Autenticação: Laravel Sanctum
- Integração nativa com Laravel
- Simplicidade para APIs SPA e mobile
- Tokens seguros e gerenciáveis

### Estrutura do Projeto
- Separação clara de responsabilidades
- Validações robustas com Form Requests
- Middleware para autenticação e autorização
- Recursos (Resources) para padronização de respostas

### Funcionalidades Implementadas

#### Soft Delete nas Tarefas
- Implementação do soft delete para manter histórico de tarefas
- Recuperação de tarefas excluídas quando necessário
- Filtros automáticos para excluir registros deletados das consultas padrão
- Endpoint específico para listar tarefas excluídas
- Endpoint para restaurar tarefas excluídas

#### Validações com Form Requests
- Validações centralizadas em classes dedicadas
- Regras de validação específicas para cada operação
- Mensagens de erro personalizadas e traduzidas
- Validação de dados antes de atingir o controller
- Reutilização de regras de validação entre endpoints

#### Testes de Integração
- Testes de fluxo completo de autenticação
- Testes de CRUD de tarefas
- Testes de validação de dados
- Testes de soft delete e restauração
- Testes de autorização e permissões
- Cobertura de cenários de sucesso e erro

## 🔄 Melhorias Futuras

Com mais tempo disponível, as seguintes melhorias seriam implementadas:

### Funcionalidades
- Sistema de logs estruturado
- Paginação otimizada
- Cache para consultas frequentes

### Arquitetura
- Implementação de Repository Pattern
- Services para lógica de negócio complexa
- Event/Listeners para ações assíncronas
- Queues para processamento em background

### DevOps
- Pipeline CI/CD
- Monitoramento de performance
- Backup automatizado do banco
- Environment de staging
