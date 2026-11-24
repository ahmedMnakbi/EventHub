<?php

namespace App\Repository;

use App\Entity\Venue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Venue>
 */
class VenueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Venue::class);
    }

    public function createAdminListQueryBuilder(?string $search, ?string $city): QueryBuilder
    {
        $qb = $this->createQueryBuilder('v')->orderBy('v.name', 'ASC');

        if ($search !== null && trim($search) !== '') {
            $qb->andWhere('v.name LIKE :q OR v.address LIKE :q')
               ->setParameter('q', SearchHelper::likeContains($search));
        }

        if ($city !== null && $city !== '') {
            $qb->andWhere('v.city = :city')->setParameter('city', $city);
        }

        return $qb;
    }

    /** @return list<string> */
    public function findDistinctCities(): array
    {
        return array_column(
            $this->createQueryBuilder('v')
                ->select('DISTINCT v.city')
                ->orderBy('v.city', 'ASC')
                ->getQuery()
                ->getScalarResult(),
            'city',
        );
    }
}
