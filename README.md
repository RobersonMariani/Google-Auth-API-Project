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
- Design Patterns (Repository, DTO, Service, Resource)

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
        ├── Commands/
        │   └── CleanExpiredTemporaryUsersCommand.php
        ├── Controllers/
        │   ├── GoogleAuthController.php
        │   └── UserController.php
        ├── DTOs/
        │   ├── CompleteUserDataDTO.php
        │   └── UserFilterDTO.php
        ├── Enums/
        │   ├── AuthMessagesEnum.php
        │   ├── EmailMessagesEnum.php
        │   ├── MailLogStatusEnum.php
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
        ├── Resources/
        │   └── UserResource.php
        ├── Rules/
        │   └── CpfRule.php
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
- ✅ Armazenamento temporário de usuários pré-cadastro (`TemporaryUser`) com expiração de 15 minutos
- ✅ Finalização de cadastro com nome, CPF e data de nascimento
- ✅ Validação de CPF com dígitos verificadores (regra customizada `CpfRule`)
- ✅ Validação de data de nascimento (não pode ser futura)
- ✅ Tokens do Google criptografados com `Crypt` do Laravel
- ✅ E-mail de confirmação de cadastro via Job assíncrono (3 tentativas, timeout de 30s)
- ✅ Registro de logs de envio de e-mails (`MailLog`)
- ✅ Listagem de usuários autenticada com filtros paginados (nome e CPF, máximo 100 por página)
- ✅ Limpeza automática de registros temporários expirados (`artisan temporary-users:cleanup`)
- ✅ Soft delete nos usuários
- ✅ API RESTful estruturada com API Resources

---

## 🛡️ Segurança

- ✅ Rotas protegidas com `auth:sanctum` (listagem de usuários)
- ✅ Rate limiting: 10 req/min para autenticação, 5 req/min para cadastro, 60 req/min geral
- ✅ Tokens sensíveis criptografados com `Crypt`
- ✅ Erros internos não expostos ao cliente (mensagens genéricas via Enum)
- ✅ `env()` utilizado apenas em arquivos de config (compatível com `config:cache`)
- ✅ API Resource controlando campos retornados (sem expor `google_token`, `google_email`, `deleted_at`)
- ✅ Expiração de 15 minutos para usuários temporários
- ✅ Headers de segurança no Nginx (X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy)
- ✅ Usuário não-root nos containers Docker
- ✅ Email codificado com `urlencode()` na URL de redirect

---

## 🧪 Testes Automatizados

**56 testes** com **137 assertions**, todos no padrão **AAA** (Arrange / Act / Assert).

### Controllers (13 testes)

**GoogleAuthController:**
- URL de login retorna URL válida
- Callback armazena usuário temporário e redireciona
- Callback sem código retorna 400
- Callback com código vazio retorna 400
- Callback com erro retorna mensagem genérica (sem vazar detalhes internos)

**UserController:**
- Listagem retorna dados para usuário autenticado
- Listagem sem autenticação retorna 401
- Listagem não expõe `google_token` nem `google_email`
- Cadastro completo cria usuário a partir do temporário
- CPF inválido retorna 422
- CPF com dígitos repetidos retorna 422
- Data de nascimento futura retorna 422
- Campos obrigatórios faltando retorna 422
- Resposta usa formato do Resource (sem dados sensíveis)

### Services (11 testes)

**GoogleAuthService:**
- Callback cria usuário temporário
- Callback atualiza usuário temporário existente
- Callback define tempo de expiração
- Token inválido lança exceção
- Token expirado lança exceção

**UserService:**
- Cadastro completo cria usuário a partir do temporário
- Cadastro completo deleta usuário temporário após criação
- Temporário expirado lança exceção
- Temporário inexistente lança exceção
- Listagem com filtros retorna resultados corretos
- Listagem sem filtros retorna todos

### Repositories (10 testes)

- Criação de usuário persiste no banco
- Busca por e-mail do Google retorna usuário
- Busca por e-mail inexistente retorna null
- Atualização de dados altera atributos
- Filtragem apenas por nome
- Filtragem apenas por CPF
- Filtragem por nome e CPF
- Filtragem sem resultados retorna vazio
- Filtragem exclui soft deleted
- Resultados ordenados por ID decrescente

### Unitários (18 testes)

**CpfRule:**
- CPF válido (somente dígitos)
- CPF válido (com formatação)
- Outro CPF válido
- CPF com dígitos verificadores errados
- Todos os dígitos repetidos (10 variantes)
- Menos de 11 dígitos
- Mais de 11 dígitos
- String vazia
- Valor não-string
- CPF com letras

**UserFilterDTO:**
- Construtor remove formatação do CPF
- Construtor mantém CPF null
- fromRequest com valores padrão
- fromRequest com todos os parâmetros
- `per_page` limitado a 100
- `per_page` mínimo é 1
- `per_page` negativo é clampado
- `per_page` não-numérico usa padrão

### Commands (3 testes)

**CleanExpiredTemporaryUsersCommand:**
- Remove registros expirados e preserva válidos
- Sem expirados não deleta nada
- Tabela vazia roda sem erro

### Executando os testes

```bash
docker compose exec app php artisan test
```

Rodar um teste específico:

```bash
docker compose exec app php artisan test --filter=GoogleAuthControllerTest
```

---

## 📮 Endpoints da API

### 🔑 Autenticação Google

**Obter URL de login:**

