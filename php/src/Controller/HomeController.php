<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\User;
use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(BookRepository $bookRepository): Response
    {
        $featuredBook = $this->pickRandomOnlineBook($bookRepository);
        $featuredDescription = $this->truncateDescription($featuredBook?->getDescription());

        $collectionBook = null;
        $currentUser = $this->getUser();
        if ($currentUser instanceof User) {
            $firstUserBook = $currentUser->getBooks()->first();
            if ($firstUserBook instanceof Book) {
                $collectionBook = $firstUserBook;
            }
        }
        $onlineBooks = $bookRepository->findOnlineOrderedByPublishedAtDesc();
        $latestOnlineBook = array_slice($onlineBooks, 0, 4);
        $total = count($onlineBooks);
        $loaned = $bookRepository->countLoanedBooks();

        return $this->render('home/index.html.twig', [
            'total' => $total,
            'loaned' => $loaned,
            'controller_name' => 'HomeController',
            'featuredBook' => $featuredBook,
            'featuredDescription' => $featuredDescription,
            'collectionBook' => $collectionBook,
            'latestOnlineBook' => $latestOnlineBook,
        ]);
    }

    private function pickRandomOnlineBook(BookRepository $bookRepository): ?Book
    {
        $onlineBooks = $bookRepository->findOnlineOrderedByPublishedAtDesc();
        if ($onlineBooks === []) {
            return null;
        }

        return $onlineBooks[random_int(0, count($onlineBooks) - 1)];
    }

    private function truncateDescription(?string $description, int $maxLength = 240): string
    {
        $normalizedDescription = trim((string) $description);
        if ($normalizedDescription === '') {
            return 'No description provided.';
        }

        if (mb_strlen($normalizedDescription) <= $maxLength) {
            return $normalizedDescription;
        }

        return rtrim(mb_substr($normalizedDescription, 0, $maxLength - 3)).'...';
    }
}
