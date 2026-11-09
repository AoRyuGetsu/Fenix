<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Core\Env;
use App\Core\Container;
use Doctrine\ORM\EntityManager;

Env::load(__DIR__ . '/../..');

$container = new Container();
$entityManager = $container->get(EntityManager::class);
$conn = $entityManager->getConnection();

// ── Buscar cuestionario ──
$qId = $conn->executeQuery(
    "SELECT id FROM questionnaires WHERE title = 'Evaluación Técnica de Jugador' AND deleted_at IS NULL"
)->fetchOne();

if ($qId === false) {
    echo "✗ Cuestionario 'Evaluación Técnica de Jugador' no encontrado. Ejecuta primero 003_seed_basketball_questionnaire.\n";
    exit(1);
}
$qId = (int) $qId;

// ── Buscar preguntas del cuestionario (a través de secciones) ──
$questionRows = $conn->executeQuery(
    "SELECT q.id, q.text, q.question_order
     FROM questions q
     JOIN questionnaire_sections s ON q.section_id = s.id
     WHERE s.questionnaire_id = ?
       AND q.deleted_at IS NULL
       AND s.deleted_at IS NULL
     ORDER BY s.section_order, q.question_order",
    [$qId]
)->fetchAllAssociative();

if (count($questionRows) !== 19) {
    echo "✗ Se esperaban 19 preguntas, se encontraron " . count($questionRows) . ".\n";
    exit(1);
}

$questionIds = array_map(fn($r) => (int) $r['id'], $questionRows);
$now = new \DateTime();

// ── Buscar entrenador (responder) y jugadores (evaluados) ──
$entrenadorId = $conn->executeQuery(
    "SELECT u.id FROM users u
     JOIN user_roles ur ON u.id = ur.user_id
     JOIN roles r ON ur.role_id = r.id
     WHERE r.code = 'ENT' AND u.deleted_at IS NULL
     LIMIT 1"
)->fetchOne();

if ($entrenadorId === false) {
    echo "✗ Usuario con rol 'ENT' no encontrado.\n";
    exit(1);
}
$entrenadorId = (int) $entrenadorId;

$jugadores = $conn->executeQuery(
    "SELECT u.id, u.email FROM users u
     JOIN user_roles ur ON u.id = ur.user_id
     JOIN roles r ON ur.role_id = r.id
     WHERE r.code = 'JUG' AND u.deleted_at IS NULL"
)->fetchAllAssociative();

if ($jugadores !== false && count($jugadores) === 0) {
    echo "✗ No se encontraron jugadores con rol 'JUG'.\n";
    exit(1);
}
echo "• Se evaluarán " . count($jugadores) . " jugadores.\n";

