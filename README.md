# MyNewsApp

A lightweight news application that fetches top headlines via NewsAPI, caches them in a MySQL database, allows users to search (fulltext) and post comments.  
Built with PHP 8.2 (Apache) using Docker.

## Table of Contents
- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [Environment Variables (.env)](#environment-variables-env)
- [Database & Migrations](#database--migrations)
- [Composer Packages](#composer-packages)
- [Development Commands](#development-commands)
- [Routes](#routes)
- [Search Logic](#search-logic)
- [Comments & Security](#comments--security)
- [Known Limitations (NewsAPI)](#known-limitations-newsapi)
- [Troubleshooting](#troubleshooting)

---

## Requirements
- Docker Desktop (with `docker compose`)
- Composer (for installing PHP dependencies)
- A NewsAPI key: https://newsapi.org/

---

## Quick Start

```bash

# 1) Create .env file
cp .env.example .env
# Add your NEWS_API_KEY and IP_HASH_SECRET

# 2) Install dependencies
composer install

# 3) Start Docker
docker compose up -d --build

# 4) Import database schema
docker exec -i <db-container> mysql -uapp -psecret newsapp < database/schema.sql

# 5) Open in browser
http://localhost:8080/
```


## Environment Variables (.env)

```dotenv
NEWS_API_KEY=your_newsapi_key_here
USER_AGENT=MyNewsApp/1.0

DB_HOST=db
DB_DATABASE=newsapp
DB_USERNAME=app
DB_PASSWORD=secret

# Generate this with:
# php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
IP_HASH_SECRET=your_random_64_char_secret_here
```

After editing `.env`, restart containers:

```bash
docker compose down
docker compose up -d
```

---

## Database & Migrations

Schema located at DatabaseMigration/database.sql creates:

- `articles` (cached API data)
- `sources` (NewsAPI sources)
- `comments` (article comments)

Import via:

```bash
docker exec -i <db-container> mysql -uapp -psecret newsapp < DatabaseMigration/database.sql
```

---

## Composer Packages

- **andreskrey/readability.php** — extracts readable article text

Install:
```bash
composer install
```

---

## Development Commands

```bash
docker compose up -d --build
docker compose down
docker compose logs -f php
docker exec <php-container> printenv NEWS_API_KEY
```

---

## Routes

| Route | Description |
|-------|--------------|
| `/articles` | List latest articles |
| `/article/{id}` | Single article + comments |
| `/search?q=...&source=...&country=...` | Search with filters |

---

## Search Logic

1. Query local database first (FULLTEXT on `title, description, content`).
2. If no/few results → call NewsAPI and upsert results into DB.

Parameters:
- `q` = fulltext query
- `source` = NewsAPI source ID
- `country` = country code (see API limitations)

---

## Comments & Security

- Public comment form (no login)
- Security measures:
  - CSRF token
  - Honeypot field
  - IP hashing (`HMAC-SHA256` using `IP_HASH_SECRET`)
  - Basic rate limit per session
- Comments escaped on display; HTML not allowed by default.

---

## Known Limitations (NewsAPI)

- `content` field is truncated (~200 chars). Full text fetched with Readability.
- `country` only supports `us` for `/top-headlines`.
  - Use `sources` filter or `/everything` endpoint for other regions.

---

## Troubleshooting

**Deprecated warnings (Readability):**  
Suppress in `index.php`:
```php
error_reporting(E_ALL & ~E_DEPRECATED);
```

**“Anonymous requests are not allowed”:**  
Ensure `X-Api-Key` and `User-Agent` are set.

**PDO parameter mismatch:**  
Double-check that all placeholders match in your prepared statements.

---

## License
This project is for evaluation purposes only. External API keys are not included.  
All configuration is handled through `.env`.




## What I would have done if I had more time.

I would have changed the api calls to a cron job instead.
I would also build in more security in the comment part. Maybe limiting posts a bit more
If I had even more time I would probably change it so you had to login to be able to post comments.
Maybe also some form of moderation for the comments (approved, pending, denied)
I would also try to move the comment code out to a seperate file with its own class.
Another thing I would do is to add some more functions to the database class. So you could simply call those
function without going multiple steps for preparing, executing and fetching.
I would also look into the style of the articles and see if I could make it look a bit better. And maybe some form
of moderation for the articles. Since some of the articles might have broken images or might be behind a paywall so the scraper doesn't allways get good results.