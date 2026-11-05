<?php

declare(strict_types=1);

namespace App\Core\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Analítica API',
    description: 'API REST modular para análisis de datos. Arquitectura Feature-based con dominios aislados.',
)]
#[OA\Server(url: 'http://localhost:8000', description: 'Servidor de desarrollo')]
#[OA\Tag(name: 'Health', description: 'Monitoreo del estado de la API')]
#[OA\Tag(name: 'Users', description: 'Gestión de usuarios')]
#[OA\Tag(name: 'Auth', description: 'Autenticación y autorización')]
#[OA\Tag(name: 'Roles', description: 'Gestión de roles')]
#[OA\Tag(name: 'Permissions', description: 'Gestión de permisos')]
#[OA\Tag(name: 'User Roles', description: 'Asignación de roles a usuarios')]
#[OA\Tag(name: 'Menus', description: 'Gestión de menús')]
#[OA\Tag(name: 'People', description: 'People management')]
#[OA\Tag(name: 'Parentescos', description: 'Catálogo de parentescos')]
#[OA\Tag(name: 'Evaluation', description: 'Evaluaciones y respuestas')]
#[OA\Tag(name: 'Periods', description: 'Periodos de evaluación')]
#[OA\Tag(name: 'Teams', description: 'Gestión de equipos')]
#[OA\Tag(name: 'Dashboard', description: 'Dashboard y estadísticas')]
#[OA\Tag(name: 'PlayerSpecification', description: 'Especificaciones deportivas de jugadores')]
#[OA\Tag(name: 'EvaluationStats', description: 'Análisis estadístico de evaluaciones de jugadores')]