// ── Perfiles de jugadores (coherencia) ──
// Cada perfil define base inicial, mejora (puede ser negativa) y variabilidad
// por periodo para cada grupo de preguntas.
// Grupos: ofensivo (0-4), defensivo (5-9), físico (10-13), mental (14-18)
// - mejora > 0  → tendencia creciente (progresión)
// - mejora < 0  → tendencia decreciente (regresión/inconsistencia)
// - mejora ≈ 0  → meseta (jugador estable con poco margen de mejora)
// - variabilidad (desviación estándar) más alta → respuestas más dispersas
$perfiles = [
    'jugador@mail.com' => [
        'nombre'       => 'Juan García',
        'bases'        => [3.5, 3.8, 3.2, 3.6],  // ofensivo, defensivo, físico, mental
        'mejora'       => [0.3, 0.2, 0.3, 0.2],  // mejora por periodo (creciente moderada)
        'variabilidad' => [0.5, 0.6, 0.5, 0.6],
    ],
    'jugador2@mail.com' => [
        'nombre'       => 'Pedro Martínez',
        'bases'        => [2.8, 3.0, 3.5, 2.5],
        'mejora'       => [0.5, 0.3, 0.4, 0.4],
        'variabilidad' => [0.7, 0.6, 0.6, 0.8],
    ],
    'jugador3@mail.com' => [
        'nombre'       => 'Luis Fernández',
        'bases'        => [4.5, 4.2, 4.0, 4.3],
        'mejora'       => [0.1, 0.2, 0.3, 0.1],
        'variabilidad' => [0.4, 0.4, 0.5, 0.5],
    ],
    'jugador4@mail.com' => [
        'nombre'       => 'Diego Ramírez',
        'bases'        => [2.5, 2.2, 3.0, 2.0],
        'mejora'       => [0.2, 0.3, 0.2, 0.3],
        'variabilidad' => [0.7, 0.7, 0.6, 0.8],
    ],
    'jugadora5@mail.com' => [
        'nombre'       => 'Ana Torres',
        'bases'        => [3.0, 3.5, 3.8, 3.2],
        'mejora'       => [0.5, 0.3, 0.3, 0.5],
        'variabilidad' => [0.6, 0.7, 0.8, 0.7],
    ],
    'jugador6@mail.com' => [
        'nombre'       => 'Mateo Hernández',
        'bases'        => [3.2, 3.4, 3.1, 3.0],
        'mejora'       => [0.6, 0.5, 0.5, 0.6],  // alta proyección: progresión pronunciada
        'variabilidad' => [0.6, 0.5, 0.7, 0.6],
    ],
    'jugador7@mail.com' => [
        'nombre'       => 'Santiago Gómez',
        'bases'        => [4.0, 3.8, 4.2, 4.4],
        'mejora'       => [-0.3, -0.2, -0.3, -0.4], // tendencia decreciente (baja de rendimiento)
        'variabilidad' => [0.9, 0.8, 0.9, 0.8],     // muy inconstante: respuestas muy dispersas
    ],
    'jugador8@mail.com' => [
        'nombre'       => 'Emiliano Castro',
        'bases'        => [3.6, 3.0, 2.8, 3.4],
        'mejora'       => [0.1, 0.4, 0.3, 0.2],
        'variabilidad' => [0.5, 0.6, 0.6, 0.5],
    ],
    'jugadora9@mail.com' => [
        'nombre'       => 'Valentina Morales',
        'bases'        => [2.9, 3.1, 2.5, 2.7],
        'mejora'       => [0.2, 0.2, 0.3, 0.3],
        'variabilidad' => [0.8, 0.7, 0.8, 0.9],     // irregular: rachas de muy bueno y muy malo
    ],
    'jugador10@mail.com' => [
        'nombre'       => 'Iker Vega',
        'bases'        => [4.7, 4.4, 4.5, 4.2],
        'mejora'       => [0.0, 0.1, 0.0, 0.1],     // meseta: élite ya consolidado, poco margen
        'variabilidad' => [0.3, 0.3, 0.4, 0.4],
    ],
    'jugador11@mail.com' => [
        'nombre'       => 'Alejandro Robles',
        'bases'        => [3.1, 3.3, 3.4, 3.0],
        'mejora'       => [0.4, 0.3, 0.3, 0.4],
        'variabilidad' => [0.5, 0.6, 0.6, 0.5],
    ],
    'jugador12@mail.com' => [
        'nombre'       => 'Fernanda Castillo',
        'bases'        => [3.8, 3.6, 3.2, 4.0],
        'mejora'       => [0.2, 0.3, 0.4, 0.2],
        'variabilidad' => [0.6, 0.5, 0.5, 0.6],
    ],
    'jugador13@mail.com' => [
        'nombre'       => 'Samuel Ortega',
        'bases'        => [2.4, 2.8, 3.0, 2.6],
        'mejora'       => [0.6, 0.4, 0.5, 0.5],     // proyección: sube desde base baja
        'variabilidad' => [0.7, 0.7, 0.6, 0.7],
    ],
    'jugador14@mail.com' => [
        'nombre'       => 'Camila Mendoza',
        'bases'        => [4.3, 4.5, 4.0, 4.1],
        'mejora'       => [0.1, 0.0, 0.2, 0.1],     // meseta alta y estable
        'variabilidad' => [0.4, 0.3, 0.5, 0.4],
    ],
    'jugador15@mail.com' => [
        'nombre'       => 'Nicolás Aguilar',
        'bases'        => [2.9, 3.1, 2.6, 2.8],
        'mejora'       => [-0.1, 0.0, -0.2, -0.1],   // declinante irregular
        'variabilidad' => [0.8, 0.8, 0.7, 0.8],
    ],
    'jugador16@mail.com' => [
        'nombre'       => 'Mariana Serrano',
        'bases'        => [3.4, 3.0, 3.6, 3.8],
        'mejora'       => [0.3, 0.4, 0.2, 0.3],
        'variabilidad' => [0.6, 0.7, 0.6, 0.5],
    ],
    'jugador17@mail.com' => [
        'nombre'       => 'Leonardo Fuentes',
        'bases'        => [3.9, 4.1, 3.8, 3.5],
        'mejora'       => [-0.2, -0.1, -0.2, -0.3],  // decadencia marcada
        'variabilidad' => [0.7, 0.6, 0.7, 0.6],
    ],
    'jugador18@mail.com' => [
        'nombre'       => 'Daniela Rojas',
        'bases'        => [2.7, 2.9, 2.5, 3.0],
        'mejora'       => [0.5, 0.5, 0.6, 0.4],
        'variabilidad' => [0.7, 0.6, 0.8, 0.7],
    ],
    'jugador19@mail.com' => [
        'nombre'       => 'Gabriel Salinas',
        'bases'        => [4.0, 3.7, 4.2, 3.9],
        'mejora'       => [0.1, 0.3, 0.1, 0.2],
        'variabilidad' => [0.5, 0.6, 0.5, 0.5],
    ],
    'jugador20@mail.com' => [
        'nombre'       => 'Isabella Cordero',
        'bases'        => [3.3, 3.5, 2.9, 3.1],
        'mejora'       => [0.2, 0.1, 0.4, 0.3],
        'variabilidad' => [0.6, 0.5, 0.7, 0.6],
    ],
    'jugador21@mail.com' => [
        'nombre'       => 'Andrés Peña',
        'bases'        => [2.2, 2.5, 2.8, 2.3],
        'mejora'       => [0.4, 0.3, 0.3, 0.4],
        'variabilidad' => [0.9, 0.8, 0.8, 0.9],     // muy irregular, nivel bajo
    ],
    'jugador22@mail.com' => [
        'nombre'       => 'Sofía Lozano',
        'bases'        => [4.5, 4.3, 4.6, 4.4],
        'mejora'       => [-0.2, -0.2, -0.1, -0.2],  // élite con leve decaimiento
        'variabilidad' => [0.5, 0.6, 0.4, 0.5],
    ],
    'jugador23@mail.com' => [
        'nombre'       => 'Sebastián Delgado',
        'bases'        => [3.0, 3.2, 3.1, 2.9],
        'mejora'       => [0.5, 0.4, 0.4, 0.5],
        'variabilidad' => [0.6, 0.7, 0.6, 0.6],
    ],
    'jugador24@mail.com' => [
        'nombre'       => 'Renata Quiñones',
        'bases'        => [3.6, 3.8, 3.3, 3.5],
        'mejora'       => [0.0, 0.1, 0.2, 0.0],     // meseta media
        'variabilidad' => [0.5, 0.6, 0.5, 0.5],
    ],
    'jugador25@mail.com' => [
        'nombre'       => 'Emilio Navarro',
        'bases'        => [2.6, 3.0, 2.4, 2.7],
        'mejora'       => [0.3, 0.2, 0.4, 0.3],
        'variabilidad' => [0.8, 0.7, 0.8, 0.9],
    ],
    'jugador26@mail.com' => [
        'nombre'       => 'Bárbara Cáceres',
        'bases'        => [4.2, 4.0, 3.9, 4.3],
        'mejora'       => [0.1, 0.2, 0.3, 0.1],
        'variabilidad' => [0.4, 0.5, 0.4, 0.4],
    ],
    'jugador27@mail.com' => [
        'nombre'       => 'Facundo Romero',
        'bases'        => [3.5, 3.3, 3.7, 3.4],
        'mejora'       => [-0.1, 0.0, 0.1, -0.2],   // fluctuante, sin tendencia clara
        'variabilidad' => [0.7, 0.6, 0.7, 0.8],
    ],
];

