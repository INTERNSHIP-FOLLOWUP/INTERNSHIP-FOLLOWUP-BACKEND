<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    private array $allowedMimeTypes = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    private int $maxFileSize = 5 * 1024 * 1024; // 5MB

    private string $disk = 'public';

    private string $directory = 'worklogs';

    /**
     * Validate and upload file
     *
     * @throws \InvalidArgumentException
     */
    public function upload(UploadedFile $file): array
    {
        $this->validateFile($file);

        $extension = $file->getClientOriginalExtension();
        $fileName = Str::random(40) . '.' . $extension;
        $path = $file->storeAs($this->directory, $fileName, $this->disk);

        return [
            'path' => $path,
            'type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ];
    }

    /**
     * Delete file from storage
     */
    public function delete(string $path): bool
    {
        return Storage::disk($this->disk)->delete($path);
    }

    /**
     * Validate file type and size
     */
    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Invalid file upload.');
        }

        $mimeType = $file->getMimeType();

        if (!array_key_exists($mimeType, $this->allowedMimeTypes)) {
            throw new \InvalidArgumentException('Invalid file type. Allowed types: PNG, JPG, PDF, DOCX.');
        }

        if ($file->getSize() > $this->maxFileSize) {
            throw new \InvalidArgumentException('File size exceeds maximum limit of 5MB.');
        }
    }
}