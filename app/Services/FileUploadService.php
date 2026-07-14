<?php

namespace App\Services;

<<<<<<< HEAD
use App\Models\Attachment;
=======
>>>>>>> feature/evaluation-issue
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
<<<<<<< HEAD
    /**
     * Maximum allowed file size in bytes (10MB).
     */
    const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Allowed MIME types for worklog attachments.
     */
    const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/jpg',
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    /**
     * Allowed file extensions for worklog attachments.
     */
    const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'pdf',
        'docx',
    ];

    /**
     * Storage disk to use for worklog files.
     */
    const STORAGE_DISK = 'public';

    /**
     * Storage directory for worklog files.
     */
    const STORAGE_DIRECTORY = 'worklogs';

    /**
     * Validate an uploaded file against allowed types and size constraints.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return array{valid: bool, message: string}
     */
    public function validate(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            return [
                'valid' => false,
                'message' => 'Invalid file type. Allowed types: ' . implode(', ', self::ALLOWED_EXTENSIONS) . '.',
            ];
        }

        $mimeType = $file->getMimeType();

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            return [
                'valid' => false,
                'message' => 'Invalid file MIME type.',
            ];
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            $maxSizeMB = self::MAX_FILE_SIZE / 1024 / 1024;
            return [
                'valid' => false,
                'message' => "File size exceeds the maximum allowed size of {$maxSizeMB}MB.",
            ];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Store an uploaded file and return the stored path.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string  The stored file path relative to the disk root
     */
    public function store(UploadedFile $file): string
    {
        $filename = $this->generateFileName($file);

        $path = $file->storeAs(
            self::STORAGE_DIRECTORY,
            $filename,
            self::STORAGE_DISK
        );

        return $path;
    }

    /**
     * Store a file and create an Attachment record for the given worklog.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  int  $worklogId
     * @return \App\Models\Attachment
     */
    public function storeAndCreateAttachment(UploadedFile $file, int $worklogId): Attachment
    {
        $path = $this->store($file);
        $mimeType = $file->getMimeType();

        return Attachment::create([
            'worklog_id' => $worklogId,
            'file_path'  => $path,
            'file_type'  => $mimeType,
            'file_size'  => $file->getSize(),
        ]);
    }

    /**
     * Delete a file from storage.
     *
     * @param  string  $path  Relative path to the file on the storage disk
     * @return bool
     */
    public function delete(string $path): bool
    {
        if (Storage::disk(self::STORAGE_DISK)->exists($path)) {
            return Storage::disk(self::STORAGE_DISK)->delete($path);
        }

        return false;
    }

    /**
     * Delete an attachment record and its associated file.
     *
     * @param  \App\Models\Attachment  $attachment
     * @return bool
     */
    public function deleteAttachment(Attachment $attachment): bool
    {
        $this->delete($attachment->file_path);

        return (bool) $attachment->delete();
    }

    /**
     * Generate a unique filename for the uploaded file.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string
     */
    private function generateFileName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Ymd_His');
        $uuid = Str::uuid()->toString();

        return "{$timestamp}_{$uuid}.{$extension}";
    }
}
=======
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
>>>>>>> feature/evaluation-issue
