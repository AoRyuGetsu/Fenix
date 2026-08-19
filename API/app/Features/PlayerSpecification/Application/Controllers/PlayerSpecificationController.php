<?php

declare(strict_types=1);

namespace App\Features\PlayerSpecification\Application\Controllers;

use App\Core\AppException;
use App\Core\Env;
use App\Core\Interfaces\ControllerInterface;
use App\Features\PlayerSpecification\Application\DTO\PlayerSpecificationQuery;
use App\Features\PlayerSpecification\Domain\Interfaces\PlayerSpecificationServiceInterface;
use App\Infrastructure\Auth\AuthContext;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PlayerSpecificationController implements ControllerInterface
{
    public function __construct(
        private PlayerSpecificationServiceInterface $playerSpecService,
        private Request $request,
        private AuthContext $authContext,
    ) {}

    public function execute(): Response
    {
        return new JsonResponse(['error' => getError('NOT_FOUND'), 'code' => 'NOT_FOUND'], 404);
    }

    #[OA\Get(path: '/player-specifications', tags: ['PlayerSpecification'], summary: 'Listar especificaciones de jugadores')]
    #[OA\Parameter(name: 'position', in: 'query', description: 'Filter by position', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'status', in: 'query', description: 'Filter by status', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'name', in: 'query', description: 'Filter by user email', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'perPage', in: 'query', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Lista paginada', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    public function list(): Response
    {
        $page = (int) ($this->request->query->get('page', '1'));
        $perPage = (int) ($this->request->query->get('perPage', Env::get('PAGINATION_PER_PAGE', '20')));

        $query = new PlayerSpecificationQuery(
            position: $this->request->query->get('position'),
            status: $this->request->query->get('status'),
            name: $this->request->query->get('name'),
            page: max(1, $page),
            perPage: max(1, min(100, $perPage)),
        );

        [$items, $total] = $this->playerSpecService->findPaginated($query);

        $data = array_map(fn ($s) => $this->serialize($s), $items);

        return new JsonResponse([
            'data' => $data,
            'total' => $total,
            'page' => $query->page,
            'perPage' => $query->perPage,
        ]);
    }

    #[OA\Get(path: '/player-specifications/{id}', tags: ['PlayerSpecification'], summary: 'Obtener especificación por ID de usuario')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Especificación encontrada', content: new OA\JsonContent(ref: '#/components/schemas/PlayerSpecification'))]
    #[OA\Response(response: 404, description: 'No encontrada')]
    public function show(int $id): Response
    {
        $spec = $this->playerSpecService->findById($id);

        if ($spec === null) {
            return new JsonResponse(['error' => getError('PLAYER_SPEC_NOT_FOUND'), 'code' => 'PLAYER_SPEC_NOT_FOUND'], 404);
        }

        return new JsonResponse($this->serialize($spec));
    }

    #[OA\Post(path: '/player-specifications', tags: ['PlayerSpecification'], summary: 'Crear especificación de jugador')]
    #[OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/PlayerSpecificationCreate'))]
    #[OA\Response(response: 201, description: 'Creada', content: new OA\JsonContent(ref: '#/components/schemas/PlayerSpecification'))]
    #[OA\Response(response: 422, description: 'Datos inválidos')]
    public function create(): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => getError('VALIDATION_JSON_INVALID'), 'code' => 'VALIDATION_JSON_INVALID'], 400);
        }

        $required = ['user_id', 'birth_date', 'height_cm', 'weight_kg', 'gender', 'entry_date', 'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                return new JsonResponse(['error' => getError('VALIDATION_REQUIRED'), 'code' => 'VALIDATION_REQUIRED'], 422);
            }
        }

        try {
            $spec = $this->playerSpecService->create((int) $data['user_id'], $data, $this->authContext->getUserId());
        } catch (AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse($this->serialize($spec), 201);
    }

    #[OA\Put(path: '/player-specifications/{id}', tags: ['PlayerSpecification'], summary: 'Actualizar especificación de jugador')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/PlayerSpecificationUpdate'))]
    #[OA\Response(response: 200, description: 'Actualizada', content: new OA\JsonContent(ref: '#/components/schemas/PlayerSpecification'))]
    #[OA\Response(response: 404, description: 'No encontrada')]
    public function update(int $id): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => getError('VALIDATION_JSON_INVALID'), 'code' => 'VALIDATION_JSON_INVALID'], 400);
        }

        try {
            $spec = $this->playerSpecService->update($id, $data, $this->authContext->getUserId());
        } catch (AppException $e) {
            if ($e->getErrorCode() === 'PLAYER_SPEC_NOT_FOUND') {
                return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], 404);
            }
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse($this->serialize($spec));
    }

    #[OA\Delete(path: '/player-specifications/{id}', tags: ['PlayerSpecification'], summary: 'Eliminar especificación (soft delete)')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 204, description: 'Eliminada')]
    #[OA\Response(response: 404, description: 'No encontrada')]
    public function delete(int $id): Response
    {
        try {
            $this->playerSpecService->softDelete($id);
        } catch (AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse(null, 204);
    }

    private function serialize(object $spec): array
    {
        return [
            'user_id' => $spec->getUser()->getId(),
            'user_email' => $spec->getUser()->getEmail(),
            'birth_date' => $spec->getBirthDate()->format('Y-m-d'),
            'height_cm' => (float) $spec->getHeightCm(),
            'weight_kg' => (float) $spec->getWeightKg(),
            'gender' => $spec->getGender(),
            'entry_date' => $spec->getEntryDate()->format('Y-m-d'),
            'medical_conditions' => $spec->getMedicalConditions(),
            'emergency_contact_name' => $spec->getEmergencyContactName(),
            'emergency_contact_phone' => $spec->getEmergencyContactPhone(),
            'emergency_contact_relationship' => $spec->getEmergencyContactRelationship(),
            'position' => $spec->getPosition(),
            'jersey_number_1' => $spec->getJerseyNumber1(),
            'jersey_number_2' => $spec->getJerseyNumber2(),
            'jersey_number_3' => $spec->getJerseyNumber3(),
            'dominant_foot' => $spec->getDominantFoot(),
            'nationality' => $spec->getNationality(),
            'blood_type' => $spec->getBloodType(),
            'allergies' => $spec->getAllergies(),
            'insurance' => $spec->getInsurance(),
            'medications' => $spec->getMedications(),
            'basketball_experience' => $spec->getBasketballExperience(),
            'previous_club' => $spec->getPreviousClub(),
            'category' => $spec->getCategory(),
            'shirt_size' => $spec->getShirtSize(),
            'profile_photo_url' => $spec->getProfilePhotoUrl(),
            'status' => $spec->getStatus(),
            'created_at' => $spec->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $spec->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
