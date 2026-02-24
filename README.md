# 🔐 Google Auth API Project

Este projeto é uma API RESTful desenvolvida com **Laravel 12** para autenticação de usuários via **Google OAuth 2.0**, com fluxo de cadastro em duas etapas: login social seguido de complemento de dados pessoais (nome, CPF e data de nascimento).

---

## 🚀 Tecnologias Utilizadas

- PHP 8.4
- Laravel 12
- PostgreSQL 17
- Laravel Sanctum
- Google API Client (OAuth 2.0)
- PHPUnit
- Docker + Docker Compose
- PHPDoc
- PHPStan (nível 9)
- PHP-CS-Fixer (PSR-12)
- PSR-4 Autoload
- Princípios SOLID
- Design Patterns (Repository, DTO, Service)

---

## 🧩 Estrutura Modular

O projeto foi construído com arquitetura modular, onde cada domínio está separado com suas próprias responsabilidades:

```
app/
├── Http/Controllers/
├── Providers/
│   └── AppServiceProvider.php
└── Modules/
    └── User/
        ├── Controllers/
        │   ├── GoogleAuthController.php
        │   └── UserController.php
        ├── DTOs/
        │   ├── CompleteUserDataDTO.php
        │   └── UserFilterDTO.php
        ├── Enums/
        │   ├── AuthMessagesEnum.php
        │   └── UserMessagesEnum.php
        ├── Jobs/
        │   └── SendRegistrationEmailJob.php
        ├── Models/
        │   ├── MailLog.php
        │   ├── TemporaryUser.php
        │   └── User.php
        ├── Repositories/
        │   ├── UserRepository.php
        │   └── UserRepositoryInterface.php
        ├── Requests/
        │   └── CompleteUserRequest.php
        ├── Services/
        │   ├── GoogleAuthService.php
        │   └── UserService.php
        └── routes/
            └── api.php
```

---

## 📌 Regras de Negócio Atendidas

- ✅ Autenticação via Google OAuth 2.0 (login social)
- ✅ Cadastro em duas etapas (login Google → complemento de dados)
- ✅ Armazenamento temporário de usuários pré-cadastro (`TemporaryUser`)
- ✅ Finalização de cadastro com nome, CPF e data de nascimento
- ✅ Tokens do Google criptografados com `Crypt` do Laravel
- ✅ CPF com validação e sanitização (remoção de formatação)
- ✅ E-mail de confirmação de cadastro via Job assíncrono
- ✅ Registro de logs de envio de e-mails (`MailLog`)
- ✅ Listagem de usuários com filtros paginados (nome e CPF)
- ✅ Soft delete nos usuários
- ✅ API RESTful estruturada

---

## 🧪 Testes Automatizados

### Testes de Feature

**Controllers:**
- Geração da URL de login do Google
- Callback do Google armazenando usuário temporário
- Listagem de usuários retornando dados corretos
- Finalização de cadastro criando usuário a partir de dados temporários

**Services:**
- `GoogleAuthService` — criação de `TemporaryUser` a partir do callback
- `UserService` — finalização de cadastro com DTO e listagem com filtros

**Repositories:**
- Criação de usuário
- Busca por e-mail do Google
- Atualização de dados
- Filtragem por nome e CPF com paginação

### Executando os testes

```bash
php artisan test
```

Rodar um teste específico:

```bash
php artisan test --filter=GoogleAuthControllerTest
```

---

## 📮 Endpoints da API

### 🔑 Autenticação Google

**Obter URL de login:**

```
GET /api/google/login-url
```

Resposta:
```json
{
  "url": "https://accounts.google.com/o/oauth2/v2/auth?..."
}
```

| Status | Descrição |
|--------|-----------|
| `200`  | URL gerada com sucesso |

---

**Callback do Google:**

```
GET /api/google/callback?code={authorization_code}
```

| Status | Descrição |
|--------|-----------|
| `302`  | Redireciona para `FRONT_CALLBACK_URL/register?email={email}` |
| `400`  | Código de autorização inválido |
| `500`  | Falha na autenticação com o Google |

---

### 👤 Usuários

**Listar usuários:**

```
GET /api/users?name={nome}&cpf={cpf}&per_page={quantidade}&page={pagina}
```

Todos os query params são opcionais. Resposta:

```json
{
  "message": "Lista de usuários carregada com sucesso.",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "João Silva",
        "cpf": "12345678900",
        "birth_date": "1990-01-15",
        "google_email": "joao@gmail.com",
        "created_at": "2025-04-13T00:00:00.000000Z",
        "updated_at": "2025-04-13T00:00:00.000000Z"
      }
    ],
    "per_page": 20,
    "total": 1
  }
}
```

| Status | Descrição |
|--------|-----------|
| `200`  | Lista retornada com sucesso |

---

**Completar cadastro:**

```
POST /api/users/complete
```

Body:
```json
{
  "name": "João Silva",
  "cpf": "123.456.789-00",
  "birth_date": "1990-01-15",
  "google_token": "ya29.a0AfH6SMB..."
}
```

| Campo          | Tipo   | Obrigatório | Regras             |
|----------------|--------|-------------|--------------------|
| `name`         | string | sim         | max: 255           |
| `cpf`          | string | sim         | max: 14            |
| `birth_date`   | string | sim         | formato: date      |
| `google_token` | string | sim         | token válido       |

