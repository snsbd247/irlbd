<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stores applicant CVs on the PRIVATE "local" disk (storage/app/private), never in the web root.
 * The real file type is checked from its content signature, not the client file name.
 */
class CvStorage
{
    public const TYPES = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    /** @return array{path: string, original: string, mime: string, size: int} */
    public function store(UploadedFile $file): array
    {
        $head = (string) file_get_contents($file->getRealPath(), false, null, 0, 8);

        $ext = match (true) {
            str_starts_with($head, '%PDF-') => 'pdf',
            str_starts_with($head, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") => 'doc',
            str_starts_with($head, "PK\x03\x04") && $this->isDocx($file->getRealPath()) => 'docx',
            default => null,
        };

        if (! $ext) {
            throw ValidationException::withMessages(['cv' => 'Your CV must be a PDF or Word (.doc / .docx) document.']);
        }

        $path = 'cvs/'.now()->format('Y/m').'/'.Str::uuid()->toString().'.'.$ext;
        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        return [
            'path' => $path,
            'original' => Str::limit(preg_replace('/[^\w.\- ]+/u', '_', basename($file->getClientOriginalName())), 200, ''),
            'mime' => array_search($ext, self::TYPES, true),
            'size' => (int) $file->getSize(),
        ];
    }

    protected function isDocx(string $path): bool
    {
        if (! class_exists(\ZipArchive::class)) {
            return true; // signature already checked; zip extension missing on host
        }
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            return false;
        }
        $ok = $zip->locateName('word/document.xml') !== false;
        $zip->close();

        return $ok;
    }
}
