<?php

namespace App\Serializer\Normalizer;

use App\Entity\Farm;
use App\Entity\Product;
use App\Entity\Media;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AutoDiscoveryNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private NormalizerInterface $normalizer,
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function normalize($object, ?string $format = null, array $context = []): array
    {
        $className = (new ReflectionClass($object))->getShortName();
        $className = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
        $data = $this->normalizer->normalize($object, $format, $context);
        $data['_links'] = [
            "up" => [
                "method" => ["GET"],
                "path" => $this->urlGenerator->generate("api_get_all_" . $className . "s"),
            ],
            "self" => [
                "method" => ["GET"],
                "path" => $this->urlGenerator->generate("api_get_" . $className, $this->getRouteParameters($className, $data["id"])),
            ],
        ];

        // Ajouter automatiquement les médias pour Product et Farm
        if ($object instanceof Product || $object instanceof Farm) {
            $entityType = strtolower($className);
            $mediaRepo = $this->entityManager->getRepository(Media::class);
            $medias = $mediaRepo->findBy(['entityType' => $entityType, 'entityId' => $object->getId()]);
            
            // Sérialiser chaque média individuellement avec le groupe 'media' et gestion des références circulaires
            $mediaContext = array_merge($context, [
                'groups' => ['media'],
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);
            $data['medias'] = array_map(function (Media $media) use ($format, $mediaContext) {
                return $this->normalizer->normalize($media, $format, $mediaContext);
            }, $medias);
        }

        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return ($data instanceof Farm || $data instanceof Product) && $format === 'json';
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Farm::class => true,
            Product::class => true
        ];
    }

    private function getRouteParameters($className, $id)
    {
        // Retourner le bon nom de paramètre selon l'entité
        switch ($className) {
            case 'product':
                return ['id' => $id];
            case 'farm':
                return ['farm' => $id];
            default:
                return [$className => $id];
        }
    }
}
