<?php

namespace App\Utils;

use App\Entity\Media;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaUploader
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function upload(UploadedFile $file, string $publicPath = 'media', ?string $entityType = null, ?int $entityId = null): Media
    {
        $media = new Media();
        $media->setFile($file)
            ->setRealName($file->getClientOriginalName())
            ->setPublicPath($publicPath)
            ->setStatus('on')
            ->setUploadedAt(new \DateTime())
            ->setEntityType($entityType)
            ->setEntityId($entityId);

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $media;
    }
}