// ── Periodos desde la tabla ──
$periodRows = $conn->executeQuery(
    "SELECT id, name, start_date FROM periods WHERE deleted_at IS NULL ORDER BY start_date ASC"
)->fetchAllAssociative();

if (empty($periodRows)) {
    echo "✗ No se encontraron periodos. Ejecuta primero 005_seed_periods.\n";
    exit(1);
}

$periodos = [];
foreach ($periodRows as $row) {
    $periodos[] = [
        'id'    => (int) $row['id'],
        'label' => $row['name'],
        'start' => new \DateTime($row['start_date'] ?? '2025-01-01'),
    ];
}

// ── Índices de preguntas por grupo ──
// Las preguntas llegan ordenadas por section_order, question_order
// Grupos: 0-4 ofensivo, 5-9 defensivo, 10-13 físico, 14-18 mental
$grupos = [
    'ofensivo'  => [0, 1, 2, 3, 4],
    'defensivo' => [5, 6, 7, 8, 9],
    'fisico'    => [10, 11, 12, 13],
    'mental'    => [14, 15, 16, 17, 18],
];

// Para usar mt_rand con distribución normal aproximada
function normalRand(float $mean, float $stdDev): float
{
    $u1 = mt_rand() / mt_getrandmax();
    $u2 = mt_rand() / mt_getrandmax();
    return $mean + $stdDev * sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);
}

