# Gerenciador de Tarefas Backend - (Laravel 12 + Redis + SQLite)

API RESTful construída em Laravel 12, responsável por gerenciar autenticação, usuários, equipes e tarefas.

**Ele fornece os serviços principais do sistema, incluindo:**

- [x] 🔐 Autenticação via Laravel Sanctum (registro, login, logout e perfil).
--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^
- [x] 👥 Gestão de equipes e membros (criação, convites, aprovação/rejeição, papéis de acesso).
--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^
- [x] ✅ Gerenciamento de tarefas com CRUD completo, filtros, paginação e visualização em Kanban.
--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^
- [x] 📬 Notificações por e-mail processadas em filas Redis (tarefas criadas ou atribuídas).
--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^
- [x] 📅 Serviço inteligente de feriados integrado à API da Invertexto, com cache em Redis, para alertar usuários quando a data de vencimento cair em um feriado.
--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^
- [ ] ⚡ Integração em tempo real (pronta para WebSockets/Reverb) para atualização instantânea de tarefas e notificações. ^(^^em^ ^desencolvimento^^)^
--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^--^
- [ ] 🛠️ Infraestrutura escalável com Redis para cache, filas e suporte opcional ao Laravel Horizon para monitoramento. ^(^^em^ ^desencolvimento^^)^

---
## ============== ⚙️ Stack ===================
- PHP 8.2+
- Laravel 12
- SQLite (dev)
- Redis (cache + queues)
- Sanctum (SPA auth)
- Mail (SMTP/Mailtrap)
- (Opcional) Horizon (dashboard de filas)
---

## =========== 🚀 Subir localmente ===========
### 1) Dependências
- ##### dentro de /backend
```
composer install
cp .env.example .env
```
- ##### gerar app key
```
php artisan key:generate
```


### 2) Banco (SQLite)
- ##### cria o arquivo do banco
```
touch database/database.sqlite
```

### 3) Variáveis de ambiente

- ##### No .env:

```
# FrontEnd and BackEnd Comunication
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:9000

# Auth SPA (Sanctum)
SESSION_DRIVER=cookie
SESSION_DOMAIN=localhost
SANCTUM_STATEFUL_DOMAINS=localhost:9000
SESSION_SECURE_COOKIE=false

# Banco
DB_CONNECTION=sqlite
DB_DATABASE=/abs/path/para/database/database.sqlite

# Cache/Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail (Mailtrap/Sua conta SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=SEU_USER
MAIL_PASSWORD=SEU_PASS
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=no-reply@localhost
MAIL_FROM_NAME="Tasks App"

# Feriados (Invertexto)
INVERTEXTO_API_KEY=SEU_TOKEN
HOLIDAY_CACHE_TTL=43200  # 12h
HOLIDAY_DEFAULT_UF=PE
```

### 4) Migrations

```
php artisan migrate
```
### 6) Servidores
- #### app HTTP
```
php artisan serve
```

- #### filas (emails etc.)
```
php artisan queue:work --queue=emails,default
```
ou
```
php artisan queue:work
```

- [ ] (Opcional) Horizon: ^(^^em^ ^desencolvimento^^)^
```
composer require laravel/horizon
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"
php artisan horizon
```
---
## =========== 🔑 Autenticação =========== 

SPA com Sanctum (cookies).

O frontend deve chamar GET /sanctum/csrf-cookie antes de POST /login//register.

## =============🔌 Endpoints principais ===========
```
 ______________________________________________________
|    METODO   |          ROTA         |     ACESSO     |
|-------------|-----------------------|----------------|
|     POST    |      /api/register    |    (guest)     |
|     POST    |      /api/login       |    (guest)     |
|     POST    |      /api/logout      |   (auth:web)   |
|             |                       |                |
|     GET     |      /api/auth/me     | (auth:sanctum) |
|             |                       |                |
| apiResource |       /api/tasks      | (auth:sanctum) |
| apiResource |       /api/teams      | (auth:sanctum) |
| apiResource | /api/team-memberships | (auth:sanctum) |
|             |                       |                |
|     GET     | /api/holidays/check   | (auth:sanctum) |
|-------------|-----------------------|----------------|
```

## =========== 📬 Filas + E-mails =========== 

- _**Jobs:**_

SendTaskCreatedMail

SendTaskAssignedMail

- _**Disparo:**_

Em TaskController@store (criação)

Em TaskController@assign ou update quando assignee_id muda.

- _**Worker:**_
```
php artisan queue:work --queue=emails,default
```

## ==== 🎉 Feriados (Service + Cache Redis) ====

app/Services/HolidayService.php

> Usa GET https://api.invertexto.com/v1/holidays/{year}?token=...&state=UF

Cache {year}:{UF} (TTL configurável).

**GET** `/api/holidays/check`

Retorna `Json`: 
```
{ 
    is_holiday, 
    name, 
    date, 
    uf, 
    year 
}
```


## =========== 🧪 Testes rápidos ===========
php artisan tinker
```
>>> app(\App\Services\HolidayService::class)->check('2025-09-07', 'PE')

Response: --->
{
    is_holiday: true,
    name: "Dia da Independência",
    date: "2025-09-07",
    uf: "PE",
    year: "2025"
}
```

🐞 Troubleshooting

