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

if (count($jugadores) !== 5) {
    echo "⚠ Se encontraron " . count($jugadores) . " jugadores (esperados 5).\n";
}

// ── Perfiles de jugadores (coherencia) ──
// Cada perfil define base inicial y mejora por periodo para cada grupo de preguntas
// Grupos: ofensivo (0-4), defensivo (5-9), físico (10-13), mental (14-18)
$perfiles = [
    'jugador@mail.com' => [
        'nombre'  => 'Juan García',
        'bases'   => [3.5, 3.8, 3.2, 3.6],  // ofensivo, defensivo, físico, mental
        'mejora'  => [0.3, 0.2, 0.3, 0.2],  // mejora por periodo
    ],
    'jugador2@mail.com' => [
        'nombre'  => 'Pedro Martínez',
        'bases'   => [2.8, 3.0, 3.5, 2.5],
        'mejora'  => [0.5, 0.3, 0.4, 0.4],
    ],
    'jugador3@mail.com' => [
        'nombre'  => 'Luis Fernández',
        'bases'   => [4.5, 4.2, 4.0, 4.3],
        'mejora'  => [0.1, 0.2, 0.3, 0.1],
    ],
    'jugador4@mail.com' => [
        'nombre'  => 'Diego Ramírez',
        'bases'   => [2.5, 2.2, 3.0, 2.0],
        'mejora'  => [0.2, 0.3, 0.2, 0.3],
    ],
    'jugadora5@mail.com' => [
        'nombre'  => 'Ana Torres',
        'bases'   => [3.0, 3.5, 3.8, 3.2],
        'mejora'  => [0.5, 0.3, 0.3, 0.5],
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
    $startDate = new \DateTime($row['start_date'] ?? '2025-01-01');
    $midPoint = (clone $startDate)->modify('+45 days');
    $periodos[] = [
        'id'    => (int) $row['id'],
        'label' => $row['name'],
        'date'  => $midPoint->format('Y-m-d H:i:s'),
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
        $fecha = $periodo['date'];

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

        // Calcular base para este periodo (progresión lineal)
        $progreso = $pi / (count($periodos) - 1);  // 0 a 1
        $basesPeriodo = [];
        foreach ($perfil['bases'] as $gi => $base) {
            $basesPeriodo[$gi] = $base + $perfil['mejora'][$gi] * $progreso;
        }

        // Generar 19 respuestas coherentes
        $scores = [];
        foreach ($grupos as $gNombre => $indices) {
            $gi = array_search($gNombre, array_keys($grupos)); // índice del grupo (0-3)
            $base = $basesPeriodo[$gi];

            foreach ($indices as $qi) {
                $score = normalRand($base, 0.6);
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