function clampScore(float $val): int
{
    return max(1, min(5, (int) round($val)));
}

$totalEvals = 0;
$totalAnswers = 0;
$evalInsertIds = [];

foreach ($jugadores as $jugador) {
    $email = $jugador['email'];
    $jugadorId = (int) $jugador['id'];
    $perfil = $perfiles[$email] ?? null;

    if ($perfil === null) {
        echo "• Jugador '{$email}' sin perfil definido, se omite.\n";
        continue;
    }

    echo "--- {$perfil['nombre']} ({$email}) ---\n";

    foreach ($periodos as $pi => $periodo) {
        // Semilla determinista por jugador+periodo: mismas respuestas en cada ejecución,
        // pero variadas entre jugadores y periodos.
        mt_srand(crc32($email . '|' . $periodo['label']));

        // Fecha de la evaluación dentro del periodo (días 15..80 desde el inicio) para
        // esparcir los test en el tiempo.
        $offsetDias = mt_rand(15, 80);
        $fecha = (clone $periodo['start'])->modify("+{$offsetDias} days")->format('Y-m-d H:i:s');

        // ── Verificar si la evaluación ya existe ──
        $existingEval = $conn->executeQuery(
            "SELECT id FROM evaluations
             WHERE questionnaire_id = ? AND responder_user_id = ? AND target_user_id = ? AND period_id = ? AND deleted_at IS NULL",
            [$qId, $entrenadorId, $jugadorId, $periodo['id']]
        )->fetchOne();

        if ($existingEval !== false) {
            echo "  • Evaluación para {$perfil['nombre']} en {$periodo['label']} ya existe (ID: {$existingEval}). Saltando.\n";
            continue;
        }

        // Calcular base para este periodo (progresión lineal, puede ser negativa)
        $progreso = $pi / (count($periodos) - 1);  // 0 a 1
        $basesPeriodo = [];
        foreach ($perfil['bases'] as $gi => $base) {
            $basesPeriodo[$gi] = $base + $perfil['mejora'][$gi] * $progreso;
        }

        // Generar 19 respuestas coherentes y variadas
        $scores = [];
        foreach ($grupos as $gNombre => $indices) {
            $gi = array_search($gNombre, array_keys($grupos)); // índice del grupo (0-3)
            $base = $basesPeriodo[$gi];
            $stdDev = $perfil['variabilidad'][$gi] ?? 0.6;

            foreach ($indices as $qi) {
                $score = normalRand($base, $stdDev);

                // Rachas (inconsistencia): ~8% de probabilidad de ±1 punto extra
                $racha = mt_rand(1, 100);
                if ($racha <= 8) {
                    $score += 1;
                } elseif ($racha >= 96) {
                    $score -= 1;
                }

                $scores[$qi] = clampScore($score);
            }
        }

        // Insertar evaluación
        $conn->executeStatement(
            "INSERT INTO evaluations (questionnaire_id, responder_user_id, target_user_id, period_id, created_by, updated_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$qId, $entrenadorId, $jugadorId, $periodo['id'], $entrenadorId, $entrenadorId, $fecha, $fecha]
        );
        $evaluacionId = (int) $conn->lastInsertId();

        // Insertar respuestas
        foreach ($scores as $qi => $valor) {
            $questionId = $questionIds[$qi];
            $conn->executeStatement(
                "INSERT INTO answers (evaluation_id, question_id, value, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?)",
                [$evaluacionId, $questionId, (string) $valor, $fecha, $fecha]
            );
            $totalAnswers++;
        }

        $totalEvals++;
        echo "  ✓ Periodo {$periodo['label']}: {$totalEvals} evaluaciones, {$totalAnswers} respuestas\n";
    }
}

echo "\nMigración de test 002 completada. {$totalEvals} evaluaciones, {$totalAnswers} respuestas creadas.\n";
