# 04 — Modelo Entidad-Relación (Base de Datos)

Diagrama ER completo mostrando todas las entidades del sistema, sus relaciones y columnas relevantes. Las entidades con `deleted_at` soportan soft-delete. Las tablas pivote implementan relaciones N:N.

```mermaid
erDiagram
    users {
        bigint id PK
        varchar email UK
        varchar password
        tinyint active
        bigint created_by FK
        bigint updated_by FK
        bigint deleted_by FK
        datetime created_at
        datetime updated_at
        datetime deleted_at "Soft-delete"
    }

    roles {
        bigint id PK
        varchar name
        varchar code UK
        bigint created_by FK
        bigint updated_by FK
        bigint deleted_by FK
        datetime created_at
        datetime updated_at
        datetime deleted_at "Soft-delete"
    }

    permissions {
        bigint id PK
        varchar name UK
        datetime created_at
        datetime updated_at
        datetime deleted_at "Soft-delete"
    }

    menus {
        bigint id PK
        bigint parent_id FK "Auto-referencia"
        varchar label
        varchar icon
        varchar route
        int menu_order
        tinyint is_active
        bigint created_by FK
        bigint updated_by FK
        bigint deleted_by FK
        datetime created_at
        datetime updated_at
        datetime deleted_at "Soft-delete"
    }

    people_type {
        bigint id PK
        varchar code UK
        varchar description
        bigint created_by FK
        bigint updated_by FK
        bigint deleted_by FK
        datetime created_at
        datetime updated_at
        datetime deleted_at "Soft-delete"
    }

    people {
        bigint id PK
        bigint people_type_id FK
        varchar first_name
        varchar middle_name
        varchar first_surname
        varchar second_surname
        varchar document_number UK
        varchar email UK
        varchar phone
        text address
        bigint created_by FK
        bigint updated_by FK
        bigint deleted_by FK
        datetime created_at
        datetime updated_at
        datetime deleted_at "Soft-delete"
    }

    person_user {
        bigint person_id PK, FK
        bigint user_id UK, FK
    }

    user_roles {
        bigint user_id PK, FK
        bigint role_id PK, FK
    }

    role_permissions {
        bigint role_id PK, FK
        bigint permission_id PK, FK
    }

    menu_role {
        bigint menu_id PK, FK
        bigint role_id PK, FK
    }

    questionnaires {
        bigint id PK
        varchar title
        text description
        tinyint active
        bigint responder_role_id FK
        bigint target_role_id FK
        bigint created_by FK
        bigint updated_by FK
        bigint deleted_by FK
        datetime created_at
        datetime updated_at
        datetime deleted_at "Soft-delete"
    }

    questionnaire_sections {
        bigint id PK
        bigint questionnaire_id FK
        varchar title
        text description
        int section_order
        datetime created_at
        datetime updated_at
        datetime deleted_at "Soft-delete"
    }

    questions {
        bigint id PK
        bigint section_id FK
        text text
        varchar type "text|number|selection"
        int question_order
        datetime created_at
        datetime updated_at
        datetime deleted_at "Soft-delete"
    }

    question_options {
        bigint id PK
        bigint question_id FK
        varchar option_text
        int option_order
    }

    periods {
        bigint id PK
        varchar name UK
        date start_date
        date end_date
        bigint created_by FK
        bigint updated_by FK
        bigint deleted_by FK
        datetime created_at
        datetime updated_at
        datetime deleted_at "Soft-delete"
    }

    evaluations {
        bigint id PK
        bigint questionnaire_id FK
        bigint responder_user_id FK
        bigint target_user_id FK
        bigint period_id FK
        bigint created_by FK
        bigint updated_by FK
        bigint deleted_by FK
        datetime created_at
        datetime updated_at
        datetime deleted_at "Soft-delete"
    }

    answers {
        bigint id PK
        bigint evaluation_id FK
        bigint question_id FK
        text value
        datetime created_at
        datetime updated_at
        datetime deleted_at "Soft-delete"
    }

    %% Relaciones N:N
    users ||--o{ user_roles : tiene
    roles ||--o{ user_roles : "asignado a"
    roles ||--o{ role_permissions : tiene
    permissions ||--o{ role_permissions : "asignado a"
    menus ||--o{ menu_role : visible_por
    roles ||--o{ menu_role : tiene

    %% Relaciones 1:N
    users ||--o{ users : "auditoría (created_by/updated_by/deleted_by)"
    menus ||--o{ menus : "parent_id (jerarquía)"
    people_type ||--o{ people : clasifica
    people ||--|| person_user : "una persona"
    users ||--|| person_user : "un usuario"

    %% Cuestionarios
    questionnaires ||--o{ questionnaire_sections : contiene
    questionnaire_sections ||--o{ questions : contiene
    questions ||--o{ question_options : "opciones (solo type=selection)"
    roles ||--o{ questionnaires : "responder_role_id"
    roles ||--o{ questionnaires : "target_role_id"

    %% Evaluaciones
    questionnaires ||--o{ evaluations : evaluado
    users ||--o{ evaluations : "responder_user_id"
    users ||--o{ evaluations : "target_user_id"
    periods ||--o{ evaluations : "periodo"
    evaluations ||--o{ answers : contiene
    questions ||--o{ answers : respondida
```

## Entidades con Soft-Delete

| Entidad | Columna `deleted_at` | Excepción |
|---|---|---|
| `users` | Sí | — |
| `roles` | Sí | — |
| `permissions` | Sí | — |
| `menus` | Sí | — |
| `people` | Sí | — |
| `people_type` | Sí | — |
| `periods` | Sí | — |
| `questionnaires` | Sí | — |
| `questionnaire_sections` | Sí | — |
| `questions` | Sí | — |
| `evaluations` | Sí | — |
| `answers` | Sí | — |
| `question_options` | **No** | Entidad hija con `orphanRemoval: true`; se elimina físicamente al eliminar la pregunta padre |

## Notas

- **Auditoría**: `users`, `roles`, `menus`, `people`, `people_type`, `questionnaires`, `periods` y `evaluations` tienen FK `created_by`, `updated_by`, `deleted_by` auto-referenciales a `users.id`
- **Menús jerárquicos**: `menus.parent_id` FK auto-referencial para estructura de árbol
- **Tablas pivote N:N**: `user_roles`, `role_permissions`, `menu_role`, `person_user` (0..1 a 0..1)
- **Unique constraint**: `uq_eval_period` en `evaluations` (questionnaire_id, responder_user_id, target_user_id, period_id)