#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'active', type: 'boolean'),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'team', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
        ], type: 'object', nullable: true),
        new OA\Property(property: 'representative', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'display_name', type: 'string'),
            new OA\Property(property: 'parentesco', ref: '#/components/schemas/Parentesco', nullable: true),
        ], type: 'object', nullable: true),
        new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'UserCreate',
    properties: [
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'password', type: 'string'),
        new OA\Property(property: 'person', ref: '#/components/schemas/PersonCreate'),
        new OA\Property(property: 'team_id', type: 'integer', nullable: true),
        new OA\Property(property: 'representative_id', type: 'integer', nullable: true),
        new OA\Property(property: 'parentesco_id', type: 'integer', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'Parentesco',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'code', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'PlayerSummary',
    description: 'Resumen de un jugador para listados',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'display_name', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'LoginRequest',
    properties: [
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'password', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'RefreshTokenRequest',
    properties: [
        new OA\Property(property: 'refresh_token', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'MenuItem',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'label', type: 'string'),
        new OA\Property(property: 'icon', type: 'string', nullable: true),
        new OA\Property(property: 'route', type: 'string', nullable: true),
        new OA\Property(property: 'parent_id', type: 'integer', nullable: true),
        new OA\Property(property: 'menu_order', type: 'integer'),
    ]
)]
#[OA\Schema(
    schema: 'AuthResponse',
    properties: [
        new OA\Property(property: 'access_token', type: 'string'),
        new OA\Property(property: 'refresh_token', type: 'string'),
        new OA\Property(property: 'expires_in', type: 'integer'),
        new OA\Property(property: 'user', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'email', type: 'string'),
            new OA\Property(property: 'person', nullable: true, properties: [
                new OA\Property(property: 'full_name', type: 'string'),
            ], type: 'object'),
        ], type: 'object'),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'menus', type: 'array', items: new OA\Items(ref: '#/components/schemas/MenuItem')),
    ]
)]
#[OA\Schema(
    schema: 'MeResponse',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'menus', type: 'array', items: new OA\Items(ref: '#/components/schemas/MenuItem')),
    ]
)]
#[OA\Schema(
    schema: 'Role',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'code', type: 'string'),
        new OA\Property(property: 'name', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'RoleCreate',
    properties: [
        new OA\Property(property: 'code', type: 'string'),
        new OA\Property(property: 'name', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'Permission',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'PermissionCreate',
    properties: [
        new OA\Property(property: 'name', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'RolesPermissionsResponse',
    properties: [
        new OA\Property(
            property: 'permissions',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Permission')
        ),
        new OA\Property(
            property: 'roles',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'code', type: 'string'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(
                        property: 'permissions',
                        type: 'array',
                        items: new OA\Items(type: 'string')
                    ),
                ],
                type: 'object'
            )
        ),
    ]
)]
#[OA\Schema(
    schema: 'AssignPermissionsRequest',
    properties: [
        new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string')),
    ]
)]
#[OA\Schema(
    schema: 'AssignRoleRequest',
    properties: [
        new OA\Property(property: 'role', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'Person',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'user_id', type: 'integer', nullable: true),
        new OA\Property(property: 'create_user', type: 'boolean', default: true),
        new OA\Property(property: 'type', type: 'string'),
        new OA\Property(property: 'first_name', type: 'string'),
        new OA\Property(property: 'middle_name', type: 'string', nullable: true),
        new OA\Property(property: 'first_surname', type: 'string'),
        new OA\Property(property: 'second_surname', type: 'string', nullable: true),
        new OA\Property(property: 'document_number', type: 'string'),
        new OA\Property(property: 'email', type: 'string', nullable: true),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'address', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'PersonCreate',
    required: ['first_name', 'first_surname', 'document_number', 'type'],
    properties: [
        new OA\Property(property: 'create_user', type: 'boolean', default: true),
        new OA\Property(property: 'type', type: 'string'),
        new OA\Property(property: 'first_name', type: 'string'),
        new OA\Property(property: 'middle_name', type: 'string', nullable: true),
        new OA\Property(property: 'first_surname', type: 'string'),
        new OA\Property(property: 'second_surname', type: 'string', nullable: true),
        new OA\Property(property: 'document_number', type: 'string'),
        new OA\Property(property: 'email', type: 'string', nullable: true),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'address', type: 'string', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'PersonUpdate',
    properties: [
        new OA\Property(property: 'type', type: 'string'),
        new OA\Property(property: 'first_name', type: 'string'),
        new OA\Property(property: 'middle_name', type: 'string', nullable: true),
        new OA\Property(property: 'first_surname', type: 'string'),
        new OA\Property(property: 'second_surname', type: 'string', nullable: true),
        new OA\Property(property: 'document_number', type: 'string'),
        new OA\Property(property: 'email', type: 'string', nullable: true),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'address', type: 'string', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'PaginatedResponse',
    description: 'Respuesta paginada genérica. Los campos data, total, page, perPage varían según el endpoint.',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'total', type: 'integer'),
        new OA\Property(property: 'page', type: 'integer'),
        new OA\Property(property: 'perPage', type: 'integer'),
    ]
)]
#[OA\Schema(
    schema: 'RepresentedPlayer',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'display_name', type: 'string'),
        new OA\Property(property: 'person', nullable: true, properties: [
            new OA\Property(property: 'first_name', type: 'string'),
            new OA\Property(property: 'middle_name', type: 'string', nullable: true),
            new OA\Property(property: 'first_surname', type: 'string'),
            new OA\Property(property: 'second_surname', type: 'string', nullable: true),
            new OA\Property(property: 'document_number', type: 'string'),
            new OA\Property(property: 'email', type: 'string', nullable: true),
            new OA\Property(property: 'phone', type: 'string', nullable: true),
            new OA\Property(property: 'address', type: 'string', nullable: true),
        ], type: 'object'),
        new OA\Property(property: 'parentesco', ref: '#/components/schemas/Parentesco'),
        new OA\Property(property: 'player_spec', ref: '#/components/schemas/RepresentedPlayerSpec', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'RepresentedPlayerSpec',
    properties: [
        new OA\Property(property: 'birth_date', type: 'string', format: 'date'),
        new OA\Property(property: 'height_cm', type: 'number', format: 'float'),
        new OA\Property(property: 'weight_kg', type: 'number', format: 'float'),
        new OA\Property(property: 'gender', type: 'string'),
        new OA\Property(property: 'entry_date', type: 'string', format: 'date'),
        new OA\Property(property: 'medical_conditions', type: 'string', nullable: true),
        new OA\Property(property: 'emergency_contact_name', type: 'string', nullable: true),
        new OA\Property(property: 'emergency_contact_phone', type: 'string', nullable: true),
        new OA\Property(property: 'emergency_contact_relationship', type: 'string', nullable: true),
        new OA\Property(property: 'position', type: 'string', nullable: true),
        new OA\Property(property: 'jersey_number_1', type: 'integer', nullable: true),
        new OA\Property(property: 'jersey_number_2', type: 'integer', nullable: true),
        new OA\Property(property: 'jersey_number_3', type: 'integer', nullable: true),
        new OA\Property(property: 'dominant_foot', type: 'string', nullable: true),
        new OA\Property(property: 'nationality', type: 'string', nullable: true),
        new OA\Property(property: 'blood_type', type: 'string', nullable: true),
        new OA\Property(property: 'allergies', type: 'string', nullable: true),
        new OA\Property(property: 'insurance', type: 'string', nullable: true),
        new OA\Property(property: 'medications', type: 'string', nullable: true),
        new OA\Property(property: 'basketball_experience', type: 'boolean', nullable: true),
        new OA\Property(property: 'previous_club', type: 'string', nullable: true),
        new OA\Property(property: 'category', type: 'string', nullable: true),
        new OA\Property(property: 'shirt_size', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'RepresentedPlayerResponse',
    description: 'Respuesta paginada de jugadores representados por un representante.',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/RepresentedPlayer')),
        new OA\Property(property: 'total', type: 'integer'),
        new OA\Property(property: 'page', type: 'integer'),
        new OA\Property(property: 'perPage', type: 'integer'),
    ]
)]
#[OA\Schema(
    schema: 'PeopleType',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'code', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'Error',
    properties: [
        new OA\Property(property: 'error', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'Questionnaire',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'active', type: 'boolean'),
        new OA\Property(property: 'responder_role_id', type: 'integer'),
        new OA\Property(property: 'responder_role', type: 'string'),
        new OA\Property(property: 'target_role_id', type: 'integer'),
        new OA\Property(property: 'target_role', type: 'string'),
        new OA\Property(property: 'sections', type: 'array', items: new OA\Items(ref: '#/components/schemas/Section')),
        new OA\Property(property: 'questions_count', type: 'integer'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Section',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'section_order', type: 'integer'),
        new OA\Property(property: 'questions', type: 'array', items: new OA\Items(ref: '#/components/schemas/Question')),
        new OA\Property(property: 'questions_count', type: 'integer'),
    ]
)]
#[OA\Schema(
    schema: 'Question',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'text', type: 'string'),
        new OA\Property(property: 'type', type: 'string'),
        new OA\Property(property: 'question_order', type: 'integer'),
        new OA\Property(property: 'options', type: 'array', items: new OA\Items(ref: '#/components/schemas/QuestionOption'), nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'QuestionOption',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'option_text', type: 'string'),
        new OA\Property(property: 'option_order', type: 'integer'),
        new OA\Property(property: 'label', type: 'string', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'Period',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'start_date', type: 'string', format: 'date'),
        new OA\Property(property: 'end_date', type: 'string', format: 'date'),
    ]
)]
#[OA\Schema(
    schema: 'PeriodCreate',
    required: ['name', 'start_date', 'end_date'],
    properties: [
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'start_date', type: 'string', format: 'date'),
        new OA\Property(property: 'end_date', type: 'string', format: 'date'),
    ]
)]
#[OA\Schema(
    schema: 'PeriodUpdate',
    required: ['name', 'start_date', 'end_date'],
    properties: [
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'start_date', type: 'string', format: 'date'),
        new OA\Property(property: 'end_date', type: 'string', format: 'date'),
    ]
)]

