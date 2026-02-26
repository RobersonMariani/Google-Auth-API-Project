# Google Auth API

API RESTful para autenticação de usuários via Google OAuth 2.0, com fluxo de cadastro em duas etapas (login social + complemento de dados pessoais), construída com Laravel 12.

## Funcionalidades

- Autenticação via Google OAuth 2.0 (login social)
- Cadastro em duas etapas: login Google → complemento de dados (nome, CPF, data de nascimento)
- Armazenamento temporário de usuários pré-cadastro com expiração de 15 minutos
- Validação algorítmica de CPF com dígitos verificadores (regra customizada)
- E-mail de confirmação de cadastro via job assíncrono (3 tentativas, timeout de 30s)
- Registro de logs de envio de e-mails (`MailLog`)
- Listagem de usuários com filtros (nome, CPF) e paginação configurável
- Tokens do Google criptografados com `Crypt` do Laravel
- Limpeza automática de registros temporários expirados via comando Artisan
- Rate limiting por tipo de rota (auth, registration, api)
- Soft delete nos usuários

## Stack

| Camada         | Tecnologia                                      |
|----------------|--------------------------------------------------|
| Linguagem      | PHP 8.4                                          |
| Framework      | Laravel 12                                       |
| Banco de Dados | PostgreSQL 17                                    |
| Autenticação   | Laravel Sanctum + Google OAuth 2.0               |
| OAuth Client   | Google API Client (`google/apiclient`)           |
| Fila / Cache   | Database driver                                  |
| Code Style     | PHP-CS-Fixer (PSR-12)                            |
| Análise        | PHPStan (nível 9)                                |
| Testes         | PHPUnit (unitários + feature)                    |
| Containers     | Docker Compose                                   |

## Requisitos

- Docker e Docker Compose

## Instalação

```bash
git clone https://github.com/RobersonMariani/Google-Auth-API-Project.git
cd Google-Auth-API-Project

cp .env.example .env
```

Preencha as variáveis do Google OAuth no `.env`:

```env
GOOGLE_CLIENT_ID=seu-client-id
GOOGLE_CLIENT_SECRET=seu-client-secret
```

Suba os containers e configure o Laravel:

```bash
docker compose up -d --build

docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Após o setup, a API estará disponível em `http://localhost:8000`.

## Serviços

| Serviço    | URL / Porta          | Descrição                     |
|------------|----------------------|-------------------------------|
| API (Nginx)| http://localhost:8000 | Proxy reverso para PHP-FPM   |
| App (PHP)  | porta 9000 (interna) | PHP 8.4 FPM Alpine           |
| PostgreSQL | localhost:5432        | Banco de dados                |

## Dados Iniciais (Seeder)

| Dado      | Detalhe                               |
|-----------|---------------------------------------|
| Usuários  | 150.000 usuários gerados via factory (batches de 5.000) |

---

## Endpoints

As rotas de listagem e perfil requerem o header:

```
Authorization: Bearer {token}
```

### Health Check

```
GET /up
```

---

### Autenticação Google

#### Obter URL de login

```
GET /api/google/login-url
```

Middleware: `throttle:auth` (10 req/min)

**Resposta** `200`:

```json
{
  "url": "https://accounts.google.com/o/oauth2/v2/auth?client_id=..."
}
```

#### Callback do Google

```
GET /api/google/callback?code={authorization_code}
```

Middleware: `throttle:auth` (10 req/min)

O Google redireciona o usuário para esta rota após autenticação. A API cria um `TemporaryUser` (expira em 15 min) e redireciona para o frontend com o e-mail na query string.

**Resposta** `302`:

```
→ {FRONT_CALLBACK_URL}{FRONT_REGISTER_PATH}?email={email}
```

| Status | Descrição                                |
|--------|------------------------------------------|
| `302`  | Redireciona para o frontend              |
| `400`  | Código de autorização ausente ou inválido|
| `429`  | Rate limit excedido                      |
| `500`  | Falha na autenticação com o Google       |

---

### Usuários

#### Completar cadastro

```
POST /api/users/complete
```

Middleware: `throttle:registration` (5 req/min)

**Body:**

```json
{
  "name": "João Silva",
  "cpf": "529.982.247-25",
  "birth_date": "1990-01-15",
  "email": "joao@gmail.com"
}
```

**Resposta** `201`:

```json
{
  "message": "Usuário criado com sucesso.",
  "data": {
    "id": 1,
    "name": "João Silva",
    "cpf": "52998224725",
    "birth_date": "1990-01-15",
    "created_at": "2026-02-24T12:00:00.000000Z",
    "updated_at": "2026-02-24T12:00:00.000000Z"
  },
  "token": "1|abc123def456..."
}
```