```
GET /api/google/login-url
```

Middleware: `throttle:auth` (10 req/min)

Resposta:
```json
{
  "url": "https://accounts.google.com/o/oauth2/v2/auth?..."
}
```

| Status | Descrição |
|--------|-----------|
| `200`  | URL gerada com sucesso |
| `429`  | Rate limit excedido |

---

**Callback do Google:**

```
GET /api/google/callback?code={authorization_code}
```

Middleware: `throttle:auth` (10 req/min)

| Status | Descrição |
|--------|-----------|
| `302`  | Redireciona para `FRONT_CALLBACK_URL` + `FRONT_REGISTER_PATH` + `?email={email}` |
| `400`  | Código de autorização inválido |
| `429`  | Rate limit excedido |
| `500`  | Falha na autenticação com o Google |

---

### 👤 Usuários

**Listar usuários:**

```
GET /api/users?name={nome}&cpf={cpf}&per_page={quantidade}&page={pagina}
```

Middleware: `auth:sanctum`

Todos os query params são opcionais. `per_page` aceita valores entre 1 e 100 (padrão: 20).

Resposta:

```json
{
  "message": "Lista de usuários carregada com sucesso.",
  "data": [
    {
      "id": 1,
      "name": "João Silva",
      "cpf": "12345678900",
      "birth_date": "1990-01-15",
      "created_at": "2025-04-13T00:00:00.000000Z",
      "updated_at": "2025-04-13T00:00:00.000000Z"
    }
  ]
}
```

| Status | Descrição |
|--------|-----------|
| `200`  | Lista retornada com sucesso |
| `401`  | Não autenticado |

---

**Completar cadastro:**

```
POST /api/users/complete
```

Middleware: `throttle:registration` (5 req/min)

Body:
```json
{
  "name": "João Silva",
  "cpf": "529.982.247-25",
  "birth_date": "1990-01-15",
  "google_token": "ya29.a0AfH6SMB..."
}
```

| Campo          | Tipo   | Obrigatório | Regras                                          |
|----------------|--------|-------------|--------------------------------------------------|
| `name`         | string | sim         | max: 255                                         |
| `cpf`          | string | sim         | max: 14, validação de dígitos verificadores      |
| `birth_date`   | string | sim         | formato date, anterior a hoje, posterior a 1900   |
| `google_token` | string | sim         | token Google válido                              |

Resposta:
```json
{
  "message": "Usuário criado com sucesso.",
  "data": {
    "id": 1,
    "name": "João Silva",
    "cpf": "52998224725",
    "birth_date": "1990-01-15",
    "created_at": "2025-04-13T00:00:00.000000Z",
    "updated_at": "2025-04-13T00:00:00.000000Z"
  }
}
```

| Status | Descrição |
|--------|-----------|
| `201`  | Usuário criado com sucesso |
| `422`  | Erro de validação (CPF inválido, data futura, campos faltando) |
| `429`  | Rate limit excedido |
| `500`  | Token inválido ou usuário temporário não encontrado/expirado |

---

**Usuário autenticado (Sanctum):**

```
GET /api/user
```

Middleware: `auth:sanctum`

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
git clone https://github.com/RobersonMariani/Google-Auth-API-Project.git
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
FRONT_REGISTER_PATH=/register
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
- ✅ 56 testes automatizados com 137 assertions no padrão AAA

### Comandos de qualidade

```bash
# Análise estática com PHPStan
composer phpstan

# Verificar estilo de código
composer cs:check

# Corrigir estilo de código automaticamente
composer cs:fix

# Limpar usuários temporários expirados
docker compose exec app php artisan temporary-users:cleanup
```

---

## 🛡️ Diferenciais Entregues

- ✅ Código limpo e modularizado
- ✅ Princípios SOLID aplicados
- ✅ Padrões PSR-4 e PSR-12
- ✅ 56 testes automatizados no padrão AAA (unitários + feature)
- ✅ Uso de DTOs, Services, Repositories e Resources
- ✅ Validação de CPF com dígitos verificadores (regra customizada)
- ✅ Rate limiting por tipo de rota (auth, registration, api)
- ✅ Rotas protegidas com Laravel Sanctum
- ✅ Expiração de usuários temporários com command de cleanup
- ✅ Tokens sensíveis criptografados com `Crypt`
- ✅ Erros internos não vazam para o cliente
- ✅ Strings centralizadas em Enums (mensagens, status, e-mails)
- ✅ Job assíncrono com retries e método `failed()`
- ✅ Logs de envio de e-mails (`MailLog`)
- ✅ API Resource controlando campos expostos
- ✅ Docker com multi-stage build e Xdebug para desenvolvimento
- ✅ Healthcheck no PostgreSQL
- ✅ Headers de segurança no Nginx
- ✅ Usuário não-root nos containers
- ✅ Índices de performance no banco de dados
- ✅ Documentação clara e detalhada

---

## 🔄 Fluxo de Autenticação

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
│          │          google_token }               │          │
│          │                                       │          │
│          │     6. Usuário criado + e-mail enviado│          │
│          │     ◀── 201 { user }                  │          │
└──────────┘                                       └──────────┘
```

---

## 👨‍💻 Autor

**Roberson Mariani**
Desenvolvedor Fullstack PHP & Laravel | JS e VueJS
[LinkedIn](https://linkedin.com/in/roberson-mariani) | [GitHub](https://github.com/RobersonMariani)
