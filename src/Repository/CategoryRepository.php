<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /** Query used by the admin list (search + sort handled by the paginator). */
    public function createAdminListQueryBuilder(?string $search): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c')->orderBy('c.name', 'ASC');

        if ($search !== null && trim($search) !== '') {
            $qb->andWhere('c.name LIKE :q OR c.description LIKE :q')
               ->setParameter('q', SearchHelper::likeContains($search));
        }

        return $qb;
    }
}
