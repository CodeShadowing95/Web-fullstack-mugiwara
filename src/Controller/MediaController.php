<?php

namespace App\Controller;

use App\Entity\Media;
use App\Utils\MediaUploader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

final class MediaController extends AbstractController
{
    #[Route('/', name: 'app_media')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/MediaController.php',
        ]);
    }
    #[Route('/api/public/v1/media/{media}', name: 'api_get_media', methods: ['GET'])]
    #[OA\Tag(name: 'Media')]
    #[OA\Parameter(
        name: 'media',
        in: 'path',
        description: 'ID du média',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Retourne un média',
        content: new OA\JsonContent(ref: new OA\Model(type: Media::class))
    )]
    #[OA\Response(
        response: 404,
        description: 'Média non trouvé',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Média non trouvé')
            ]
        )
    )]
    public function get(Media $media, UrlGeneratorInterface $urlGenerator, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $location = $urlGenerator->generate("app_media", [], UrlGeneratorInterface::ABSOLUTE_URL);
        $location = $location . str_replace("/public/", "", $media->getPublicPath() . "/" . $media->getRealPath());

        return $media ?
            new JsonResponse($serializer->serialize($media, "json", []), Response::HTTP_OK, ["Location" => $location], true) :
            new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    #[Route('/api/v1/media', name: 'api_create_media', methods: ['POST'])]
    #[OA\Tag(name: 'Media')]
    #[OA\RequestBody(
        description: 'Upload d\'un média',
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['file', 'entityType', 'entityId'],
                properties: [
                    new OA\Property(property: 'file', type: 'string', format: 'binary'),
                    new OA\Property(property: 'entityType', type: 'string', example: 'product'),
                    new OA\Property(property: 'entityId', type: 'integer', example: 1)
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Média créé avec succès',
        content: new OA\JsonContent(ref: new OA\Model(type: Media::class))
    )]
    #[OA\Response(
        response: 400,
        description: 'Entrée invalide',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Erreur lors de l\'upload du fichier')
            ]
        )
    )]
    public function create(
        Request $request,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator,
        MediaUploader $mediaUploader
    ): JsonResponse {
        $file = $request->files->get('file');
        $media = $mediaUploader->upload(
            $file,
            'media',
            $request->request->get('entityType'),
            $request->request->get('entityId')
        );
        $jsonFile = $serializer->serialize($media, "json");
        $location = $urlGenerator->generate('api_get_media', ["media" => $media->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonFile, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route("/api/v1/media/{media}", name:"api_delete_media", methods: ["DELETE"])]
    #[OA\Tag(name: 'Media')]
    #[OA\Parameter(
        name: 'media',
        in: 'path',
        description: 'ID du média à supprimer',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 204,
        description: 'Média supprimé avec succès'
    )]
    #[OA\Response(
        response: 404,
        description: 'Média non trouvé',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Média non trouvé')
            ]
        )
    )]
    public function delete(Media $media, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($media);
        $entityManager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}