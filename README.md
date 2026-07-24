# Finkita Backend API

Backend service for the Finkita application, built on Laravel 11/12 and configured with Laravel Passport for secure OAuth2/JWT authentication.

GitHub Repository: [https://github.com/robyajo/be-finkita](https://github.com/robyajo/be-finkita)

---

## Seeded Users

The database seeder automatically configures three user accounts with different roles and permissions.

| Name | Email | Password | Role | Provider | Avatar |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Super Admin** | `su@finkita.web.id` | `Password123` | `SU` | `CREDENTIALS` | [Adventurer (su)](https://api.dicebear.com/7.x/adventurer/svg?seed=su) |
| **System Admin** | `admin@finkita.web.id` | `Password123` | `ADMIN` | `CREDENTIALS` | [Adventurer (admin)](https://api.dicebear.com/7.x/adventurer/svg?seed=admin) |
| **Regular User** | `user@finkita.web.id` | `Password123` | `USER` | `CREDENTIALS` | [Adventurer (user)](https://api.dicebear.com/7.x/adventurer/svg?seed=user) |

---

## API Documentation

The API endpoints are fully documented using **Scramble** (an OpenAPI documentation generator for Laravel).

- **Local Docs Link**: [http://localhost:8000/docs/api](http://localhost:8000/docs/api)

---

## Features Implemented

1. **Authentication (OAuth2 & JWT)**:
   - Integration with Laravel Passport.
   - Session retrieval, token pairing, and token refresh.
   - Google Sign-in callback mappings under `/api/auth/callback/google`.
   - Credentials sign-up and sign-in handlers.
2. **Administrative Controls**:
   - `EnsureAdmin` middleware restricting routes to `ADMIN` or `SU` roles.
   - Full CRUD endpoints for user management (`/api/admin/users`).
3. **CORS & Credentials Security**:
   - Preflight request filtering mapping headers to allow port `3000` (Next.js dashboard) with credentials enabled.
4. **Interactive Dashboard Integrations**:
   - Enforcing password creation constraints exclusively for the `USER` role when registering or signing in via Google.

---

## Installation & Setup

1. **Clone & install dependencies**:
   ```bash
   git clone https://github.com/robyajo/be-finkita.git
   cd be-finkita
   composer install
   ```

2. **Configure environment**:
   - Duplicate `.env.example` to `.env`.
   - Set database settings, Google client ID/secret, and frontend redirects.

3. **Prepare database & OAuth keys**:
   ```bash
   php artisan migrate:fresh --seed
   ```
   *(Note: The DatabaseSeeder automatically generates the Laravel Passport Personal Access client required for JWT token issuing).*

4. **Run Server**:
   ```bash
   php artisan serve
   ```

---

## Production Deployment Notes

Since the OAuth cryptographic keys are not committed for security, they must be generated on the production server.

1. Navigate to the project directory on your production server.
2. Run the key generation command:
   ```bash
   php artisan passport:keys
   ```
3. Set proper read/write file permissions for the generated keys so they can be read by your web server (e.g. `www-data` or PHP process user):
   ```bash
   chmod 600 storage/oauth-private.key
   chmod 600 storage/oauth-public.key
   ```

