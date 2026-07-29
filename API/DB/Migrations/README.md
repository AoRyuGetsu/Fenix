# Migrations

Scripts para poblar y modificar datos de la base de datos `analiticaDB`.

## Diferencia entre SQL/ y Migrations/

| Carpeta | Propósito | Ejecución |
|---------|-----------|-----------|
| `DB/SQL/` | Estructura base (CREATE TABLE, ALTER) | `mysql ... < archivo.sql` |
| `DB/Migrations/` | Datos iniciales y migraciones de datos | vía web (`/migrate.php`) |

Los scripts de Migrations usan las clases y servicios de la aplicación (`UserService`, `RoleAccessControlService`, etc.) para mantener la lógica de negocio centralizada, sin duplicar reglas en SQL directo.

## Ejecución vía web (hosting)

Acceder desde el navegador a:

```
http://tu-dominio.com/migrate.php              → modo incremental
http://tu-dominio.com/migrate.php?confirm=reset → reset completo + migraciones
```

El sistema detecta y ejecuta automáticamente todas las migraciones en orden numérico.

### Modo incremental vs Reset completo

El comportamiento se controla desde el `.env`:

| Variable | Valor | Comportamiento |
|----------|-------|----------------|
| `RESET_DATABASE_ON_MIGRATE` | `false` (default) | **Incremental**: las migraciones verifican existencia antes de crear. No se pierden datos existentes. |
| `RESET_DATABASE_ON_MIGRATE` | `true` | **Reset completo**: ejecuta `000_reset_structure.php` que borra todas las tablas y recrea la estructura desde `DB/SQL/002_create_structure.sql`, luego ejecuta todas las migraciones de datos. |
| `?confirm=reset` en URL | — | Fuerza reset manual (override de la variable `.env`). |

En modo incremental todas las migraciones deben ser **idempotentes** (ver Convenciones).

### Seguridad

- En **desarrollo** (`APP_ENV=development`): se accede sin restricciones.
- En **producción** (`APP_ENV=production`): requiere definir `MIGRATION_TOKEN` en el `.env` y acceder con:
  ```
  http://tu-dominio.com/migrate.php?token=tu_token_secreto
  ```
- Una vez ejecutadas las migraciones en producción, eliminar o bloquear el archivo `public/migrate.php`.

## Ejecución local (desarrollo)

```bash
php public/migrate.php
```

O desde el navegador con el servidor embebido:

```bash
composer serve
# abrir http://localhost:8000/migrate.php
```

## Requisitos

- PHP 8.2+ con extensiones `pdo_mysql` y `json`
- Archivo `.env` configurado con credenciales de base de datos
- Estructura de base de datos creada (`DB/SQL/002_create_structure.sql`)

## Migraciones de Test

El directorio `DB/test/` contiene migraciones que crean datos de prueba (usuarios ficticios, etc.). Estas migraciones **solo se ejecutan** cuando la variable `RUN_TEST_MIGRATIONS=true` está definida en el `.env`. Las migraciones de test siguen el mismo formato y convenciones que las normales.

| Script | Descripción |
|--------|-------------|
| `001_seed_test_users.php` | Crea usuarios de prueba para cada rol excepto administrador: supervisor, entrenador, 5 jugadores, representante. Con persona y rol asignado. |
| `002_seed_basketball_evaluations.php` | Genera 25 evaluaciones (5 jugadores × 5 periodos trimestrales) para el cuestionario "Evaluación Técnica de Jugador". Respuestas coherentes con perfil de cada jugador y progresión temporal. |

### Credenciales de usuarios de test

| Usuario | Contraseña | Rol |
|---------|-----------|-----|
| `supervisor@mail.com` | `pass1234` | supervisor |
| `entrenador@mail.com` | `pass1234` | entrenador |
| `jugador@mail.com` | `pass1234` | jugador (Juan García) |
| `jugador2@mail.com` | `pass1234` | jugador (Pedro Martínez) |
| `jugador3@mail.com` | `pass1234` | jugador (Luis Fernández) |
| `jugador4@mail.com` | `pass1234` | jugador (Diego Ramírez) |
| `jugadora5@mail.com` | `pass1234` | jugador (Ana Torres) |
| `representante@mail.com` | `pass1234` | representante |

