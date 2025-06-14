<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Persona;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): JsonResponse
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

        return $this->json(['message' => 'Utilisateur créé avec succès'], Response::HTTP_CREATED);
    }
}