**Validações:**
- `name`: obrigatório, string, máx. 255 caracteres
- `cpf`: obrigatório, máx. 14 caracteres, validação algorítmica de dígitos verificadores
- `birth_date`: obrigatório, formato date, anterior a hoje, posterior a 1900
- `email`: obrigatório, formato e-mail válido, único no sistema

| Status | Descrição                                                    |
|--------|--------------------------------------------------------------|
| `201`  | Usuário criado com sucesso (inclui token Sanctum)            |
| `422`  | Erro de validação (CPF inválido, data futura, e-mail duplicado) |
| `429`  | Rate limit excedido                                          |
| `500`  | Usuário temporário não encontrado ou expirado                |

#### Listar usuários

```
GET /api/users
GET /api/users?name=João&cpf=529&per_page=20&page=1
```

Middleware: `auth:sanctum`

| Parâmetro  | Tipo   | Padrão | Descrição                    |
|------------|--------|--------|------------------------------|
| `name`     | string | —      | Filtra por nome (parcial)    |
| `cpf`      | string | —      | Filtra por CPF (parcial)     |
| `page`     | int    | 1      | Página atual                 |
| `per_page` | int    | 20     | Itens por página (máx. 100)  |

**Resposta** `200`:

```json
{
  "message": "Lista de usuários carregada com sucesso.",
  "data": [
    {
      "id": 1,
      "name": "João Silva",
      "cpf": "52998224725",
      "birth_date": "1990-01-15",
      "created_at": "2026-02-24T12:00:00.000000Z",
      "updated_at": "2026-02-24T12:00:00.000000Z"
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 10 }
}
```

| Status | Descrição                     |
|--------|-------------------------------|
| `200`  | Lista retornada com sucesso   |
| `401`  | Não autenticado               |

#### Usuário autenticado

```
GET /api/user
```

Middleware: `auth:sanctum`

**Resposta** `200`:

```json
{
  "id": 1,
  "name": "João Silva",
  "cpf": "52998224725",
  "birth_date": "1990-01-15",
  "created_at": "2026-02-24T12:00:00.000000Z",
  "updated_at": "2026-02-24T12:00:00.000000Z"
}
```

---

## Fluxo de Autenticação

```
┌──────────┐     1. GET /api/google/login-url     ┌──────────┐
│          │ ──────────────────────────────────▶  │          │
│ Frontend │     ◀── { url: "..." }               │   API    │
│          │                                       │  Laravel │
│          │     2. Usuário faz login no Google    │          │
│          │ ──────────────────────────────────▶  │          │
│          │                                       │          │
│          │     3. Google redireciona para        │          │
│          │        /api/google/callback?code=...  │          │
│          │                                       │          │
│          │     4. API salva TemporaryUser e      │          │
│          │        redireciona para o front       │          │
│          │     ◀── 302 /register?email=...       │          │
│          │                                       │          │
│          │     5. POST /api/users/complete       │          │
│          │ ──────────────────────────────────▶   │          │
│          │        { name, cpf, birth_date,       │          │
│          │          email }                      │          │
│          │                                       │          │
│          │     6. Usuário criado + e-mail enviado│          │
│          │     ◀── 201 { user, token }           │          │
└──────────┘                                       └──────────┘
```

---

## Segurança

- Rotas protegidas com `auth:sanctum`
- Rate limiting: 10 req/min (auth), 5 req/min (registration), 60 req/min (api geral)
- Tokens sensíveis criptografados com `Crypt`
- Erros internos não expostos ao cliente (mensagens genéricas via Enum)
- API Resource controlando campos retornados (sem expor `google_token`, `google_email`, `deleted_at`)
- Expiração de 15 minutos para usuários temporários
- Headers de segurança no Nginx (X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy)
- Usuário não-root nos containers Docker
- `env()` utilizado apenas em arquivos de config (compatível com `config:cache`)

## Variáveis de Ambiente

