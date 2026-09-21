<?php

namespace App\Controller;

use App\DTO\ListingForm;
use App\DTO\ListingFormEdit;
use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Listing;
use App\Entity\Picture;
use App\Entity\User;
use App\Enum\BookCondition;
use App\Enum\Statut;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use App\Repository\ListingRepository;
use App\Services\BookApiService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
final class ListingController extends AbstractController
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializerInterface)
    {
        $this->serializer = $serializerInterface;
    }


    #[Route('/booklistings', name: 'app_book_listing', methods: ['GET'])]
    public function listings(ListingRepository $repository): JsonResponse
    {
        $listings = $repository->findAll();

        $jsonListing = $this->serializer->serialize($listings, 'json', ['groups' => 'getListing']);


        return new JsonResponse($jsonListing, 200, [], true);
    }

    #[Route('/listing/{id}', name: 'api_detailListing', methods: ['GET'])]
    public function detailListing(?Listing $listing)
    {
        if (!$listing) {
            return $this->json(['error' => 'Listing not found'], 404);
        }
        $jsonListing = $this->serializer->serialize($listing, 'json', ['groups' => 'getListing']);
        return new JsonResponse($jsonListing, Response::HTTP_OK, [], true);
    }

    #[Route('/listings/me', name: 'api_my_listings', methods: ['GET'])]
    public function listingsUser(ListingRepository $repository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $listings = $repository->findBy(['user' => $user]);

        $jsonListing = $this->serializer->serialize($listings, 'json', ['groups' => 'getListing']);


        return new JsonResponse($jsonListing, 200, [], true);
    }

    #[Route('/new/listing', name: 'api_new_Listing', methods: ['POST'])]
    public function newListing(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        BookRepository $bookRepository,
        AuthorRepository $authorRepository,
        CategoryRepository $categoryRepository,
        BookApiService $apiService
    ) {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Désérialiser en DTO
        $data = $request->request->get('data');

        if (!$data) {
            return $this->json([
                'error' => 'Le champ data est manquant'
            ], Response::HTTP_BAD_REQUEST);
        }

        $dto = $this->serializer->deserialize(
            $data,
            ListingForm::class,
            'json'
        );

        // Valider le DTO
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $isbn = $dto->isbn;

        // Chercher ou créer le livre
        $book = $bookRepository->findOneBy(['isbn' => $isbn]);

        if (!$book) {
            $googleResult = $apiService->searchBookByIsbn($isbn);

            if (empty($googleResult['items'])) {
                return $this->json(['error' => 'Livre non trouvé'], Response::HTTP_NOT_FOUND);
            }

            $bookData = $googleResult['items'][0]['volumeInfo'];

            $book = new Book();
            $book->setIsbn($isbn);
            $book->setTitle($bookData['title'] ?? 'Titre inconnu');

            $authorNames = $bookData['authors'] ?? [];
            foreach ($authorNames as $authorName) {
                $author = $authorRepository->findOneBy(['name' => $authorName]);

                if (!$author) {
                    $author = new Author();
                    $author->setName($authorName);
                    $em->persist($author);
                }

                $book->addAuthor($author);
            }

            $em->persist($book);
            $em->flush();
        }

        // Créer l'annonce
        $listing = new Listing();
        $listing->setTitle($dto->title);
        $listing->setPrice($dto->price);
        $listing->setLanguage($dto->language);
        $listing->setPublicationDate(new DateTimeImmutable());
        $listing->setStatut(Statut::For_Sale);
        if ($dto->category !== null) {
            $category = $categoryRepository->find($dto->category);

            if (!$category) {
                return $this->json([
                    'error' => 'Catégorie introuvable'
                ], Response::HTTP_NOT_FOUND);
            }

            $listing->setCategory($category);
        }
        $listing->setBook($book);
        $listing->setUser($user);

        // Gérer l'état (condition) si tu as un enum
        if ($dto->book_condition) {
            $condition = BookCondition::tryFrom($dto->book_condition);
            if ($condition) {
                $listing->setBookCondition($condition);
            }
        }

        $picture = new Picture();

        $frontCoverFile = $request->files->get('frontCover');
        $backCoverFile = $request->files->get('backCover');

        if ($frontCoverFile) {
            $picture->setFrontCoverFile($frontCoverFile);
        }

        if ($backCoverFile) {
            $picture->setBackCoverFile($backCoverFile);
        }

        $picture->setListing($listing);
        $picture->setUpdatedAt(new \DateTimeImmutable());

        $em->persist($picture);
        $em->persist($listing);
        $em->flush();

        $jsonListing = $this->serializer->serialize($listing, 'json', ['groups' => 'getListing']);

        return new JsonResponse($jsonListing, Response::HTTP_CREATED, [], true);
    }

    #[Route('/edit/listing/{id}', name: 'api_edit_Listing', methods: ['PUT'])]
    public function editListing(Request $request, Listing $currentListing, EntityManagerInterface $em, ValidatorInterface $validator)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $request->request->get('data');

        $dto = $this->serializer->deserialize($data, ListingFormEdit::class, 'json');

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        if ($dto->title !== null) {
            $currentListing->setTitle($dto->title);
        }

        if ($dto->price !== null) {
            $currentListing->setPrice($dto->price);
        }

        if ($dto->book_condition !== null) {
            $currentListing->setBookCondition($dto->book_condition);
        }

        if ($dto->language !== null) {
            $currentListing->setLanguage($dto->language);
        }

        $frontCoverFile = $request->files->get('frontCover');
        $backCoverFile = $request->files->get('backCover');

        if ($frontCoverFile || $backCoverFile) {
            $picture = $currentListing->getPicture();

            // Si la Picture n'existe pas encore → on la crée
            if (!$picture) {
                $picture = new Picture();
                $picture->setListing($currentListing);
                $currentListing->setPicture($picture);
                $em->persist($picture);
            }

            if ($frontCoverFile) {
                $picture->setFrontCoverFile($frontCoverFile);
            }
            if ($backCoverFile) {
                $picture->setBackCoverFile($backCoverFile);
            }

            $picture->setUpdatedAt(new \DateTimeImmutable());
        }

        $em->flush();

        $jsonListing = $this->serializer->serialize($currentListing, 'json', ['groups' => 'getListing']);
        return new JsonResponse($jsonListing, 200, [], true);
    }

    #[Route('/delete/listing/{id}', name: 'api_delete_Listing', methods: ['DELETE'])]
    public function deleteListing(Listing $listing, EntityManagerInterface $em)
    {
        $em->remove($listing);
        $em->flush();
        return new JsonResponse(null, 204);
    }
}
