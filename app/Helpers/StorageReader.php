<?php

namespace App\Helpers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
     * @param string|null $name
     *
     * @return resource|null
     * @throws Exception
     */
    public function asTempFile(string $path, ?string $name = null)
    {
        if ($name) {
            $tempDir = Str::of(sys_get_temp_dir())
                ->finish('/')
                ->append(Str::random(4))
                ->value();

            if (!is_dir($tempDir)) {
                if (!mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
                    throw new Exception('Failed to create directory: ' . $tempDir);
                }
            }

            $tempPath = $tempDir . '/' . $name;

            $tempFile = fopen($tempPath, 'w+');
            file_put_contents($tempPath, $this->asStream($path));

            return $tempFile;
        }

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