| Variável               | Descrição                                | Valor Padrão                                  |
|------------------------|------------------------------------------|-----------------------------------------------|
| `DB_CONNECTION`        | Driver do banco                          | `pgsql`                                       |
| `DB_HOST`              | Host do PostgreSQL                       | `postgres`                                    |
| `DB_PORT`              | Porta do PostgreSQL                      | `5432`                                        |
| `DB_DATABASE`          | Nome do banco                            | `google_auth_api_project`                     |
| `DB_USERNAME`          | Usuário do banco                         | `laravel`                                     |
| `DB_PASSWORD`          | Senha do banco                           | `secret`                                      |
| `QUEUE_CONNECTION`     | Driver da fila                           | `database`                                    |
| `CACHE_STORE`          | Driver de cache                          | `database`                                    |
| `GOOGLE_CLIENT_ID`     | Client ID do Google OAuth                | —                                             |
| `GOOGLE_CLIENT_SECRET` | Client Secret do Google OAuth            | —                                             |
| `GOOGLE_REDIRECT_URI`  | URL de callback do Google                | `http://localhost:8000/api/google/callback`    |
| `FRONT_CALLBACK_URL`   | URL base do frontend                     | `http://localhost:5173`                        |
| `FRONT_REGISTER_PATH`  | Caminho da página de registro no frontend| `/register`                                   |
| `APP_PORT`             | Porta da API (Docker)                    | `8000`                                        |

## Testes

**56 testes** com **137 assertions**, todos no padrão **AAA** (Arrange / Act / Assert).

| Módulo                    | Testes | Descrição                                              |
|---------------------------|--------|--------------------------------------------------------|
| GoogleAuthController      | 5      | URL de login, callback, erros de código                |
| UserController            | 8      | Listagem, cadastro, validações, campos protegidos      |
| GoogleAuthService         | 5      | Criação/atualização de temporários, expiração, tokens  |
| UserService               | 6      | Cadastro completo, temporário expirado, filtros        |
| UserRepository            | 10     | CRUD, filtros, soft delete, ordenação                  |
| CpfRule (unitário)        | 10     | CPFs válidos, inválidos, repetidos, formatos           |
| UserFilterDTO (unitário)  | 8      | Construtor, limites de per_page, valores padrão        |
| CleanExpiredCommand       | 3      | Limpeza de expirados, tabela vazia                     |

```bash
# Todos os testes
docker compose exec app php artisan test

# Teste específico
docker compose exec app php artisan test --filter=GoogleAuthControllerTest
```

## Qualidade de Código

```bash
# Análise estática (PHPStan nível 9)
composer phpstan

# Verificar estilo de código (PHP-CS-Fixer — PSR-12)
composer cs:check

# Corrigir estilo de código automaticamente
composer cs:fix

# Limpar usuários temporários expirados
docker compose exec app php artisan temporary-users:cleanup
```

## Arquitetura

O projeto segue uma **arquitetura modular** com o domínio `User` isolado em `app/Modules/User/`. O módulo possui suas próprias camadas seguindo os padrões Repository, DTO, Service e Resource:

```
app/Modules/
└── User/
    ├── Commands/        → Comandos Artisan (limpeza de temporários)
    ├── Controllers/     → Recebe a requisição HTTP e delega ao Service
    ├── DTOs/            → Data Transfer Objects (tipagem de dados)
    ├── Enums/           → Mensagens e status centralizados
    ├── Jobs/            → Jobs assíncronos (envio de e-mails)
    ├── Models/          → Eloquent models (User, TemporaryUser, MailLog)
    ├── Repositories/    → Acesso a dados (abstração do Eloquent)
    ├── Requests/        → Form Requests com validação
    ├── Resources/       → Formatação da resposta JSON
    ├── Rules/           → Regras de validação customizadas (CPF)
    ├── Services/        → Regra de negócio (GoogleAuth + User)
    └── routes/          → Rotas do módulo
```

### Fluxo de uma requisição

```
Request → Controller → FormRequest (validação) → Service (regra de negócio) → Repository (dados) → Resource (resposta)
```

## Estrutura Docker

```
.docker/
├── php/
│   ├── Dockerfile       → PHP 8.4-FPM Alpine (multi-stage: base + dev com Xdebug)
│   └── php.ini          → 256M memory, 64M upload, timezone America/Sao_Paulo
└── nginx/
    └── default.conf     → Virtual host com headers de segurança
```

| Container   | Descrição                                       |
|-------------|-------------------------------------------------|
| `app`       | PHP 8.4 FPM — executa a aplicação Laravel       |
| `nginx`     | Nginx 1.27 — proxy reverso para PHP-FPM         |
| `postgres`  | PostgreSQL 17 — banco de dados com health check  |

---

## Autor

**Roberson Mariani**
Desenvolvedor Fullstack PHP & Laravel | JS e VueJS
[LinkedIn](https://linkedin.com/in/roberson-mariani) | [GitHub](https://github.com/RobersonMariani)