## Convenciones

- Numeración: `001_`, `002_`, `003_` — secuencia independiente de `DB/SQL/`
- Cada script debe ser **idempotente** (regla R49 en `AGENT_CONTEXT.md`): ejecutable múltiples veces sin generar duplicados ni registros huérfanos. Verificar existencia por identificador único natural antes de INSERT, y verificar que los padres FK existen antes de crear hijos.
- Usar `declare(strict_types=1)`
- Bootstrappear la aplicación con `require_once __DIR__ . '/../../vendor/autoload.php'` y `Env::load(__DIR__ . '/../..')`
- No usar SQL directo para datos — usar los servicios de la aplicación via Container

## Scripts actuales

| Script | Descripción |
|--------|-------------|
| `000_reset_structure.php` | Reset total de la base de datos (drop + recreate estructura desde 002 SQL). Se activa automáticamente con `RESET_DATABASE_ON_MIGRATE=true` o manualmente con `?confirm=reset`. |
| `001_seed_roles_and_admin_user.php` | Crea roles (administrador, entrenador, jugador, supervisor), permisos (read, write, delete), usuario administrador y asigna permisos según rol |
| `002_seed_menus.php` | Crea todos los ítems del menú lateral (Configuración, Roles, Usuarios, Cuestionarios, Evaluaciones, Estadísticas) con sus asignaciones por rol |
| `003_seed_basketball_questionnaire.php` | Crea el cuestionario "Evaluación Técnica de Jugador" con 4 secciones y 19 preguntas tipo selección (1-5). Responder: entrenador, evaluado: jugador |

### Credenciales del usuario administrador

| Usuario | Contraseña |
|---------|-----------|
| `administrador@mail.com` | `adminPass1234` |

## Migraciones Manuales de Esquema SQL

Los siguientes cambios estructurales se aplican directamente sobre la base de datos existente (no via PHP). Se listan aquí para referencia.

### v2 — Person-User pivot table + column standardization

**Step A**: Create new tables with English names and migrate data.

```sql
-- 1. Crear tabla people (reemplaza personas)
CREATE TABLE people (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(20) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) DEFAULT NULL,
    first_surname VARCHAR(100) NOT NULL,
    second_surname VARCHAR(100) DEFAULT NULL,
    document_number VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    updated_by BIGINT UNSIGNED DEFAULT NULL,
    deleted_by BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_people_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_people_updated_by FOREIGN KEY (updated_by) REFERENCES users(id),
    CONSTRAINT fk_people_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- 2. Crear tabla pivote person_user
CREATE TABLE person_user (
    person_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    CONSTRAINT fk_person_user_person FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    CONSTRAINT fk_person_user_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Migrar datos de personas → people
INSERT INTO people (id, type, first_name, middle_name, first_surname, second_surname, document_number, email, phone, address, created_by, updated_by, deleted_by, created_at, updated_at, deleted_at)
SELECT id, tipo, primer_nombre, segundo_nombre, apellido_paterno, apellido_materno, identificacion, email, telefono, direccion, created_by, updated_by, deleted_by, created_at, updated_at, deleted_at FROM personas;

-- 4. Migrar datos existentes de personas.user_id → person_user
INSERT INTO person_user (person_id, user_id)
SELECT id, user_id FROM personas WHERE user_id IS NOT NULL;

-- 5. Eliminar tablas viejas
ALTER TABLE personas DROP FOREIGN KEY fk_personas_created_by;
ALTER TABLE personas DROP FOREIGN KEY fk_personas_updated_by;
ALTER TABLE personas DROP FOREIGN KEY fk_personas_deleted_by;
DROP TABLE persona_user;
DROP TABLE personas;
```
