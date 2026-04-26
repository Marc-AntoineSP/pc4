<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Book;
use App\Entity\User;
use App\Form\BookCreationType;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/book', name: 'app_book_')]
final class BookController extends AbstractController
{
    #[Route('/add', name: 'add', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $book = new Book();
        $form = $this->createForm(BookCreationType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($book);
            $entityManager->flush();

            $this->addFlash('success', 'Book added successfully.');

            return $this->redirectToRoute('app_book_add');
        }

        return $this->render('book/book_add.html.twig', [
            'bookForm' => $form->createView(),
        ]);
    }

    #[Route('/all', name: 'all', methods: ['GET'])]
    public function all(BookRepository $bookRepository): Response
    {
        $books = $bookRepository->findOnlineOrderedByPublishedAtDesc();

        return $this->render('book/book_all.html.twig', [
            'books' => $books,
        ]);
    }

    #[Route('/{id}', name: 'detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detail(Book $book): Response
    {
        if (!$book->isOnline()) {
            $this->addFlash('error', 'This book is not available.');

            return $this->redirectToRoute('app_book_all');
        }

        return $this->render('book/book_detail.html.twig', [
            'book' => $book,
        ]);
    }

    #[Route('/{id}/loan', name: 'loan', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function loan(Book $book, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$book->isOnline()) {
            $this->addFlash('error', 'This book is not available.');

            return $this->redirectToRoute('app_book_all');
        }

        if (!$this->isCsrfTokenValid('loan_book_' . $book->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid loan request.');

            return $this->redirectToRoute('app_book_detail', ['id' => $book->getId()]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $bookOwner = $book->getUserEntity();
        if (null !== $bookOwner) {
            if ($bookOwner->getId() === $user->getId()) {
                $this->addFlash('info', 'You already loaned this book.');
            } else {
                $this->addFlash('error', 'This book is already loaned.');
            }

            return $this->redirectToRoute('app_book_detail', ['id' => $book->getId()]);
        }

        if ($user->getBooks()->count() >= 2) {
            $this->addFlash('error', 'You already have 2 books on loan.');

            return $this->redirectToRoute('app_book_detail', ['id' => $book->getId()]);
        }

        $user->addBook($book);
        $entityManager->flush();
        $this->addFlash('success', 'Book loaned successfully.');

        return $this->redirectToRoute('app_book_detail', ['id' => $book->getId()]);
    }

    #[Route('/{id}/return', name: 'return', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function returnBook(Book $book, Request $request, EntityManagerInterface $entityManager): Response
    {
        $redirectToMyBooks = 'app_book_me' === (string) $request->request->get('_redirect_route');
        $redirectRoute = $redirectToMyBooks ? 'app_book_me' : 'app_book_detail';
        $redirectParams = $redirectToMyBooks ? [] : ['id' => $book->getId()];

        if (!$this->isCsrfTokenValid('return_book_' . $book->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid return request.');

            return $this->redirectToRoute($redirectRoute, $redirectParams);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $bookOwner = $book->getUserEntity();
        if (null === $bookOwner || $bookOwner->getId() !== $user->getId()) {
            $this->addFlash('error', 'You can only return your own loaned books.');

            return $this->redirectToRoute($redirectRoute, $redirectParams);
        }

        $user->removeBook($book);
        $entityManager->flush();
        $this->addFlash('success', 'Book returned successfully.');

        return $this->redirectToRoute($redirectRoute, $redirectParams);
    }

    #[Route(path: '/me', name: 'me', methods: 'GET')]
    public function myBooks(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $books = $user->getBooks();

        return $this->render('book/book_me.html.twig', [
            'books' => $books,
        ]);
    }
}