Resposta:
```json
{
  "message": "Usuário criado com sucesso.",
  "data": {
    "id": 1,
    "name": "João Silva",
    "cpf": "12345678900",
    "birth_date": "1990-01-15",
    "google_email": "joao@gmail.com",
    "created_at": "2025-04-13T00:00:00.000000Z",
    "updated_at": "2025-04-13T00:00:00.000000Z"
  }
}
```

| Status | Descrição |
|--------|-----------|
| `201`  | Usuário criado com sucesso |
| `422`  | Erro de validação |
| `500`  | Token inválido ou usuário temporário não encontrado |

---

**Usuário autenticado (Sanctum):**

```
GET /api/user
```

| Status | Descrição |
|--------|-----------|
| `200`  | Retorna dados do usuário autenticado |
| `401`  | Não autenticado |

---

## 🐳 Docker

O projeto possui um ambiente Docker completo com **PHP-FPM**, **Nginx** e **PostgreSQL**, configurado com boas práticas.

### Estrutura Docker

```
.docker/
├── php/
│   ├── Dockerfile    # Multi-stage: base (produção) + dev (com Xdebug)
│   └── php.ini       # Configurações customizadas do PHP
└── nginx/
    └── default.conf  # Configuração do Nginx para Laravel

docker-compose.yml    # Orquestração dos serviços
.dockerignore         # Exclusões para o build
```

### Serviços

| Serviço      | Imagem                | Porta           |
|--------------|-----------------------|-----------------|
| **app**      | PHP 8.4 FPM Alpine    | 9000 (interna)  |
| **nginx**    | Nginx 1.27 Alpine     | 8000 → 80       |
| **postgres** | PostgreSQL 17 Alpine  | 5432 → 5432     |

### Subindo o projeto

```bash
docker compose up -d --build
```

### Executando comandos dentro do container

```bash
docker compose exec app bash
```

### Executando comandos fora do container

```bash
docker compose exec app php artisan migrate
```

---

## 📄 Como Rodar o Projeto

1. **Clone o repositório:**

```bash
git clone https://github.com/seu-usuario/Google-Auth-API-Project.git
cd Google-Auth-API-Project
```

2. **Configure o arquivo de ambiente:**

```bash
cp .env.example .env
```

Preencha as variáveis do Google OAuth no `.env`:

```env
GOOGLE_CLIENT_ID=seu-client-id
GOOGLE_CLIENT_SECRET=seu-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/google/callback
FRONT_CALLBACK_URL=http://localhost:5173
```

3. **Suba os containers:**

```bash
docker compose up -d --build
```

4. **Instale as dependências e configure o Laravel:**

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

5. **Rode os testes:**

```bash
docker compose exec app php artisan test
```

6. **Acesse a aplicação em:** `http://localhost:8000`

---

## ✅ Qualidade e Análise de Código

- ✅ PHPDoc para documentação de classes, métodos e propriedades
- ✅ PHPStan nível **9** (máximo) configurado
- ✅ PHP-CS-Fixer com padrão **PSR-12**
- ✅ Código totalmente tipado com suporte a análise por IDEs

### Comandos de qualidade

```bash
# Análise estática com PHPStan
composer phpstan

# Verificar estilo de código
composer cs:check

# Corrigir estilo de código automaticamente
composer cs:fix
```

---

## 🛡️ Diferenciais Entregues

- ✅ Código limpo e modularizado
- ✅ Princípios SOLID aplicados
- ✅ Padrões PSR-4 e PSR-12
- ✅ Testes automatizados (controllers, services e repositories)
- ✅ Uso de DTOs, Services e Repositories
- ✅ Tokens sensíveis criptografados com `Crypt`
- ✅ Job assíncrono para envio de e-mails
- ✅ Logs de envio de e-mails (`MailLog`)
- ✅ Docker com multi-stage build e Xdebug para desenvolvimento
- ✅ Healthcheck no PostgreSQL
- ✅ Headers de segurança no Nginx
- ✅ Usuário não-root nos containers
- ✅ Documentação clara e detalhada

---

## 🔄 Fluxo de Autenticação

```
┌──────────┐     1. GET /api/google/login-url     ┌──────────┐
│          │ ──────────────────────────────────▶   │          │
│ Frontend │     ◀── { url: "..." }                │   API    │
│          │                                       │  Laravel │
│          │     2. Usuário faz login no Google     │          │
│          │ ──────────────────────────────────▶   │          │
│          │                                       │          │
│          │     3. Google redireciona para         │          │
│          │        /api/google/callback?code=...   │          │
│          │                                       │          │
│          │     4. API salva TemporaryUser e       │          │
│          │        redireciona para o front        │          │
│          │     ◀── 302 /register?email=...       │          │
│          │                                       │          │
│          │     5. POST /api/users/complete        │          │
│          │ ──────────────────────────────────▶   │          │
│          │        { name, cpf, birth_date,        │          │
│          │          google_token }                │          │
│          │                                       │          │
│          │     6. Usuário criado + e-mail enviado │          │
│          │     ◀── 201 { user }                  │          │
└──────────┘                                       └──────────┘
```

---

## 👨‍💻 Autor

**Roberson Mariani**
Desenvolvedor Fullstack PHP & Laravel | JS e VueJS
[LinkedIn](https://linkedin.com/in/robersonmariani) | [GitHub](https://github.com/RobersonMariani)
