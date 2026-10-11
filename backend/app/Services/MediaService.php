<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stores admin uploads safely:
 *  - type is detected from file contents (finfo), never trusted from the client name/extension
 *  - JPEG/PNG/WebP are decoded and re-encoded with GD, which strips metadata and
 *    any non-image payload, and are downscaled to the configured max dimension
 *  - files get random names inside the uploads disk; original names are only metadata
 */
class MediaService
{
    public function store(UploadedFile $file, string $folder = 'general', ?int $userId = null, ?string $alt = null): Media
    {
        $mimes = config('cms.uploads.mimes');
        $mime = $file->getMimeType();

        if (! isset($mimes[$mime])) {
            throw ValidationException::withMessages(['file' => 'Only JPG, PNG, WebP, GIF, ICO images and PDF documents are allowed.']);
        }

        $ext = $mimes[$mime];
        $folder = in_array($folder, config('cms.uploads.folders'), true) ? $folder : 'general';
        $relative = $folder.'/'.now()->format('Y/m').'/'.Str::uuid()->toString().'.'.$ext;
        $width = $height = null;

        if ($ext === 'pdf') {
            // Must really be a PDF (signature in the first bytes), not a renamed script or HTML file.
            $head = (string) file_get_contents($file->getRealPath(), false, null, 0, 1024);
            if (! str_contains($head, '%PDF-')) {
                throw ValidationException::withMessages(['file' => 'The file is not a valid PDF document.']);
            }
            if ($file->getSize() > config('cms.uploads.document_max_kb') * 1024) {
                throw ValidationException::withMessages(['file' => 'The document is too large.']);
            }
            if ($folder === 'general') {
                $folder = 'documents';
                $relative = $folder.'/'.now()->format('Y/m').'/'.Str::uuid()->toString().'.pdf';
            }
            Storage::disk('uploads')->put($relative, file_get_contents($file->getRealPath()));
        } elseif ($ext === 'ico') {
            Storage::disk('uploads')->put($relative, file_get_contents($file->getRealPath()));
        } else {
            $info = @getimagesize($file->getRealPath());
            if ($info === false || $info[0] < 1 || $info[1] < 1) {
                throw ValidationException::withMessages(['file' => 'The file is not a valid image.']);
            }
            [$width, $height] = $info;

            if ($ext === 'gif') {
                // GIFs are kept as-is to preserve animation; contents were verified as an image above.
                Storage::disk('uploads')->put($relative, file_get_contents($file->getRealPath()));
            } else {
                [$binary, $width, $height] = $this->reencode($file->getRealPath(), $ext);
                Storage::disk('uploads')->put($relative, $binary);
            }
        }

        return Media::create([
            'path' => $relative,
            'original_name' => Str::limit(basename($file->getClientOriginalName()), 250, ''),
            'mime_type' => $mime,
            'size' => Storage::disk('uploads')->size($relative),
            'width' => $width,
            'height' => $height,
            'alt' => $alt,
            'folder' => $folder,
            'uploaded_by' => $userId,
        ]);
    }

    public function delete(Media $media): void
    {
        Storage::disk('uploads')->delete($media->path);
        $media->delete();
    }

    /** @return array{0: string, 1: int, 2: int} */
    protected function reencode(string $path, string $ext): array
    {
        $image = match ($ext) {
            'jpg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'webp' => @imagecreatefromwebp($path),
        };

        if (! $image) {
            throw ValidationException::withMessages(['file' => 'The image could not be processed.']);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $max = config('cms.uploads.max_dimension');

        if ($width > $max || $height > $max) {
            $ratio = min($max / $width, $max / $height);
            $newW = max(1, (int) round($width * $ratio));
            $newH = max(1, (int) round($height * $ratio));
            $resized = imagecreatetruecolor($newW, $newH);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $width, $height);
            imagedestroy($image);
            $image = $resized;
            [$width, $height] = [$newW, $newH];
        } else {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        ob_start();
        match ($ext) {
            'jpg' => imagejpeg($image, null, 85),
            'png' => imagepng($image, null, 7),
            'webp' => imagewebp($image, null, 85),
        };
        $binary = ob_get_clean();
        imagedestroy($image);

        return [$binary, $width, $height];
    }
}
