# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

MMI Party Backend - API REST Laravel 11 pour un jeu de cartes a collectionner destine aux etudiants MMI (Metiers du Multimedia et de l'Internet) de l'Universite de Montpellier. Les utilisateurs creent un avatar MMII personnalise, ouvrent des lootboxes quotidiennes et collectionnent des cartes representant etudiants, personnel et objets.

## Development Commands

```bash
# Full dev environment (server + queue + logs + vite)
composer dev

# Individual commands
php artisan serve              # API server
php artisan queue:listen       # Queue worker
php artisan pail               # Real-time logs
npm run dev                    # Vite dev server

# Database
php artisan migrate
php artisan migrate:fresh --seed   # Reset with seeders
php artisan db:seed --class=ProdSeeder  # Production seeder

# Testing
php artisan test               # All tests
php artisan test --filter=ExampleTest  # Single test
vendor/bin/phpunit tests/Unit  # Unit tests only

# Code quality
vendor/bin/pint                # Laravel Pint (code style)

# Cache
php artisan config:clear && php artisan cache:clear
```

## Architecture

### Authentication
- JWT via `php-open-source-saver/jwt-auth`
- Refresh tokens stored in `refresh_tokens` table
- Email verification required (code sent to `um_email`)
- Users must have `@umontpellier.fr` or `@etu.umontpellier.fr` email

### Domain Models (UUID primary keys)

```
User (groupe: mmi1|mmi2|mmi3|staff|student)
  └── Mmii (avatar with shape/background)
  └── CardInstance[] (collection)

CardTemplate (type: student|staff|object, level: 1-3 for students)
  └── CardVersion[] (rarity: common|uncommon|rare|epic|legendary)
       └── CardInstance[]

Lootbox (type: quotidian)
  └── CardInstance[]
```

### Key Services

- **LootboxService**: Generates lootboxes with slot-based drop rates configured in `config/app.php` (`loot_rate`, `lootbox_times`, `lootbox_avaibility`)
- **MMIIService**: Avatar generation/manipulation
- **ShapeValidator/StatsValidator**: Card shape and stats validation per level

### Configuration Files

- `config/mmii.php`: MMII avatar colors (skin, hair, eyes, clothes, etc.)
- `config/app.php`: Loot rates per slot position, lootbox availability times
- `config/jwt.php`: JWT configuration

### API Routes Structure

```
/api/auth/*           # register, login, logout, refresh, password reset
/api/me/*             # User profile, loot, collection
/api/mmii/parts/*     # MMII avatar parts and backgrounds
/api/collection/*     # Card collection (requires email verification)
/api/assets/{path}    # Public file serving
```

### Middleware

- `auth:api`: JWT authentication
- `EnsureEmailIsVerifiedApi`: Requires verified `um_email` for protected routes

## Database Notes

- All models use UUID (`HasUuids` trait)
- Card ordering uses custom scopes with `FIELD()` for rarity/type ordering
- Factories support states: `mmi1()`, `mmi2()`, `mmi3()`, `staff()`, `student()`

## Testing

### Structure
```
tests/
├── Unit/
│   ├── Enums/           # CardRarity, CardTypes, UserGroups
│   └── Services/        # ShapeValidator, StatsValidator
├── Feature/
│   ├── Auth/            # Registration, Login, RefreshToken, EmailVerification
│   ├── Collection/      # Collection access, card viewing
│   ├── Lootbox/         # LootboxService availability logic
│   └── Models/          # User, CardTemplate, CardVersion factories/relations
```

### Commands
```bash
php artisan test                           # All tests
php artisan test tests/Unit                # Unit tests only
php artisan test tests/Feature             # Feature tests only
php artisan test --filter=LoginTest        # Single test class
php artisan test --filter=test_user_can_login  # Single test method
```

### Key Test Patterns
- **RefreshDatabase** trait for Feature tests (isolated DB per test)
- **Carbon::setTestNow()** for time-dependent tests (Lootbox availability)
- **User::factory()->mmi1()** for quick student user creation
- Middleware `EnsureEmailIsVerifiedApi` returns 409 for unverified users
