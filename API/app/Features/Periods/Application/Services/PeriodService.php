<?php

declare(strict_types=1);

namespace App\Features\Periods\Application\Services;

use App\Core\AppException;
use App\Features\Periods\Application\DTO\PeriodQuery;
use App\Features\Periods\Domain\Entities\Period;
use App\Features\Periods\Domain\Interfaces\PeriodRepositoryInterface;
use App\Features\Periods\Domain\Interfaces\PeriodServiceInterface;

class PeriodService implements PeriodServiceInterface
{
    public function __construct(
        private PeriodRepositoryInterface $periodRepository,
    ) {}

    public function findAll(): array
    {
        return $this->periodRepository->findAll();
    }

    public function findById(int $id): ?Period
    {
        return $this->periodRepository->findById($id);
    }

    public function findPaginated(PeriodQuery $query): array
    {
        return $this->periodRepository->findPaginated($query);
    }

    public function create(string $name, string $startDate, string $endDate): Period
    {
        $existing = $this->periodRepository->findByName($name);
        if ($existing !== null) {
            throw new AppException('PERIOD_OVERLAP');
        }

        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);

        if ($start > $end) {
            throw new AppException('PERIOD_DATES_INVALID');
        }

        $period = new Period();
        $period->setName($name);
        $period->setStartDate($start);
        $period->setEndDate($end);
        $period->setCreatedAt(new \DateTime());
        $period->setUpdatedAt(new \DateTime());

        $this->periodRepository->save($period);

        return $period;
    }

    public function update(int $id, string $name, string $startDate, string $endDate): Period
    {
        $period = $this->periodRepository->findById($id);
        if ($period === null) {
            throw new AppException('PERIOD_NOT_FOUND');
        }

        $existing = $this->periodRepository->findByName($name);
        if ($existing !== null && $existing->getId() !== $id) {
            throw new AppException('PERIOD_OVERLAP');
        }

        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);

        if ($start > $end) {
            throw new AppException('PERIOD_DATES_INVALID');
        }

        $period->setName($name);
        $period->setStartDate($start);
        $period->setEndDate($end);
        $period->setUpdatedAt(new \DateTime());

        $this->periodRepository->save($period);

        return $period;
    }

    public function softDelete(int $id): void
    {
        $period = $this->periodRepository->findById($id);
        if ($period === null) {
            throw new AppException('PERIOD_NOT_FOUND');
        }

        $this->periodRepository->softDelete($period);
    }
}
