<?php

namespace App\Controller\Admin;

use App\Repository\BookRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/librarian', name: 'librarian_')]
final class AdminController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(BookRepository $bookRepository, UserRepository $userRepository): Response
    {
        return $this->render('librarian/librarian_home.html.twig', [
            'booksCount' => $bookRepository->countAllBooks(),
            'loanedBooksCount' => $bookRepository->countLoanedBooks(),
            'usersCount' => $userRepository->countNonAdminUsers(),
        ]);
    }
}
