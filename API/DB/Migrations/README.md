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
http://tu-dominio.com/migrate.php
```

El sistema detecta y ejecuta automáticamente todas las migraciones pendientes en orden numérico.

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

- PHP 8.3+ con extensiones `pdo_mysql` y `json`
- Archivo `.env` configurado con credenciales de base de datos
- Estructura de base de datos creada (`DB/SQL/002_create_structure.sql`)

## Convenciones

- Numeración: `001_`, `002_`, `003_` — secuencia independiente de `DB/SQL/`
- Cada script debe ser **idempotente** (ejecutable múltiples veces sin errores)
- Usar `declare(strict_types=1)`
- Bootstrappear la aplicación con `require_once __DIR__ . '/../../vendor/autoload.php'` y `Env::load(__DIR__ . '/../..')`
- No usar SQL directo para datos — usar los servicios de la aplicación via Container

## Scripts actuales

| Script | Descripción |
|--------|-------------|
| `001_seed_roles_and_admin_user.php` | Crea roles (administrador, entrenador, jugador, supervisor), permisos (read, write, delete), usuario administrador y asigna permisos según rol |

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
    id_number VARCHAR(50) NOT NULL UNIQUE,
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
INSERT INTO people (id, type, first_name, middle_name, first_surname, second_surname, id_number, email, phone, address, created_by, updated_by, deleted_by, created_at, updated_at, deleted_at)
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
