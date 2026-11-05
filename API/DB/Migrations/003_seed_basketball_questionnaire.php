<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Core\Env;
use App\Core\Container;
use App\Features\RolesPermisos\Domain\Interfaces\RoleRepositoryInterface;
use App\Features\Questionnaire\Domain\Interfaces\QuestionnaireServiceInterface;
use App\Features\Questionnaire\Domain\Interfaces\SectionServiceInterface;
use App\Features\Questionnaire\Domain\Interfaces\QuestionServiceInterface;

Env::load(__DIR__ . '/../..');

$container = new Container();

$roleRepository = $container->get(RoleRepositoryInterface::class);
$questionnaireService = $container->get(QuestionnaireServiceInterface::class);
$sectionService = $container->get(SectionServiceInterface::class);
$questionService = $container->get(QuestionServiceInterface::class);

$entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
$conn = $entityManager->getConnection();

// ── Verificar si el cuestionario ya existe ──
$existingId = $conn->executeQuery(
    "SELECT id FROM questionnaires WHERE title = 'Evaluación Técnica de Jugador' AND deleted_at IS NULL"
)->fetchOne();

if ($existingId !== false) {
    echo "• Cuestionario 'Evaluación Técnica de Jugador' ya existe (ID: {$existingId}). Saltando.\n";
    return;
}

$entrenadorRole = $roleRepository->findByCode('ENT');
$jugadorRole = $roleRepository->findByCode('JUG');

if ($entrenadorRole === null || $jugadorRole === null) {
    echo "✗ Roles 'ENT' o 'JUG' no encontrados. Ejecuta primero la migración 001.\n";
    exit(1);
}

$options = [
    ['value' => '1', 'label' => 'Malo'],
    ['value' => '2', 'label' => 'Regular'],
    ['value' => '3', 'label' => 'Bueno'],
    ['value' => '4', 'label' => 'Muy bueno'],
    ['value' => '5', 'label' => 'Excelente'],
];

// ── Crear cuestionario ──
$questionnaire = $questionnaireService->create([
    'title'             => 'Evaluación Técnica de Jugador',
    'description'       => 'Evaluación integral del rendimiento técnico, táctico y actitudinal del jugador',
    'responder_role_id' => $entrenadorRole->getId(),
    'target_role_id'    => $jugadorRole->getId(),
]);
$qId = $questionnaire->getId();
echo "✓ Cuestionario '{$questionnaire->getTitle()}' creado (ID: {$qId}).\n";

// ── Sección 1: Fundamentos Técnicos (Ofensivos) ──
$sec1 = $sectionService->create($qId, [
    'title'       => 'Fundamentos Técnicos (Ofensivos)',
    'description' => 'Evaluación de habilidades ofensivas con balón y sin balón',
    'section_order' => 1,
]);
$sec1Id = $sec1->getId();
echo "✓ Sección '{$sec1->getTitle()}' creada (ID: {$sec1Id}).\n";

$offensiveQuestions = [
    ['text' => 'Manejo de balón (control, uso de ambas manos, protección)', 'order' => 1],
    ['text' => 'Mecánica de tiro (suspensión, libres, triples)', 'order' => 2],
    ['text' => 'Selección de tiro (toma de decisiones ofensivas)', 'order' => 3],
    ['text' => 'Capacidad de pase (precisión, visión, lectura)', 'order' => 4],
    ['text' => 'Finalización cerca del aro (entradas, juego interior)', 'order' => 5],
];
foreach ($offensiveQuestions as $q) {
    $questionService->create($sec1Id, [
        'text'           => $q['text'],
        'type'           => 'selection',
        'question_order' => $q['order'],
        'options'        => $options,
    ]);
    echo "  ✓ Pregunta: {$q['text']}\n";
}

// ── Sección 2: Fundamentos Técnicos (Defensivos) ──
$sec2 = $sectionService->create($qId, [
    'title'       => 'Fundamentos Técnicos (Defensivos)',
    'description' => 'Evaluación de habilidades defensivas individuales y colectivas',
    'section_order' => 2,
]);
$sec2Id = $sec2->getId();
echo "✓ Sección '{$sec2->getTitle()}' creada (ID: {$sec2Id}).\n";

$defensiveQuestions = [
    ['text' => 'Desplazamiento defensivo (pies, postura, agilidad)', 'order' => 1],
    ['text' => 'Defensa individual (presión al balón, contención)', 'order' => 2],
    ['text' => 'Ayudas defensivas y rotaciones (lectura táctica)', 'order' => 3],
    ['text' => 'Control del rebote (posicionamiento y técnica)', 'order' => 4],
    ['text' => 'Comunicación defensiva (hablar en pista)', 'order' => 5],
];
foreach ($defensiveQuestions as $q) {
    $questionService->create($sec2Id, [
        'text'           => $q['text'],
        'type'           => 'selection',
        'question_order' => $q['order'],
        'options'        => $options,
    ]);
    echo "  ✓ Pregunta: {$q['text']}\n";
}

// ── Sección 3: Rendimiento Físico y Táctico ──
$sec3 = $sectionService->create($qId, [
    'title'       => 'Rendimiento Físico y Táctico',
    'description' => 'Evaluación de condición física y comprensión táctica del juego',
    'section_order' => 3,
]);
$sec3Id = $sec3->getId();
echo "✓ Sección '{$sec3->getTitle()}' creada (ID: {$sec3Id}).\n";

$fisicoQuestions = [
    ['text' => 'Resistencia cardiovascular y ritmo de juego', 'order' => 1],
    ['text' => 'Explosividad y velocidad de reacción', 'order' => 2],
    ['text' => 'Comprensión del sistema de juego (ejecución táctica)', 'order' => 3],
    ['text' => 'Disciplina táctica (cumplimiento de normas)', 'order' => 4],
];
foreach ($fisicoQuestions as $q) {
    $questionService->create($sec3Id, [
        'text'           => $q['text'],
        'type'           => 'selection',
        'question_order' => $q['order'],
        'options'        => $options,
    ]);
    echo "  ✓ Pregunta: {$q['text']}\n";
}

// ── Sección 4: Aspecto Mental y Actitudinal ──
$sec4 = $sectionService->create($qId, [
    'title'       => 'Aspecto Mental y Actitudinal',
    'description' => 'Evaluación de factores psicológicos y de actitud',
    'section_order' => 4,
]);
$sec4Id = $sec4->getId();
echo "✓ Sección '{$sec4->getTitle()}' creada (ID: {$sec4Id}).\n";

$mentalQuestions = [
    ['text' => 'Foco y concentración en los entrenamientos', 'order' => 1],
    ['text' => 'Resiliencia ante el error o marcadores adversos', 'order' => 2],
    ['text' => 'Intensidad y esfuerzo constante (actitud)', 'order' => 3],
    ['text' => 'Liderazgo y cohesión grupal', 'order' => 4],
    ['text' => 'Capacidad de aprendizaje (receptividad a correcciones)', 'order' => 5],
];
foreach ($mentalQuestions as $q) {
    $questionService->create($sec4Id, [
        'text'           => $q['text'],
        'type'           => 'selection',
        'question_order' => $q['order'],
        'options'        => $options,
    ]);
    echo "  ✓ Pregunta: {$q['text']}\n";
}

echo "\nMigración 003 completada.\n";
