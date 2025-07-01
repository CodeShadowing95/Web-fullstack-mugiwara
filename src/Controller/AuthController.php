<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Persona;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\SerializerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenApi\Attributes as OA;

class AuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    #[OA\Tag(name: 'Authentification')]
    #[OA\RequestBody(
        description: 'Données d\'inscription utilisateur',
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password', 'firstName', 'lastName'],
            properties: [
                new OA\Property(property: 'email', type: 'string', example: 'user@email.com'),
                new OA\Property(property: 'password', type: 'string', example: 'MotDePasse123!'),
                new OA\Property(property: 'firstName', type: 'string', example: 'Jean'),
                new OA\Property(property: 'lastName', type: 'string', example: 'Dupont'),
                new OA\Property(property: 'farmer', type: 'boolean', example: false),
                new OA\Property(property: 'address', type: 'string', example: '1 rue de la paix'),
                new OA\Property(property: 'zipCode', type: 'string', example: '75000'),
                new OA\Property(property: 'city', type: 'string', example: 'Paris'),
                new OA\Property(property: 'phoneNumber', type: 'string', example: '0601020304'),
                new OA\Property(property: 'birthDate', type: 'string', format: 'date', example: '1990-01-01'),
                new OA\Property(property: 'gender', type: 'string', example: 'M')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Utilisateur créé et connecté avec succès',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOi...'),
                new OA\Property(property: 'message', type: 'string', example: 'Utilisateur créé et connecté avec succès')
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Champs manquants ou format invalide',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Champs manquants')
            ]
        )
    )]
    #[OA\Response(
        response: 409,
        description: 'Email déjà utilisé',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Email déjà utilisé')
            ]
        )
    )]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['email'], $data['password'], $data['firstName'], $data['lastName'])) {
            return $this->json(['error' => 'Champs manquants'], Response::HTTP_BAD_REQUEST);
        }
        // Vérifier si l'email existe déjà
        $existingPersona = $em->getRepository(Persona::class)->findOneBy(['email' => $data['email']]);
        if ($existingPersona) {
            return $this->json(['error' => 'Email déjà utilisé'], Response::HTTP_CONFLICT);
        }
        $user = new User();
        $user->setUuid(uniqid('', true));
        $user->setRoles(['ROLE_USER']);
        if (isset($data['farmer']) && $data['farmer'] === true) {
            $user->setRoles(['ROLE_FARMER']);
        }
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        $em->persist($user);

        $persona = new Persona();
        $persona->setEmail($data['email']);
        $persona->setFirstName($data['firstName']);
        $persona->setLastName($data['lastName']);
        $persona->setUser($user);
        if (isset($data['address'])) $persona->setAddress($data['address']);
        if (isset($data['zipCode'])) $persona->setZipCode($data['zipCode']);
        if (isset($data['city'])) $persona->setCity($data['city']);
        if (isset($data['phoneNumber'])) $persona->setPhoneNumber($data['phoneNumber']);
        if (isset($data['birthDate'])) $persona->setBirthDate(new \DateTime($data['birthDate']));
        if (isset($data['gender'])) $persona->setGender($data['gender']);
        $em->persist($persona);
        $em->flush();

        // Générer le token JWT pour l'utilisateur nouvellement inscrit
        $token = $jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'message' => 'Utilisateur créé et connecté avec succès'
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/current-user', name: 'api_current_user', methods: ['GET'])]
    #[OA\Tag(name: 'Authentification')]
    #[OA\Response(
        response: 200,
        description: 'Retourne l\'utilisateur courant',
        content: new OA\JsonContent(
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Non authentifié',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Non authentifié')
            ]
        )
    )]
    public function getCurrentUser(Security $security): JsonResponse
    {
        /** @var User|null $user */
        $user = $security->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json($user, Response::HTTP_OK, [], ['groups' => 'user:read']);
    }

    #[Route('/api/become-farmer', name: 'api_become_farmer', methods: ['POST'])]
    #[OA\Tag(name: 'Authentification')]
    #[OA\Response(
        response: 200,
        description: 'L\'utilisateur est maintenant un fermier',
        content: new OA\JsonContent(
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Non authentifié',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Non authentifié')
            ]
        )
    )]
    public function becomeFarmer(Request $request, Security $security, EntityManagerInterface $em): JsonResponse
    {
        /** @var User|null $user */
        $user = $security->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        $user->setRoles(['ROLE_FARMER']);
        $em->flush();
        return $this->json($user, Response::HTTP_OK, [], ['groups' => 'user:read']);
    }
}
