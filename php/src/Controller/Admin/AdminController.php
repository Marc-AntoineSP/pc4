<?php

namespace App\Controller\Admin;

use App\Entity\Book;
use App\Enum\BookTypeEnum;
use App\Repository\BookRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/librarian', name: 'librarian_')]
final class AdminController extends AbstractController
{
    /**
     * @var array<string>
     */
    private const REQUIRED_CSV_FIELDS = ['title', 'description', 'author', 'summary', 'type'];

    /**
     * @var array<string>
     */
    private const IGNORED_CSV_FIELDS = ['is_available', 'published_at'];

    #[Route('/', name: 'home')]
    public function index(BookRepository $bookRepository, UserRepository $userRepository): Response
    {
        return $this->render('librarian/librarian_home.html.twig', [
            'booksCount' => $bookRepository->countAllBooks(),
            'loanedBooksCount' => $bookRepository->countLoanedBooks(),
            'usersCount' => $userRepository->countNonAdminUsers(),
        ]);
    }

    #[Route('/upload', name: 'upload', methods: ['POST'])]
    public function upload(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('librarian_csv_upload', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid upload request.');

            return $this->redirectToRoute('librarian_home');
        }

        $csvFile = $request->files->get('csv_upload');
        if (!$csvFile instanceof UploadedFile) {
            $this->addFlash('error', 'Please select a CSV file before uploading.');

            return $this->redirectToRoute('librarian_home');
        }

        if ('csv' !== strtolower((string) $csvFile->getClientOriginalExtension())) {
            $this->addFlash('error', 'Only CSV files are supported.');

            return $this->redirectToRoute('librarian_home');
        }

        $csvContent = file_get_contents($csvFile->getPathname());
        if (false === $csvContent) {
            $this->addFlash('error', 'Unable to read the uploaded CSV file.');

            return $this->redirectToRoute('librarian_home');
        }

        $csvEncoder = new CsvEncoder();
        try {
            $decodedRows = $csvEncoder->decode($csvContent, CsvEncoder::FORMAT, [
                CsvEncoder::NO_HEADERS_KEY => true,
                CsvEncoder::AS_COLLECTION_KEY => true,
            ]);
        } catch (\Throwable) {
            $this->addFlash('error', 'Invalid CSV file format.');

            return $this->redirectToRoute('librarian_home');
        }

        if (!is_array($decodedRows) || $decodedRows === []) {
            $this->addFlash('error', 'CSV file is empty.');

            return $this->redirectToRoute('librarian_home');
        }

        $headerRow = array_shift($decodedRows);
        if (!is_array($headerRow) || $headerRow === []) {
            $this->addFlash('error', 'CSV headers are missing or invalid.');

            return $this->redirectToRoute('librarian_home');
        }

        $normalizedHeaders = array_map(
            static fn (mixed $value): string => self::normalizeHeader((string) $value),
            array_values($headerRow)
        );

        $headerIndexes = [];
        foreach ($normalizedHeaders as $index => $header) {
            if ($header !== '') {
                $headerIndexes[$header] = $index;
            }
        }

        $acceptedHeaders = [...self::REQUIRED_CSV_FIELDS, 'pages', 'page', ...self::IGNORED_CSV_FIELDS];
        foreach (array_keys($headerIndexes) as $headerName) {
            if (!in_array($headerName, $acceptedHeaders, true)) {
                $this->addFlash('error', sprintf('CSV column "%s" is not supported.', $headerName));

                return $this->redirectToRoute('librarian_home');
            }
        }

        foreach (self::REQUIRED_CSV_FIELDS as $requiredField) {
            if (!array_key_exists($requiredField, $headerIndexes)) {
                $this->addFlash('error', sprintf('CSV is missing required column "%s".', $requiredField));

                return $this->redirectToRoute('librarian_home');
            }
        }

        $pageField = array_key_exists('pages', $headerIndexes) ? 'pages' : (array_key_exists('page', $headerIndexes) ? 'page' : null);
        if (null === $pageField) {
            $this->addFlash('error', 'CSV is missing required column "pages" (or "page").');

            return $this->redirectToRoute('librarian_home');
        }

        $importedCount = 0;
        $skippedRows = 0;

        foreach ($decodedRows as $row) {
            if (!is_array($row)) {
                ++$skippedRows;
                continue;
            }

            if (self::isCsvRowEmpty($row)) {
                continue;
            }

            $rowData = [];
            foreach ($headerIndexes as $field => $columnIndex) {
                $rawValue = $row[$columnIndex] ?? '';
                $rowData[$field] = is_string($rawValue) ? trim($rawValue) : '';
            }

            $type = BookTypeEnum::tryFrom(strtolower($rowData['type'] ?? ''));
            $pages = $rowData[$pageField] ?? '';
            if (
                '' === ($rowData['title'] ?? '')
                || '' === ($rowData['description'] ?? '')
                || '' === ($rowData['summary'] ?? '')
                || '' === ($rowData['author'] ?? '')
                || null === $type
                || false === filter_var($pages, FILTER_VALIDATE_INT)
                || (int) $pages <= 0
            ) {
                ++$skippedRows;
                continue;
            }

            $book = (new Book())
                ->setTitle($rowData['title'])
                ->setDescription($rowData['description'])
                ->setSummary($rowData['summary'])
                ->setAuthor($rowData['author'])
                ->setType($type)
                ->setPages((int) $pages);

            $entityManager->persist($book);
            ++$importedCount;
        }

        if ($importedCount > 0) {
            $entityManager->flush();
            $this->addFlash('success', sprintf('Imported %d book(s) from CSV.', $importedCount));
        }

        if ($skippedRows > 0) {
            $this->addFlash('warning', sprintf('Skipped %d invalid row(s).', $skippedRows));
        }

        if (0 === $importedCount) {
            $this->addFlash('error', 'No book was imported. Check CSV headers and values.');
        }

        return $this->redirectToRoute('librarian_home');
    }

    private static function normalizeHeader(string $header): string
    {
        $normalized = strtolower(trim($header));
        $normalized = str_replace("\xEF\xBB\xBF", '', $normalized);
        $normalized = str_replace([' ', '-'], '_', $normalized);

        return match ($normalized) {
            'isavailable' => 'is_available',
            'publishedat' => 'published_at',
            default => $normalized,
        };
    }

    /**
     * @param array<int, mixed> $row
     */
    private static function isCsvRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
