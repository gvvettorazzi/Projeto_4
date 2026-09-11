<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\File;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final class CreateFileAction
{
    private const FILE_FIELD = 'file';

    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    private const REQUIRED_ROLE = 'ROLE_USER';

    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'text/plain',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly Security $security
    ) {
    }

    public function __invoke(Request $request): File
    {
        $this->assertAuthorized();

        $uploadedFile = $this->extractUploadedFile($request);

        $this->validateUploadedFile($uploadedFile);

        $file = $this->createFileEntity($uploadedFile);

        $this->validateEntity($file);

        $this->persistSafely($file);

        $this->removeTransientFileReference($file);

        return $file;
    }

    private function assertAuthorized(): void
    {
        if (!$this->security->isGranted(self::REQUIRED_ROLE)) {
            throw new AccessDeniedHttpException(
                'Access denied.'
            );
        }
    }

    private function extractUploadedFile(Request $request): UploadedFile
    {
        $uploadedFile = $request->files->get(self::FILE_FIELD);

        if (!$uploadedFile instanceof UploadedFile) {
            throw new BadRequestHttpException(
                'Invalid file upload.'
            );
        }

        if (!$uploadedFile->isValid()) {
            throw new BadRequestHttpException(
                'Invalid file upload.'
            );
        }

        return $uploadedFile;
    }

    private function validateUploadedFile(
        UploadedFile $uploadedFile
    ): void {
        $size = $uploadedFile->getSize();

        if (!is_int($size) || $size <= 0) {
            throw new BadRequestHttpException(
                'Invalid file.'
            );
        }

        if ($size > self::MAX_FILE_SIZE) {
            throw new BadRequestHttpException(
                'Invalid file.'
            );
        }

        $this->validateFileName($uploadedFile);

        $this->validateMimeType($uploadedFile);
    }

    private function validateFileName(
        UploadedFile $uploadedFile
    ): void {
        $originalName = $uploadedFile->getClientOriginalName();

        if ($originalName === '') {
            throw new BadRequestHttpException(
                'Invalid file.'
            );
        }

        if (str_contains($originalName, "\0")) {
            throw new BadRequestHttpException(
                'Invalid file.'
            );
        }

        if ($originalName !== basename($originalName)) {
            throw new BadRequestHttpException(
                'Invalid file.'
            );
        }

        if (strlen($originalName) > 255) {
            throw new BadRequestHttpException(
                'Invalid file.'
            );
        }
    }

    private function validateMimeType(
        UploadedFile $uploadedFile
    ): void {
        $mimeType = $uploadedFile->getMimeType();

        if (
            !is_string($mimeType)
            || !in_array(
                $mimeType,
                self::ALLOWED_MIME_TYPES,
                true
            )
        ) {
            throw new BadRequestHttpException(
                'Invalid file.'
            );
        }
    }

    private function createFileEntity(
        UploadedFile $uploadedFile
    ): File {
        $file = new File();

        $file->setFile($uploadedFile);

        return $file;
    }

    private function validateEntity(File $file): void
    {
        $violations = $this->validator->validate($file);

        if ($violations->count() !== 0) {
            throw new BadRequestHttpException(
                'Invalid file.'
            );
        }
    }

    private function persistSafely(File $file): void
    {
        $connection = $this->entityManager->getConnection();

        $connection->beginTransaction();

        try {
            $this->entityManager->persist($file);
            $this->entityManager->flush();

            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            $this->entityManager->clear();

            throw new BadRequestHttpException(
                'Unable to process the file.'
            );
        }
    }

    private function removeTransientFileReference(
        File $file
    ): void {
        $file->file = null;
    }
}
