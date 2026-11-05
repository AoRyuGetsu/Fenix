-- 002_create_structure.sql
-- Estructura completa de la base de datos: todas las tablas del sistema.
-- NO crear nuevos archivos 003+, agregar siempre aquí.

-- Tabla Users
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    updated_by BIGINT UNSIGNED DEFAULT NULL,
    deleted_by BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL
) ENGINE=InnoDB;

-- Tabla Roles
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    code VARCHAR(10) NOT NULL UNIQUE,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    updated_by BIGINT UNSIGNED DEFAULT NULL,
    deleted_by BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL
) ENGINE=InnoDB;

-- Tabla Permissions
CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL
) ENGINE=InnoDB;

-- Tabla pivote user_roles (N:N)
CREATE TABLE user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- Tabla pivote role_permissions (N:N)
CREATE TABLE role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id)
) ENGINE=InnoDB;

-- Foreign keys auto-referenciales de users
ALTER TABLE users ADD CONSTRAINT fk_users_created_by FOREIGN KEY (created_by) REFERENCES users(id);
ALTER TABLE users ADD CONSTRAINT fk_users_updated_by FOREIGN KEY (updated_by) REFERENCES users(id);
ALTER TABLE users ADD CONSTRAINT fk_users_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id);

-- Foreign keys de roles hacia users
ALTER TABLE roles ADD CONSTRAINT fk_roles_created_by FOREIGN KEY (created_by) REFERENCES users(id);
ALTER TABLE roles ADD CONSTRAINT fk_roles_updated_by FOREIGN KEY (updated_by) REFERENCES users(id);
ALTER TABLE roles ADD CONSTRAINT fk_roles_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id);

-- Tabla Menus (jerárquica)
CREATE TABLE menus (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id BIGINT UNSIGNED DEFAULT NULL,
    label VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT NULL,
    route VARCHAR(255) DEFAULT NULL,
    menu_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    updated_by BIGINT UNSIGNED DEFAULT NULL,
    deleted_by BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL
) ENGINE=InnoDB;

-- Tabla pivote menu_role (N:N)
CREATE TABLE menu_role (
    menu_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (menu_id, role_id),
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Foreign keys de menus
ALTER TABLE menus ADD CONSTRAINT fk_menus_parent FOREIGN KEY (parent_id) REFERENCES menus(id);
ALTER TABLE menus ADD CONSTRAINT fk_menus_created_by FOREIGN KEY (created_by) REFERENCES users(id);
ALTER TABLE menus ADD CONSTRAINT fk_menus_updated_by FOREIGN KEY (updated_by) REFERENCES users(id);
ALTER TABLE menus ADD CONSTRAINT fk_menus_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id);

