<?php

declare(strict_types=1);

namespace App\Features\People\Application\Controllers;

use App\Core\Env;
use App\Core\Interfaces\ControllerInterface;
use App\Features\People\Application\DTO\PersonQuery;
use App\Features\People\Domain\Interfaces\PersonServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PersonController implements ControllerInterface
{
    public function __construct(
        private PersonServiceInterface $personService,
        private Request $request,
    ) {}

    public function execute(): Response
    {
        return match ($this->request->getMethod()) {
            'POST'   => $this->create(),
            'GET'    => $this->list(),
            default  => new JsonResponse(['error' => getError('METHOD_NOT_ALLOWED'), 'code' => 'METHOD_NOT_ALLOWED'], 405),
        };
    }

    #[OA\Post(path: '/people', tags: ['People'], summary: 'Create person')]
    #[OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/PersonCreate'))]
    #[OA\Response(response: 201, description: 'Person created', content: new OA\JsonContent(ref: '#/components/schemas/Person'))]
    #[OA\Response(response: 422, description: 'Invalid data', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    private function create(): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => getError('VALIDATION_JSON_INVALID'), 'code' => 'VALIDATION_JSON_INVALID'], 400);
        }

        $required = ['first_name', 'first_surname', 'document_number', 'email', 'type'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || !is_string($data[$field])) {
                return new JsonResponse(['error' => getError('VALIDATION_REQUIRED'), 'code' => 'VALIDATION_REQUIRED'], 422);
            }
        }

        try {
            $person = $this->personService->create($data);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse($this->serialize($person), 201);
    }

    #[OA\Get(path: '/people', tags: ['People'], summary: 'List people')]
    #[OA\Parameter(name: 'type', in: 'query', description: 'Filter by type', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'document_number', in: 'query', description: 'Filter by document number', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'search', in: 'query', description: 'Search by name or ID', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'page', in: 'query', description: 'Page number', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'perPage', in: 'query', description: 'Items per page', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Paginated list of people', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    private function list(): Response
    {
        $page = (int) ($this->request->query->get('page', '1'));
        $perPage = (int) ($this->request->query->get('perPage', Env::get('PAGINATION_PER_PAGE', '20')));

        $query = new PersonQuery(
            type: $this->request->query->get('type'),
            documentNumber: $this->request->query->get('document_number'),
            search: $this->request->query->get('search'),
            page: max(1, $page),
            perPage: max(1, min(100, $perPage)),
        );

        [$people, $total] = $this->personService->list($query);

        $data = array_map(fn ($p) => $this->serialize($p), $people);

        return new JsonResponse([
            'data'    => $data,
            'total'   => $total,
            'page'    => $query->page,
            'perPage' => $query->perPage,
        ]);
    }

    #[OA\Get(path: '/people/{id}', tags: ['People'], summary: 'Get person by ID')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Person found', content: new OA\JsonContent(ref: '#/components/schemas/Person'))]
    #[OA\Response(response: 404, description: 'Person not found', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function show(int $id): Response
    {
        $person = $this->personService->findById($id);

        if ($person === null) {
            return new JsonResponse(['error' => getError('PERSON_NOT_FOUND'), 'code' => 'PERSON_NOT_FOUND'], 404);
        }

        return new JsonResponse($this->serialize($person));
    }

    #[OA\Put(path: '/people/{id}', tags: ['People'], summary: 'Update person')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/PersonUpdate'))]
    #[OA\Response(response: 200, description: 'Person updated', content: new OA\JsonContent(ref: '#/components/schemas/Person'))]
    #[OA\Response(response: 404, description: 'Person not found', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function update(int $id): Response
    {
        $data = json_decode($this->request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => getError('VALIDATION_JSON_INVALID'), 'code' => 'VALIDATION_JSON_INVALID'], 400);
        }

        try {
            $person = $this->personService->update($id, $data);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse($this->serialize($person));
    }

    #[OA\Delete(path: '/people/{id}', tags: ['People'], summary: 'Delete person (soft delete)')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 204, description: 'Person deleted')]
    #[OA\Response(response: 404, description: 'Person not found', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function delete(int $id): Response
    {
        try {
            $this->personService->softDelete($id);
        } catch (\App\Core\AppException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'code' => $e->getErrorCode()], $e->getCode());
        }

        return new JsonResponse(null, 204);
    }

    private function serialize($person): array
    {
        return [
            'id'            => $person->getId(),
            'user_id'       => $person->getUser()?->getId(),
            'type'          => $person->getPeopleType()->getCode(),
            'first_name'    => $person->getFirstName(),
            'middle_name'   => $person->getMiddleName(),
            'first_surname' => $person->getFirstSurname(),
            'second_surname'=> $person->getSecondSurname(),
            'document_number' => $person->getDocumentNumber(),
            'email'         => $person->getEmail(),
            'phone'         => $person->getPhone(),
            'address'       => $person->getAddress(),
            'create_user'   => $person->getUser() !== null,
            'created_at'    => $person->getCreatedAt()->format('c'),
            'updated_at'    => $person->getUpdatedAt()->format('c'),
        ];
    }
}
