<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Book;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/books', name: 'app_book_api_')]
class BookAPIController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ){
    }

    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(#[CurrentUser] ?User $user): JsonResponse
    {
        if($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }
        $books = $this->em->getRepository(Book::class)->findAll();
        $books = array_map(static function (Book $book) {
            return [
                'id' => $book->getId(),
                'title' => $book->getTitle(),
                'author' => $book->getAuthor(),
                'publishedDate' => $book->getPublishedAt()->format('Y-m-d'),
            ];
        }, $books);
        return new JsonResponse(
            $books,
        );
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(#[CurrentUser] ?User $user, int $id): JsonResponse
    {
        if($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }
        $book = $this->em->getRepository(Book::class)->find($id);
        if ($book === null) {
            return $this->json(['message' => 'Book not found'], 404);
        }
        return new JsonResponse([
            'id' => $book->getId(),
            'title' => $book->getTitle(),
            'author' => $book->getAuthor(),
            'publishedDate' => $book->getPublishedAt()->format('Y-m-d'),
            'description' => $book->getDescription(),
            'pages' => $book->getPages(),
            'isAvailable' => $book->isAvailable(),
            'type' => $book->getType()->value,
        ]);
    }

    #[Route('/{id}/summary', name: 'summary', methods: ['GET'])]
    public function summary(#[CurrentUser] ?User $user, int $id): JsonResponse
    {
        if($user === null) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }
        $book = $this->em->getRepository(Book::class)->find($id);
        if ($book === null) {
            return $this->json(['message' => 'Book not found'], 404);
        }
        return new JsonResponse([
            'id' => $book->getId(),
            'title' => $book->getTitle(),
            'summary' => $book->getSummary(),
        ]);
    }
}
