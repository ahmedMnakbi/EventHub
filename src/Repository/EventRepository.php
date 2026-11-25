<?php

namespace App\Repository;

use App\Entity\Event;
use App\Enum\EventStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Query used by the admin list. Every user value goes through a bound parameter.
     * Category and venue are fetched in the same query (no N+1).
     */
    public function createAdminListQueryBuilder(?string $search, ?int $categoryId, ?EventStatus $status): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')
            ->addSelect('c', 'v')
            ->innerJoin('e.category', 'c')
            ->innerJoin('e.venue', 'v')
            ->orderBy('e.startAt', 'DESC');

        if ($search !== null && trim($search) !== '') {
            $qb->andWhere('e.title LIKE :q OR v.name LIKE :q OR v.city LIKE :q')
               ->setParameter('q', SearchHelper::likeContains($search));
        }

        if ($categoryId !== null) {
            $qb->andWhere('c.id = :category')->setParameter('category', $categoryId);
        }

        if ($status !== null) {
            $qb->andWhere('e.status = :status')->setParameter('status', $status);
        }

        return $qb;
    }
}
