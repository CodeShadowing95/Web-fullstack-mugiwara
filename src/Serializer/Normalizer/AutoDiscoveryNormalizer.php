<?php

namespace App\Serializer\Normalizer;

use App\Entity\Farm;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AutoDiscoveryNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private NormalizerInterface $normalizer,
        private UrlGeneratorInterface $urlGenerator
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
                "path" => $this->urlGenerator->generate("api_get_all_" . $className),
            ],
            "self" => [
                "method" => ["GET"],
                "path" => $this->urlGenerator->generate("api_get_" . $className, [$className => $data["id"]]),
            ],
        ];

        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return ($data instanceof Farm) && $format === 'json';
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Farm::class => true];
    }
}
