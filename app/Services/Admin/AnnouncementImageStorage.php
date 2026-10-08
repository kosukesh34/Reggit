<?php

namespace App\Services\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AnnouncementImageStorage
{
    public function store(UploadedFile $file, int $projectId): string
    {
        $source = imagecreatefromstring($file->get());
        if ($source === false) {
            throw new \RuntimeException('画像を読み込めませんでした');
        }
        $ratio = min(1, 1024 / max(imagesx($source), imagesy($source)));
        $image = imagecreatetruecolor(max(1, (int) (imagesx($source) * $ratio)), max(1, (int) (imagesy($source) * $ratio)));
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagecopyresampled($image, $source, 0, 0, 0, 0, imagesx($image), imagesy($image), imagesx($source), imagesy($source));
        ob_start();
        imagepng($image, null, 6);
        $bytes = ob_get_clean();
        imagedestroy($source);
        imagedestroy($image);
        $path = 'announcement-private/'.$projectId.'/'.Str::uuid().'.png';
        if (! Storage::disk('local')->put($path, $bytes)) {
            throw new \RuntimeException('画像を保存できませんでした');
        }

        return $path;
    }
}
