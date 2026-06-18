# Content Management

A small, **private** full-stack content management app. There is no public
sign-up — user accounts are provisioned by an administrator from the command
line. It ships with a login page and a profile settings page.

## Tech stack

Same stack as the companion `timetable` app:

**Backend**
- PHP 8.4+ / Symfony 8.1
- JWT authentication (`lexik/jwt-authentication-bundle` + `gesdinet/jwt-refresh-token-bundle`)
- Doctrine ORM 3 / DoctrineBundle 3 (SQLite by default, MySQL/Postgres ready)

**Frontend**
- React 19 + TypeScript
- Vite 8
- Tailwind CSS v3
- TanStack Query (data fetching) + Zustand (auth state)

## Project layout

```
backend/    Symfony API (auth + profile endpoints)
frontend/   React + Vite single-page app (login + profile settings)
```

## Getting started

### 1. Backend

```bash
cd backend
composer install

# Generate the JWT key pair (passphrase is set in .env -> JWT_PASSPHRASE)
php bin/console lexik:jwt:generate-keypair --skip-if-exists

# Create the database schema
php bin/console doctrine:migrations:migrate --no-interaction

# Create your first user (no public sign-up exists)
php bin/console app:create-user you@example.com "ChangeMe123" "Your Name" --admin

# Run the API
php -S 127.0.0.1:8000 -t public
```

The API runs at <http://127.0.0.1:8000>.

### 2. Frontend

```bash
cd frontend
npm install
npm run dev
```

The app runs at <http://localhost:5173> and proxies `/api` to the backend
on port 8000 (configured in `vite.config.ts`).

## API endpoints

| Method | Path                    | Description                                  |
| ------ | ----------------------- | -------------------------------------------- |
| POST   | `/api/auth/login`       | Log in with `{ email, password }`, returns JWT + refresh token |
| POST   | `/api/auth/refresh`     | Exchange a refresh token for a new JWT       |
| GET    | `/api/auth/me`          | Current authenticated user                   |
| PATCH  | `/api/profile`          | Update name / email / phone / job title      |
| PUT    | `/api/profile/password` | Change password (requires current password)  |

## Managing users

Because this is a private app, accounts are created via the console:

```bash
# Standard user
php bin/console app:create-user user@example.com "Password123" "Jane Doe"

# Administrator
php bin/console app:create-user admin@example.com "Password123" "Admin" --admin
```

Users can later update their own profile and password from the in-app
**Profile settings** page.
