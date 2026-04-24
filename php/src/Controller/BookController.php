<?php

namespace App\Controller;

use App\Entity\Book;
use App\Form\BookCreationType;
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
    public function all(EntityManagerInterface $entityManager): Response
    {
        $books = $entityManager->getRepository(Book::class)->findAll();
        return $this->render('book/book_all.html.twig', [
            'books' => $books,
        ]);
    }

    #[Route('/{id}', name: 'detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detail(Book $book): Response
    {
        return $this->render('book/book_detail.html.twig', [
            'book' => $book,
        ]);
    }
}
