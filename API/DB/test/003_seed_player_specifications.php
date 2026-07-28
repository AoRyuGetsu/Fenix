<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Core\Env;
use App\Core\Container;

Env::load(__DIR__ . '/../..');

$container = new Container();
$entityManager = $container->get(Doctrine\ORM\EntityManager::class);
$conn = $entityManager->getConnection();

$now = (new \DateTime())->format('Y-m-d H:i:s');

$specs = [
    [
        'email'                         => 'jugador@mail.com',
        'birth_date'                    => '2005-03-15',
        'height_cm'                     => '175.00',
        'weight_kg'                     => '72.00',
        'gender'                        => 'male',
        'entry_date'                    => '2023-01-15',
        'medical_conditions'            => null,
        'emergency_contact_name'        => 'María García',
        'emergency_contact_phone'       => '555-0101',
        'emergency_contact_relationship'=> 'madre',
        'position'                      => 'forward',
        'jersey_number'                 => 9,
        'dominant_foot'                 => 'right',
        'nationality'                   => 'Mexicana',
        'blood_type'                    => 'O+',
        'allergies'                     => null,
        'profile_photo_url'             => null,
        'status'                        => 'active',
    ],
    [
        'email'                         => 'jugador2@mail.com',
        'birth_date'                    => '2006-07-22',
        'height_cm'                     => '182.00',
        'weight_kg'                     => '78.50',
        'gender'                        => 'male',
        'entry_date'                    => '2023-02-01',
        'medical_conditions'            => 'Asma leve controlada',
        'emergency_contact_name'        => 'Laura Martínez',
        'emergency_contact_phone'       => '555-0102',
        'emergency_contact_relationship'=> 'madre',
        'position'                      => 'defender',
        'jersey_number'                 => 4,
        'dominant_foot'                 => 'right',
        'nationality'                   => 'Mexicana',
        'blood_type'                    => 'A+',
        'allergies'                     => 'Polen',
        'profile_photo_url'             => null,
        'status'                        => 'active',
    ],
    [
        'email'                         => 'jugador3@mail.com',
        'birth_date'                    => '2004-11-08',
        'height_cm'                     => '170.00',
        'weight_kg'                     => '68.00',
        'gender'                        => 'male',
        'entry_date'                    => '2022-09-10',
        'medical_conditions'            => null,
        'emergency_contact_name'        => 'Rosa Fernández',
        'emergency_contact_phone'       => '555-0103',
        'emergency_contact_relationship'=> 'madre',
        'position'                      => 'midfielder',
        'jersey_number'                 => 8,
        'dominant_foot'                 => 'left',
        'nationality'                   => 'Colombiana',
        'blood_type'                    => 'B+',
        'allergies'                     => null,
        'profile_photo_url'             => null,
        'status'                        => 'active',
    ],
    [
        'email'                         => 'jugador4@mail.com',
        'birth_date'                    => '2007-01-30',
        'height_cm'                     => '168.00',
        'weight_kg'                     => '65.00',
        'gender'                        => 'male',
        'entry_date'                    => '2024-03-05',
        'medical_conditions'            => null,
        'emergency_contact_name'        => 'Sofía Ramírez',
        'emergency_contact_phone'       => '555-0104',
        'emergency_contact_relationship'=> 'madre',
        'position'                      => 'forward',
        'jersey_number'                 => 11,
        'dominant_foot'                 => 'right',
        'nationality'                   => 'Mexicana',
        'blood_type'                    => 'O-',
        'allergies'                     => 'Mariscos',
        'profile_photo_url'             => null,
        'status'                        => 'active',
    ],
    [
        'email'                         => 'jugadora5@mail.com',
        'birth_date'                    => '2005-06-14',
        'height_cm'                     => '165.00',
        'weight_kg'                     => '60.00',
        'gender'                        => 'female',
        'entry_date'                    => '2023-08-20',
        'medical_conditions'            => null,
        'emergency_contact_name'        => 'Carlos Torres',
        'emergency_contact_phone'       => '555-0105',
        'emergency_contact_relationship'=> 'padre',
        'position'                      => 'defender',
        'jersey_number'                 => 3,
        'dominant_foot'                 => 'left',
        'nationality'                   => 'Mexicana',
        'blood_type'                    => 'AB+',
        'allergies'                     => null,
        'profile_photo_url'             => null,
        'status'                        => 'active',
    ],
];

foreach ($specs as $spec) {
    $userId = $conn->executeQuery(
        "SELECT id FROM users WHERE email = ? AND deleted_at IS NULL",
        [$spec['email']]
    )->fetchOne();

    if ($userId === false) {
        echo "✗ Usuario '{$spec['email']}' no encontrado. Se omite.\n";
        continue;
    }

    $existing = $conn->executeQuery(
        "SELECT user_id FROM player_specification WHERE user_id = ? AND deleted_at IS NULL",
        [(int) $userId]
    )->fetchOne();

    if ($existing !== false) {
        echo "• Especificación para '{$spec['email']}' ya existe.\n";
        continue;
    }

    $conn->executeStatement(
        "INSERT INTO player_specification 
         (user_id, birth_date, height_cm, weight_kg, gender, entry_date, 
          medical_conditions, emergency_contact_name, emergency_contact_phone, 
          emergency_contact_relationship, position, jersey_number, dominant_foot, 
          nationality, blood_type, allergies, profile_photo_url, status,
          created_by, updated_by, created_at, updated_at)
         VALUES 
         (?, ?, ?, ?, ?, ?, 
          ?, ?, ?, 
          ?, ?, ?, 
          ?, ?, ?, ?, ?, ?,
          ?, ?, ?, ?)",
        [
            (int) $userId,
            $spec['birth_date'],
            $spec['height_cm'],
            $spec['weight_kg'],
            $spec['gender'],
            $spec['entry_date'],
            $spec['medical_conditions'],
            $spec['emergency_contact_name'],
            $spec['emergency_contact_phone'],
            $spec['emergency_contact_relationship'],
            $spec['position'],
            $spec['jersey_number'],
            $spec['dominant_foot'],
            $spec['nationality'],
            $spec['blood_type'],
            $spec['allergies'],
            $spec['profile_photo_url'],
            $spec['status'],
            (int) $userId,
            (int) $userId,
            $now,
            $now,
        ]
    );

    echo "✓ Especificación para '{$spec['email']}' creada.\n";
}

echo "\nMigración de test 003 completada.\n";
