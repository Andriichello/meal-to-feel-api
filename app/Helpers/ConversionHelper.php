<?php

namespace App\Helpers;

use App\Models\File;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Class ConversionHelper.
 */
class ConversionHelper
{
    /**
     * Convert given media to WebP format.
     *
     * @param File|string $media
     * @param int $quality
     *
     * @return false|resource
     */
    public function toWebP(File|string $media, int $quality = 90): mixed
    {
        $image = $this->read($media)
            ->encode(new WebpEncoder($quality));

        $path = pathOf($file = tmpfile());

        $image->save($path);

        return $file;
    }

    /**
     * Read image from given media.
     *
     * @param File|string $media Media record or file path.
     *
     * @return ImageInterface
     */
    protected function read(File|string $media): ImageInterface
    {
        if ($media instanceof File) {
            $media = $media->url;
        }

        return ImageManager::gd()->read($media);
    }
}
