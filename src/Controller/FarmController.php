<?php

namespace App\Controller;

use App\Entity\Farm;
use App\Entity\FarmUser;
use OpenApi\Attributes as OA;
use App\Repository\FarmRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\FarmTypeRepository;

final class FarmController extends AbstractController
{
    #[Route('api/public/v1/farms/farmer/{id}', name: 'api_get_farms_by_farmer', methods: ['GET'])]
    #[OA\Tag(name: 'Farms')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID du fermier',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des fermes appartenant au fermier',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Farm::class, groups: ['farm','stats']))
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Fermier non trouvé',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Fermier non trouvé')
            ]
        )
    )]
    public function getFarmsByFarmer(int $id, FarmRepository $farmRepository, SerializerInterface $serializer): JsonResponse
    {
        $farms = $farmRepository->findByFarmerId($id);
        $jsonData = $serializer->serialize($farms, 'json', ['groups' => ['farm', 'stats']]);
        return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
    }

    #[Route('api/public/v1/farms', name: 'api_get_all_farms', methods: ['GET'])]
    #[OA\Tag(name: 'Farms')]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste de toutes les fermes',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Farm::class, groups: ['farm','stats']))
        )
    )]
    /**
     * Summary of getAll
     * @param \App\Repository\FarmRepository $farmFarmRepository
     * @param \Symfony\Component\Serializer\SerializerInterface $serializer
     * @return JsonResponse
     */
    public function getAll(FarmRepository $farmFarmRepository, SerializerInterface $serializer): JsonResponse
    {
        $farms = $farmFarmRepository->findAll();
        $jsonData = $serializer->serialize($farms, 'json', ['groups' => ['farm', 'stats']]);
        return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
    }

    #[Route('api/public/v1/farm/{farm}', name: 'api_get_farm', methods: ['GET'])]
    #[OA\Tag(name: 'Farms')]
    #[OA\Parameter(
        name: 'farm',
        in: 'path',
        description: 'ID de la ferme',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Retourne une ferme',
        content: new OA\JsonContent(ref: new Model(type: Farm::class, groups: ['farm', 'stats', 'farm_products']))
    )]
    #[OA\Response(
        response: 404,
        description: 'Ferme non trouvée',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Ferme non trouvée')
            ]
        )
    )]
    /**
     * Summary of get
     * @param \App\Entity\Farm $farm
     * @param \Symfony\Component\Serializer\SerializerInterface $serializer
     * @param \App\Repository\ProductRepository $productRepository
     * @return JsonResponse
     */
    public function get(Farm $farm, SerializerInterface $serializer, ProductRepository $productRepository): JsonResponse
    {
        $products = $productRepository->findBy(['farm' => $farm]);
        $jsonData = $serializer->serialize([
            'farm' => $farm,
            'products' => $products
        ], 'json', ['groups' => ['farm', 'stats', 'farm_products', 'product']]);
        return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
    }

    #[Route('api/v1/create-farm', name: 'api_create_farm', methods: ['POST'])]
    #[OA\Tag(name: 'Farms')]
    #[OA\RequestBody(
        description: 'Données de la ferme',
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'address', 'city', 'zipCode'],
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'Nom de la ferme', example: 'Ferme du Moulin'),
                new OA\Property(property: 'description', type: 'string', description: 'Description de la ferme', example: 'Production bio'),
                new OA\Property(property: 'address', type: 'string', description: 'Adresse de la ferme', example: '1 rue des champs'),
                new OA\Property(property: 'city', type: 'string', description: 'Ville de la ferme', example: 'Lyon'),
                new OA\Property(property: 'zipCode', type: 'string', description: 'Code postal', example: '69000'),
                new OA\Property(property: 'region', type: 'string', description: 'Région', example: 'Auvergne-Rhône-Alpes'),
                new OA\Property(property: 'coordinates', type: 'object', properties: [
                    new OA\Property(property: 'lat', type: 'string', example: '45.75'),
                    new OA\Property(property: 'lng', type: 'string', example: '4.85')
                ]),
                new OA\Property(property: 'phone', type: 'string', description: 'Téléphone', example: '0601020304'),
                new OA\Property(property: 'email', type: 'string', description: 'Email', example: 'ferme@email.com'),
                new OA\Property(property: 'website', type: 'string', description: 'Site web', example: 'https://ferme.com'),
                new OA\Property(property: 'farmSize', type: 'string', description: 'Taille de la ferme', example: '10ha'),
                new OA\Property(property: 'mainProducts', type: 'array', items: new OA\Items(type: 'string'), example: ['pommes', 'poires']),
                new OA\Property(property: 'seasonality', type: 'string', description: 'Saisonnalité', example: 'Toute l\'année'),
                new OA\Property(property: 'deliveryZones', type: 'array', items: new OA\Items(type: 'string'), example: ['Lyon', 'Villeurbanne']),
                new OA\Property(property: 'deliveryMethods', type: 'array', items: new OA\Items(type: 'string'), example: ['livraison', 'retrait']),
                new OA\Property(property: 'minimumOrder', type: 'string', description: 'Commande minimum', example: '20€'),
                new OA\Property(property: 'profileImage', type: 'string', description: 'Image de profil', example: 'https://img.com/ferme.jpg'),
                new OA\Property(property: 'galleryImages', type: 'array', items: new OA\Items(type: 'string'), example: ['https://img.com/1.jpg']),
                new OA\Property(property: 'types', type: 'array', items: new OA\Items(type: 'integer'), example: [1,2])
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Retourne la ferme créée',
        content: new OA\JsonContent(ref: new Model(type: Farm::class))
    )]
    #[OA\Response(
        response: 400,
        description: 'Entrée invalide',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Erreur de validation')
            ]
        )
    )]
    /**
     * Summary of create
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Routing\Generator\UrlGeneratorInterface $urlGenerator
     * @param \App\Repository\ProductRepository $productRepository
     * @param \App\Repository\FarmTypeRepository $farmTypeRepository
     * @param \App\Repository\UserRepository $userRepository
     * @param \Symfony\Component\Serializer\SerializerInterface $serializer
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @return JsonResponse
     */
    public function create(Request $request, UrlGeneratorInterface $urlGenerator, ProductRepository $productRepository, FarmTypeRepository $farmTypeRepository, UserRepository $userRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        $farm = $serializer->deserialize($request->getContent(), Farm::class, 'json');
        $requestData = $request->toArray();

        // Supprimer les FarmType vides créés par la désérialisation
        foreach ($farm->getTypes() as $type) {
            if (null === $type->getId()) {
                $farm->removeType($type);
            }
        }

        // Les produits seront ajoutés ultérieurement

        // Handle farm types
        $farm->getTypes()->clear();
        $typesData = $requestData['types'] ?? [];
        foreach ($typesData as $typeId) {
            $type = $farmTypeRepository->find($typeId);
            if ($type) {
                $farm->addType($type);
            }
        }

        $farm->setStatus('on');
        // Ajout automatique du FarmUser pour le user connecté en owner
        $user = $this->getUser();
        if ($user) {
            $farmUser = new FarmUser();
            $farmUser->setUser($user);
            $farmUser->setFarm($farm);
            $farmUser->setRole('owner');
            $entityManager->persist($farmUser);
        }
        $errors = $validator->validate($farm);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }
        $entityManager->persist($farm);
        $entityManager->flush();
        $jsonData = $serializer->serialize($farm, 'json', [
            'groups' => ['farm', 'farm_products'],
            'enable_max_depth' => true
        ]);
        $location = $urlGenerator->generate('api_get_farm', ['farm' => $farm->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonData, Response::HTTP_CREATED, ["location" => $location], true);
    }

    #[Route("api/v1/farm/{farm}", name:"api_update_farm", methods: ["PATCH"])]
    #[OA\Tag(name: 'Farms')]
    #[OA\Parameter(
        name: 'farm',
        in: 'path',
        description: 'ID de la ferme à modifier',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        description: 'Champs à modifier',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Nouvelle ferme'),
                new OA\Property(property: 'description', type: 'string', example: 'Description modifiée'),
                new OA\Property(property: 'address', type: 'string', example: '2 rue modifiée'),
                new OA\Property(property: 'city', type: 'string', example: 'Paris'),
                new OA\Property(property: 'zipCode', type: 'string', example: '75000'),
                new OA\Property(property: 'region', type: 'string', example: 'Île-de-France'),
                new OA\Property(property: 'types', type: 'array', items: new OA\Items(type: 'integer'), example: [1])
            ]
        )
    )]
    #[OA\Response(
        response: 204,
        description: 'Ferme modifiée avec succès'
    )]
    #[OA\Response(
        response: 400,
        description: 'Entrée invalide',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Erreur de validation')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Ferme non trouvée',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Ferme non trouvée')
            ]
        )
    )]
    /**
     * Summary of update
     * @param \App\Entity\Farm $farm
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Repository\ProductRepository $productRepository
     * @param \App\Repository\FarmTypeRepository $farmTypeRepository
     * @param \Symfony\Component\Serializer\SerializerInterface $serializer
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @return JsonResponse
     */
    public function update(Farm $farm, Request $request, ProductRepository $productRepository, FarmTypeRepository $farmTypeRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        $updatedFarm = $serializer->deserialize(
            $request->getContent(), Farm::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $farm]
        );

        $requestData = $request->toArray();

        // Handle products
        // if (isset($requestData['products'])) {
        //     $farm->getProducts()->clear();
        //     foreach ($requestData['products'] as $productId) {
        //         $product = $productRepository->find($productId);
        //         if ($product) {
        //             $farm->addProduct($product);
        //         }
        //     }
        // }

        // Handle farm types
        if (isset($requestData['types'])) {
            $farm->getTypes()->clear();
            foreach ($requestData['types'] as $typeId) {
                $type = $farmTypeRepository->find($typeId);
                if ($type) {
                    $farm->addType($type);
                }
            }
        }

        $errors = $validator->validate($updatedFarm);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($updatedFarm);
        $entityManager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT, [], true);
    }

    #[Route("api/v1/farm/{farm}", name:"api_delete_farm", methods: ["DELETE"])]
    #[OA\Tag(name: 'Farms')]
    #[OA\Parameter(
        name: 'farm',
        in: 'path',
        description: 'ID de la ferme à supprimer',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 204,
        description: 'Ferme supprimée avec succès'
    )]
    #[OA\Response(
        response: 404,
        description: 'Ferme non trouvée',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Ferme non trouvée')
            ]
        )
    )]
    /**
     * Summary of delete
     * @param \App\Entity\Farm $farm
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    public function delete(Farm $farm, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);

        if(isset($requestData['hard']) && $requestData['hard'] === true) {
            $entityManager->remove($farm);
        } else {
            $farm->softDelete();
        }

        $entityManager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT, []);
    }

    #[Route('api/v1/farm/{farm}/members', name: 'api_add_farm_member', methods: ['POST'])]
    #[OA\Tag(name: 'Farms')]
    #[OA\Parameter(
        name: 'farm',
        in: 'path',
        description: 'ID de la ferme',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        description: 'Données du membre à ajouter',
        required: true,
        content: new OA\JsonContent(
            required: ['user_id'],
            properties: [
                new OA\Property(property: 'user_id', type: 'integer', example: 5),
                new OA\Property(property: 'role', type: 'string', example: 'member')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Membre ajouté à la ferme',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Membre ajouté à la ferme')
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Entrée invalide',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'user_id manquant')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Utilisateur non trouvé',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Utilisateur non trouvé')
            ]
        )
    )]
    /**
     * Ajoute un membre (FarmUser) à une ferme existante
     * @param Farm $farm
     * @param Request $request
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    public function addMember(
        Farm $farm,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = $request->toArray();
        if (!isset($data['user_id'])) {
            return new JsonResponse(['error' => 'user_id manquant'], Response::HTTP_BAD_REQUEST);
        }
        $user = $userRepository->find($data['user_id']);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $role = $data['role'] ?? 'member';
        $farmUser = new FarmUser();
        $farmUser->setUser($user);
        $farmUser->setFarm($farm);
        $farmUser->setRole($role);
        $errors = $validator->validate($farmUser);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }
        $entityManager->persist($farmUser);
        $entityManager->flush();
        return new JsonResponse(['message' => 'Membre ajouté à la ferme'], Response::HTTP_CREATED);
    }
}
