<?php

namespace App\Controller;

use App\DTO\RegisterDto;
use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
final class AuthController extends AbstractController
{
    private SerializerInterface $serializer;
    private JWTTokenManagerInterface $jWtToken;

    public function __construct(SerializerInterface $serializer_interface, JWTTokenManagerInterface $token)
    {
        $this->serializer = $serializer_interface;
        $this->jWtToken = $token;
    }


    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $userPasswordHasher,
        UserRepository $userRepository
    ): JsonResponse {
        $dto = $this->serializer->deserialize($request->getContent(), RegisterDto::class, 'json');

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        if ($userRepository->findOneBy(['email' => $dto->email])) {
            return $this->json(['message' => 'Cet email est déjà utilisé'], 409);
        }

        if ($userRepository->findOneBy(['pseudo' => $dto->pseudo])) {
            return $this->json(['message' => 'Ce pseudo n\'est pas disponible'], 409);
        }

        $user = new User();
        $user->setLastname($dto->lastname);
        $user->setFirstname($dto->firstname);
        $user->setPseudo($dto->pseudo);
        $user->setEmail($dto->email);

        $passwordHashed =  $userPasswordHasher->hashPassword($user, $dto->password);
        $user->setPassword($passwordHashed);
        $user->setRole('ROLE_USER');

        $user->setCreatedAt(new DateTimeImmutable());

        $em->persist($user);
        $em->flush();

        $token = $this->jWtToken->create($user);

        $response = $this->json([
            'user' => $user,
            'JWT_TOKEN' => $token,
        ], Response::HTTP_CREATED, [], ['groups' => 'getUser']);

        $response->headers->setCookie(
            Cookie::create('JWT_TOKEN')
                ->withValue($token)
                ->withHttpOnly(true)
                ->withSecure(true)
                ->withSameSite('none')
                ->withPath('/')
        );

        return $response;
    }




    #[Route('/register/admin', name: 'api_register_admin', methods: ['POST'])]
    public function registerAdmin(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $userPasswordHasher,
    ): JsonResponse {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $password = $user->getPassword();
        $passwordHashed =  $userPasswordHasher->hashPassword($user, $password);
        $user->setPassword($passwordHashed);
        $user->setRole('ROLE_ADMIN');
        $user->setCreatedAt(new DateTimeImmutable());
        $em->persist($user);
        $em->flush();

        return $this->json([
            'user' => $user,
        ], Response::HTTP_CREATED, [], ['groups' => 'getUser']);
    }


    #[Route('/profile', name: 'api_profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json([
                'code' => 401,
                'message' => 'User not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }
        $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'getUser']);

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    #[Route('/profile/{id}', name: 'api_profile_id', methods: ['GET'])]
    public function profileByUser(?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'getUser']);

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout()
    {
        $response = new JsonResponse([
            'message' => 'Déconnexion réussie'
        ]);

        $response->headers->setCookie(
            Cookie::create('JWT_TOKEN')
                ->withValue('')
                ->withHttpOnly(true)
                ->withSecure(true)
                ->withSameSite('none')
                ->withPath('/')
                ->withExpires(new \DateTimeImmutable('@0'))
        );

        return $response;
    }
}