#[OA\Schema(
    schema: 'Evaluation',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'questionnaire_id', type: 'integer'),
        new OA\Property(property: 'questionnaire_title', type: 'string'),
        new OA\Property(property: 'period_id', type: 'integer'),
        new OA\Property(property: 'period_name', type: 'string'),
        new OA\Property(property: 'responder_user_id', type: 'integer'),
        new OA\Property(property: 'responder_email', type: 'string'),
        new OA\Property(property: 'target_user_id', type: 'integer'),
        new OA\Property(property: 'target_email', type: 'string'),
        new OA\Property(property: 'answers', type: 'array', items: new OA\Items(ref: '#/components/schemas/Answer')),
        new OA\Property(property: 'observaciones', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Answer',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'question_id', type: 'integer'),
        new OA\Property(property: 'question_text', type: 'string'),
        new OA\Property(property: 'question_type', type: 'string'),
        new OA\Property(property: 'value', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'EvaluationCreate',
    properties: [
        new OA\Property(property: 'questionnaire_id', type: 'integer'),
        new OA\Property(property: 'target_user_id', type: 'integer'),
        new OA\Property(property: 'period_id', type: 'integer'),
        new OA\Property(property: 'observaciones', type: 'string', nullable: true),
        new OA\Property(property: 'answers', type: 'array', items: new OA\Items(
            properties: [
                new OA\Property(property: 'question_id', type: 'integer'),
                new OA\Property(property: 'value', type: 'string'),
            ],
            type: 'object'
        )),
    ]
)]
#[OA\Schema(
    schema: 'DashboardStats',
    properties: [
        new OA\Property(property: 'questionnaires_total', type: 'integer'),
        new OA\Property(
            property: 'users_by_role',
            type: 'object',
            properties: [
                new OA\Property(property: 'ADM', type: 'integer'),
                new OA\Property(property: 'SUP', type: 'integer'),
                new OA\Property(property: 'ENT', type: 'integer'),
                new OA\Property(property: 'JUG', type: 'integer'),
                new OA\Property(property: 'REP', type: 'integer'),
            ],
            additionalProperties: false,
        ),
        new OA\Property(property: 'my_evaluations_total', type: 'integer'),
        new OA\Property(
            property: 'evaluations_by_period',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'period_id', type: 'integer'),
                    new OA\Property(property: 'period_name', type: 'string'),
                    new OA\Property(property: 'total', type: 'integer'),
                ],
                type: 'object'
            ),
        ),
    ]
)]
#[OA\Schema(
    schema: 'Team',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'code', type: 'string'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'trainer', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'email', type: 'string'),
            new OA\Property(property: 'display_name', type: 'string'),
        ], type: 'object'),
        new OA\Property(property: 'players', type: 'array', items: new OA\Items(
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'email', type: 'string'),
                new OA\Property(property: 'display_name', type: 'string'),
            ],
            type: 'object'
        )),
        new OA\Property(property: 'player_count', type: 'integer'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'TeamCreate',
    required: ['name', 'code', 'trainer_user_id'],
    properties: [
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'code', type: 'string'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'trainer_user_id', type: 'integer'),
        new OA\Property(property: 'player_ids', type: 'array', items: new OA\Items(type: 'integer')),
    ]
)]
#[OA\Schema(
    schema: 'PlayerSpecification',
    properties: [
        new OA\Property(property: 'user_id', type: 'integer'),
        new OA\Property(property: 'user_email', type: 'string'),
        new OA\Property(property: 'birth_date', type: 'string', format: 'date'),
        new OA\Property(property: 'height_cm', type: 'number', format: 'float'),
        new OA\Property(property: 'weight_kg', type: 'number', format: 'float'),
        new OA\Property(property: 'gender', type: 'string'),
        new OA\Property(property: 'entry_date', type: 'string', format: 'date'),
        new OA\Property(property: 'medical_conditions', type: 'string', nullable: true),
        new OA\Property(property: 'emergency_contact_name', type: 'string'),
        new OA\Property(property: 'emergency_contact_phone', type: 'string'),
        new OA\Property(property: 'emergency_contact_relationship', type: 'string'),
        new OA\Property(property: 'position', type: 'string', nullable: true),
        new OA\Property(property: 'jersey_number_1', type: 'integer', nullable: true),
        new OA\Property(property: 'jersey_number_2', type: 'integer', nullable: true),
        new OA\Property(property: 'jersey_number_3', type: 'integer', nullable: true),
        new OA\Property(property: 'dominant_foot', type: 'string', nullable: true),
        new OA\Property(property: 'nationality', type: 'string', nullable: true),
        new OA\Property(property: 'blood_type', type: 'string', nullable: true),
        new OA\Property(property: 'allergies', type: 'string', nullable: true),
        new OA\Property(property: 'insurance', type: 'string', nullable: true),
        new OA\Property(property: 'medications', type: 'string', nullable: true),
        new OA\Property(property: 'basketball_experience', type: 'boolean', nullable: true),
        new OA\Property(property: 'previous_club', type: 'string', nullable: true),
        new OA\Property(property: 'category', type: 'string', nullable: true),
        new OA\Property(property: 'shirt_size', type: 'string', nullable: true),
        new OA\Property(property: 'profile_photo_url', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'PlayerSpecificationCreate',
    required: ['user_id', 'birth_date', 'height_cm', 'weight_kg', 'gender', 'entry_date', 'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship'],
    properties: [
        new OA\Property(property: 'user_id', type: 'integer'),
        new OA\Property(property: 'birth_date', type: 'string', format: 'date'),
        new OA\Property(property: 'height_cm', type: 'number', format: 'float'),
        new OA\Property(property: 'weight_kg', type: 'number', format: 'float'),
        new OA\Property(property: 'gender', type: 'string'),
        new OA\Property(property: 'entry_date', type: 'string', format: 'date'),
        new OA\Property(property: 'medical_conditions', type: 'string', nullable: true),
        new OA\Property(property: 'emergency_contact_name', type: 'string'),
        new OA\Property(property: 'emergency_contact_phone', type: 'string'),
        new OA\Property(property: 'emergency_contact_relationship', type: 'string'),
        new OA\Property(property: 'position', type: 'string', nullable: true),
        new OA\Property(property: 'jersey_number_1', type: 'integer', nullable: true),
        new OA\Property(property: 'jersey_number_2', type: 'integer', nullable: true),
        new OA\Property(property: 'jersey_number_3', type: 'integer', nullable: true),
        new OA\Property(property: 'dominant_foot', type: 'string', nullable: true),
        new OA\Property(property: 'nationality', type: 'string', nullable: true),
        new OA\Property(property: 'blood_type', type: 'string', nullable: true),
        new OA\Property(property: 'allergies', type: 'string', nullable: true),
        new OA\Property(property: 'insurance', type: 'string', nullable: true),
        new OA\Property(property: 'medications', type: 'string', nullable: true),
        new OA\Property(property: 'basketball_experience', type: 'boolean', nullable: true),
        new OA\Property(property: 'previous_club', type: 'string', nullable: true),
        new OA\Property(property: 'category', type: 'string', nullable: true),
        new OA\Property(property: 'shirt_size', type: 'string', nullable: true),
        new OA\Property(property: 'profile_photo_url', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'PlayerSpecificationUpdate',
    properties: [
        new OA\Property(property: 'birth_date', type: 'string', format: 'date'),
        new OA\Property(property: 'height_cm', type: 'number', format: 'float'),
        new OA\Property(property: 'weight_kg', type: 'number', format: 'float'),
        new OA\Property(property: 'gender', type: 'string'),
        new OA\Property(property: 'entry_date', type: 'string', format: 'date'),
        new OA\Property(property: 'medical_conditions', type: 'string', nullable: true),
        new OA\Property(property: 'emergency_contact_name', type: 'string'),
        new OA\Property(property: 'emergency_contact_phone', type: 'string'),
        new OA\Property(property: 'emergency_contact_relationship', type: 'string'),
        new OA\Property(property: 'position', type: 'string', nullable: true),
        new OA\Property(property: 'jersey_number_1', type: 'integer', nullable: true),
        new OA\Property(property: 'jersey_number_2', type: 'integer', nullable: true),
        new OA\Property(property: 'jersey_number_3', type: 'integer', nullable: true),
        new OA\Property(property: 'dominant_foot', type: 'string', nullable: true),
        new OA\Property(property: 'nationality', type: 'string', nullable: true),
        new OA\Property(property: 'blood_type', type: 'string', nullable: true),
        new OA\Property(property: 'allergies', type: 'string', nullable: true),
        new OA\Property(property: 'insurance', type: 'string', nullable: true),
        new OA\Property(property: 'medications', type: 'string', nullable: true),
        new OA\Property(property: 'basketball_experience', type: 'boolean', nullable: true),
        new OA\Property(property: 'previous_club', type: 'string', nullable: true),
        new OA\Property(property: 'category', type: 'string', nullable: true),
        new OA\Property(property: 'shirt_size', type: 'string', nullable: true),
        new OA\Property(property: 'profile_photo_url', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'EvaluationStatsResponse',
    properties: [
        new OA\Property(property: 'target_user', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'display_name', type: 'string'),
            new OA\Property(property: 'email', type: 'string', nullable: true),
            new OA\Property(property: 'birth_date', type: 'string', format: 'date', nullable: true),
            new OA\Property(property: 'height_cm', type: 'number', format: 'float', nullable: true),
            new OA\Property(property: 'weight_kg', type: 'number', format: 'float', nullable: true),
            new OA\Property(property: 'team', type: 'string', nullable: true),
        ], type: 'object'),
        new OA\Property(property: 'periods', type: 'array', items: new OA\Items(ref: '#/components/schemas/StatsPeriod')),
        new OA\Property(property: 'questionnaires', type: 'array', items: new OA\Items(ref: '#/components/schemas/QuestionnaireStats')),
    ]
)]
#[OA\Schema(
    schema: 'StatsPeriod',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'index', type: 'integer'),
    ]
)]
#[OA\Schema(
    schema: 'QuestionnaireStats',
    properties: [
        new OA\Property(property: 'questionnaire', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'title', type: 'string'),
        ], type: 'object'),
        new OA\Property(property: 'per_question', type: 'array', items: new OA\Items(ref: '#/components/schemas/PerQuestionStats')),
        new OA\Property(property: 'overall', ref: '#/components/schemas/OverallStats'),
        new OA\Property(property: 'last_observation', type: 'string', nullable: true),
        new OA\Property(property: 'last_observation_at', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'last_evaluator', type: 'string', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'PerQuestionStats',
    properties: [
        new OA\Property(property: 'question_id', type: 'integer'),
        new OA\Property(property: 'question_text', type: 'string'),
        new OA\Property(property: 'question_type', type: 'string'),
        new OA\Property(property: 'averages', type: 'array', items: new OA\Items(ref: '#/components/schemas/PerPeriodAverage')),
        new OA\Property(property: 'observations', type: 'array', items: new OA\Items(ref: '#/components/schemas/Observation')),
        new OA\Property(property: 'regression', ref: '#/components/schemas/RegressionResult', nullable: true),
        new OA\Property(property: 'historial_regression', ref: '#/components/schemas/RegressionResult', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'PerPeriodAverage',
    properties: [
        new OA\Property(property: 'period_id', type: 'integer'),
        new OA\Property(property: 'period_name', type: 'string'),
        new OA\Property(property: 'index', type: 'integer'),
        new OA\Property(property: 'average', type: 'number', format: 'float'),
        new OA\Property(property: 'count', type: 'integer'),
    ]
)]
#[OA\Schema(
    schema: 'OverallStats',
    properties: [
        new OA\Property(property: 'averages', type: 'array', items: new OA\Items(ref: '#/components/schemas/PerPeriodAverage')),
        new OA\Property(property: 'observations', type: 'array', items: new OA\Items(ref: '#/components/schemas/Observation')),
        new OA\Property(property: 'regression', ref: '#/components/schemas/RegressionResult', nullable: true),
        new OA\Property(property: 'historial_regression', ref: '#/components/schemas/RegressionResult', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'RegressionResult',
    properties: [
        new OA\Property(property: 'slope', type: 'number', format: 'float'),
        new OA\Property(property: 'intercept', type: 'number', format: 'float'),
        new OA\Property(property: 'r_squared', type: 'number', format: 'float'),
        new OA\Property(property: 'trend', type: 'string', enum: ['upward', 'downward', 'stable']),
        new OA\Property(property: 'sse', type: 'number', format: 'float', description: 'Suma de cuadrados residual (margen de error ε)'),
        new OA\Property(property: 'std_error_slope', type: 'number', format: 'float'),
        new OA\Property(property: 'std_error_intercept', type: 'number', format: 'float'),
        new OA\Property(property: 'n', type: 'integer'),
    ]
)]
#[OA\Schema(
    schema: 'Observation',
    properties: [
        new OA\Property(property: 'evaluated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'x_days', type: 'number', format: 'float'),
        new OA\Property(property: 'value', type: 'number', format: 'float'),
        new OA\Property(property: 'fitted', type: 'number', format: 'float'),
        new OA\Property(property: 'residual', type: 'number', format: 'float'),
    ]
)]
#[OA\Schema(
    schema: 'TeamStatsResponse',
    properties: [
        new OA\Property(property: 'team', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
        ], type: 'object'),
        new OA\Property(property: 'periods', type: 'array', items: new OA\Items(ref: '#/components/schemas/StatsPeriod')),
        new OA\Property(property: 'trend_summary', properties: [
            new OA\Property(property: 'upward', type: 'integer'),
            new OA\Property(property: 'downward', type: 'integer'),
            new OA\Property(property: 'stable', type: 'integer'),
        ], type: 'object'),
        new OA\Property(property: 'questionnaires', type: 'array', items: new OA\Items(
            properties: [
                new OA\Property(property: 'questionnaire', properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'title', type: 'string'),
                ], type: 'object'),
                new OA\Property(property: 'team_averages', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'period_id', type: 'integer'),
                        new OA\Property(property: 'period_name', type: 'string'),
                        new OA\Property(property: 'average', type: 'number', format: 'float'),
                    ],
                    type: 'object'
                )),
                new OA\Property(property: 'team_regression', ref: '#/components/schemas/RegressionResult', nullable: true),
                new OA\Property(property: 'players', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'user', properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'display_name', type: 'string'),
                        ], type: 'object'),
                        new OA\Property(property: 'overall_averages', type: 'array', items: new OA\Items(ref: '#/components/schemas/PerPeriodAverage')),
                        new OA\Property(property: 'regression', ref: '#/components/schemas/RegressionResult', nullable: true),
                    ],
                    type: 'object'
                )),
            ],
            type: 'object'
        )),
    ]
)]
class OpenApiSpec
{
}
