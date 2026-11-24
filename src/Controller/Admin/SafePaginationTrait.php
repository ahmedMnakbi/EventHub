<?php

namespace App\Controller\Admin;

use Knp\Component\Pager\Exception\InvalidValueException;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Paginates a query with a whitelist of sortable fields.
 * A tampered "sort" parameter (field not in the whitelist) returns 400 instead of 500.
 */
trait SafePaginationTrait
{
    /**
     * @param list<string> $sortableFields e.g. ['c.name', 'c.createdAt']
     */
    private function paginateSafely(
        PaginatorInterface $paginator,
        mixed $query,
        Request $request,
        array $sortableFields,
        int $perPage = 10,
    ): PaginationInterface {
        try {
            return $paginator->paginate(
                $query,
                max(1, $request->query->getInt('page', 1)),
                $perPage,
                ['sortFieldAllowList' => $sortableFields],
            );
        } catch (InvalidValueException) {
            throw new BadRequestHttpException('Paramètre de tri invalide.');
        }
    }
}
