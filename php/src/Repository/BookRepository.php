<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * @return Book[]
     */
    public function findOnlineOrderedByPublishedAtDesc(): array
    {
        return $this->createQueryBuilder('book')
            ->andWhere('book.isOnline = :isOnline')
            ->setParameter('isOnline', true)
            ->orderBy('book.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countLoanedBooks(): int
    {
        return (int) $this->createQueryBuilder('book')
            ->select('COUNT(book.id)')
            ->andWhere('book.userEntity IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
