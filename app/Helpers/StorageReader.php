<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Class StorageReader.
 */
class StorageReader
{
    /**
     * Name of storage disk
     *
     * @var string
     */
    protected string $disk;

    /**
     * StorageReader constructor.
     *
     * @param string $disk
     */
    public function __construct(string $disk)
    {
        $this->disk = $disk;
    }

    /**
     * Determine if file with the given path already exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)
            ->exists($path);
    }

    /**
     * Get file content as string.
     *
     * @param string $path
     *
     * @return string|null
     */
    public function asString(string $path): ?string
    {
        return Storage::disk($this->disk)
            ->get($path);
    }

    /**
     * Get file content as stream.
     *
     * @param string $path
     *
     * @return resource|null
     */
    public function asStream(string $path)
    {
        return Storage::disk($this->disk)
            ->readStream($path);
    }

    /**
     * Get file content as temporary file.
     *
     * @param string $path
     *
     * @return resource|null
     */
    public function asTempFile(string $path)
    {
        $tempFile = tmpfile();
        $tempPath = stream_get_meta_data($tempFile)['uri'];

        file_put_contents($tempPath, $this->asStream($path));

        return $tempFile;
    }

    /**
     * Get metadata for the given file.
     *
     * @param File|resource|string $file
     *
     * @return array|null
     */
    public function metadata(mixed $file): ?array
    {
        if ($file instanceof File) {
            return [
                'size' => $file->getSize(),
                'type' => $file->getMimeType(),
                'extension' => $file->guessExtension(),
            ];
        }

        if (is_string($file)) {
            $path = $file;
            $file = $this->asTempFile($path);
        }

        if (is_resource($file)) {
            $meta = stream_get_meta_data($file);

            return [
                'size' => filesize($meta['uri']),
                'type' => mime_content_type($meta['uri']),
            ];
        }

        return null;
    }

    /**
     * Generate temporary url for the given file.
     *
     * @param string $path
     * @param Carbon $expiration
     *
     * @return string
     */
    public function temporaryUrl(string $path, Carbon $expiration): string
    {
        // @phpstan-ignore-next-line
        return Storage::disk($this->disk)
            ->temporaryUrl($path, $expiration);
    }
}
