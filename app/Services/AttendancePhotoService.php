<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendancePhotoService
{
    /** @return array{path: string, sha256: string} */
    public function store(UploadedFile $photo, int $userId, string $phase, CarbonImmutable $timestamp): array
    {
        if (! function_exists('imagecreatefromstring')) {
            throw ValidationException::withMessages(['photo' => 'Pemrosesan foto belum tersedia di server. Hubungi management untuk pencatatan manual.']);
        }
        $source = @imagecreatefromstring($photo->get());
        if (! $source) {
            throw ValidationException::withMessages(['photo' => 'Foto tidak dapat dibaca. Ambil ulang dengan format JPG, PNG, atau WebP.']);
        }
        $scale = min(1, 1280 / imagesx($source), 1280 / imagesy($source));
        $photoWidth = (int) round(imagesx($source) * $scale);
        $height = (int) round(imagesy($source) * $scale);
        $width = max(640, $photoWidth);
        $image = imagecreatetruecolor($width, $height + 56);
        imagecopyresampled($image, $source, (int) (($width - $photoWidth) / 2), 0, 0, 0, $photoWidth, $height, imagesx($source), imagesy($source));
        $white = imagecolorallocate($image, 255, 255, 255);
        imagestring($image, 4, 12, $height + 6, 'Staff #'.$userId.' | '.strtoupper(str_replace('_', ' ', $phase)), $white);
        imagestring($image, 4, 12, $height + 28, $timestamp->timezone(config('gym.timezone'))->format('Y-m-d H:i:s P').' | SERVER', $white);
        ob_start();
        imagejpeg($image, null, 85);
        $bytes = ob_get_clean();
        imagedestroy($source);
        imagedestroy($image);
        $path = $userId.'/'.$timestamp->format('Y-m').'/'.Str::uuid().'.jpg';
        Storage::disk('attendance')->put($path, $bytes);

        return ['path' => $path, 'sha256' => hash('sha256', $bytes)];
    }
}