-- Tabla People Type
CREATE TABLE people_type (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    description VARCHAR(100) NOT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    updated_by BIGINT UNSIGNED DEFAULT NULL,
    deleted_by BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_people_type_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_people_type_updated_by FOREIGN KEY (updated_by) REFERENCES users(id),
    CONSTRAINT fk_people_type_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Tabla People
CREATE TABLE people (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    people_type_id BIGINT UNSIGNED NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) DEFAULT NULL,
    first_surname VARCHAR(100) NOT NULL,
    second_surname VARCHAR(100) DEFAULT NULL,
    document_number VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    updated_by BIGINT UNSIGNED DEFAULT NULL,
    deleted_by BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_people_type FOREIGN KEY (people_type_id) REFERENCES people_type(id),
    CONSTRAINT fk_people_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_people_updated_by FOREIGN KEY (updated_by) REFERENCES users(id),
    CONSTRAINT fk_people_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Tabla pivote person_user (relación 0..1 a 0..1)
CREATE TABLE person_user (
    person_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    CONSTRAINT fk_person_user_person FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    CONSTRAINT fk_person_user_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla Questionnaires (cuestionarios)
CREATE TABLE questionnaires (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    responder_role_id BIGINT UNSIGNED NOT NULL,
    target_role_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    updated_by BIGINT UNSIGNED DEFAULT NULL,
    deleted_by BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_q_responder_role FOREIGN KEY (responder_role_id) REFERENCES roles(id),
    CONSTRAINT fk_q_target_role FOREIGN KEY (target_role_id) REFERENCES roles(id),
    CONSTRAINT fk_q_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_q_updated_by FOREIGN KEY (updated_by) REFERENCES users(id),
    CONSTRAINT fk_q_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Tabla Sections (secciones de cuestionarios)
CREATE TABLE questionnaire_sections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    questionnaire_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    section_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_sec_questionnaire FOREIGN KEY (questionnaire_id) REFERENCES questionnaires(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla Questions (preguntas)
CREATE TABLE questions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_id BIGINT UNSIGNED NOT NULL,
    text TEXT NOT NULL,
    type VARCHAR(20) NOT NULL COMMENT 'number|selection',
    question_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_q_section FOREIGN KEY (section_id) REFERENCES questionnaire_sections(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla Question Options (solo para preguntas type=selection)
CREATE TABLE question_options (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id BIGINT UNSIGNED NOT NULL,
    option_text VARCHAR(255) NOT NULL,
    option_order INT NOT NULL DEFAULT 0,
    label VARCHAR(255) DEFAULT NULL,
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_qo_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla Periods (periodos de evaluación)
CREATE TABLE periods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    CONSTRAINT uq_periods_name UNIQUE (name),
    created_by BIGINT UNSIGNED DEFAULT NULL,
    updated_by BIGINT UNSIGNED DEFAULT NULL,
    deleted_by BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_periods_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_periods_updated_by FOREIGN KEY (updated_by) REFERENCES users(id),
    CONSTRAINT fk_periods_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Tabla Evaluations (evaluaciones/respuestas a cuestionarios)
CREATE TABLE evaluations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    questionnaire_id BIGINT UNSIGNED NOT NULL,
    responder_user_id BIGINT UNSIGNED NOT NULL,
    target_user_id BIGINT UNSIGNED NOT NULL,
    period_id BIGINT UNSIGNED NOT NULL,
    observaciones TEXT DEFAULT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    updated_by BIGINT UNSIGNED DEFAULT NULL,
    deleted_by BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_evals_questionnaire FOREIGN KEY (questionnaire_id) REFERENCES questionnaires(id),
    CONSTRAINT fk_evals_responder FOREIGN KEY (responder_user_id) REFERENCES users(id),
    CONSTRAINT fk_evals_target FOREIGN KEY (target_user_id) REFERENCES users(id),
    CONSTRAINT fk_evals_period FOREIGN KEY (period_id) REFERENCES periods(id),
    CONSTRAINT fk_evals_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_evals_updated_by FOREIGN KEY (updated_by) REFERENCES users(id),
    CONSTRAINT fk_evals_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id),
    CONSTRAINT uq_eval_period UNIQUE (questionnaire_id, responder_user_id, target_user_id, period_id)
) ENGINE=InnoDB;

-- Tabla Answers (respuestas individuales a cada pregunta)
CREATE TABLE answers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evaluation_id BIGINT UNSIGNED NOT NULL,
    question_id BIGINT UNSIGNED NOT NULL,
    value TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_answers_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE,
    CONSTRAINT fk_answers_question FOREIGN KEY (question_id) REFERENCES questions(id)
) ENGINE=InnoDB;

-- Tabla Teams (equipos)
CREATE TABLE teams (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NULL,
    trainer_user_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    updated_by BIGINT UNSIGNED DEFAULT NULL,
    deleted_by BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_team_trainer FOREIGN KEY (trainer_user_id) REFERENCES users(id),
    CONSTRAINT fk_team_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_team_updated_by FOREIGN KEY (updated_by) REFERENCES users(id),
    CONSTRAINT fk_team_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla pivote team_players (N:N con UNIQUE en player_user_id)
CREATE TABLE team_players (
    team_id BIGINT UNSIGNED NOT NULL,
    player_user_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (team_id, player_user_id),
    UNIQUE KEY uq_player_team (player_user_id),
    CONSTRAINT fk_tp_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_tp_player FOREIGN KEY (player_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla catálogo Parentescos (parentesco del representante hacia el jugador)
CREATE TABLE parentescos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(100) NOT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    updated_by BIGINT UNSIGNED DEFAULT NULL,
    deleted_by BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_parentescos_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_parentescos_updated_by FOREIGN KEY (updated_by) REFERENCES users(id),
    CONSTRAINT fk_parentescos_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla pivote rep_players (representante → jugador, N:1 con UNIQUE en player y parentesco obligatorio)
CREATE TABLE rep_players (
    rep_user_id BIGINT UNSIGNED NOT NULL,
    player_user_id BIGINT UNSIGNED NOT NULL,
    parentesco_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (rep_user_id, player_user_id),
    UNIQUE KEY uq_rep_player (player_user_id),
    CONSTRAINT fk_rp_rep FOREIGN KEY (rep_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_player FOREIGN KEY (player_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_parentesco FOREIGN KEY (parentesco_id) REFERENCES parentescos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla player_specification (especificación deportiva del jugador, 1:1 con users)
CREATE TABLE player_specification (
    user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    birth_date DATE NOT NULL,
    height_cm DECIMAL(5,2) NOT NULL,
    weight_kg DECIMAL(5,2) NOT NULL,
    gender VARCHAR(20) NOT NULL,
    entry_date DATE NOT NULL,
    medical_conditions TEXT NULL,
    emergency_contact_name VARCHAR(150) NOT NULL,
    emergency_contact_phone VARCHAR(20) NOT NULL,
    emergency_contact_relationship VARCHAR(50) NOT NULL,
    position VARCHAR(50) NULL,
    jersey_number_1 INT NULL,
    jersey_number_2 INT NULL,
    jersey_number_3 INT NULL,
    dominant_foot VARCHAR(20) NULL,
    nationality VARCHAR(100) NULL,
    blood_type VARCHAR(5) NULL,
    allergies TEXT NULL,
    insurance VARCHAR(150) NULL,
    medications TEXT NULL,
    basketball_experience TINYINT(1) NULL,
    previous_club VARCHAR(150) NULL,
    category VARCHAR(20) NULL,
    shirt_size VARCHAR(5) NULL,
    profile_photo_url VARCHAR(500) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_ps_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ps_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_ps_updated_by FOREIGN KEY (updated_by) REFERENCES users(id),
    CONSTRAINT fk_ps_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
