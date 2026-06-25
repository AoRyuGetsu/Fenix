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

#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'UserCreate',
    properties: [
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'password', type: 'string'),
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
        ], type: 'object'),
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
        new OA\Property(property: 'name', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'RoleCreate',
    properties: [
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
        new OA\Property(property: 'id_number', type: 'string'),
        new OA\Property(property: 'email', type: 'string', nullable: true),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'address', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'PersonCreate',
    required: ['first_name', 'first_surname', 'id_number', 'type'],
    properties: [
        new OA\Property(property: 'create_user', type: 'boolean', default: true),
        new OA\Property(property: 'type', type: 'string'),
        new OA\Property(property: 'first_name', type: 'string'),
        new OA\Property(property: 'middle_name', type: 'string', nullable: true),
        new OA\Property(property: 'first_surname', type: 'string'),
        new OA\Property(property: 'second_surname', type: 'string', nullable: true),
        new OA\Property(property: 'id_number', type: 'string'),
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
        new OA\Property(property: 'id_number', type: 'string'),
        new OA\Property(property: 'email', type: 'string', nullable: true),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'address', type: 'string', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'Error',
    properties: [
        new OA\Property(property: 'error', type: 'string'),
    ]
)]
class OpenApiSpec
{
}
