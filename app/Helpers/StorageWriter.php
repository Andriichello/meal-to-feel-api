<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Class StorageWriter.
 */
class StorageWriter
{
    /**
     * Name of storage disk
     *
     * @var string
     */
    protected string $disk;

    /**
     * StorageWriter constructor.
     *
     * @param string $disk
     */
    public function __construct(string $disk)
    {
        $this->disk = $disk;
    }

    /**
     * @param string $disk
     *
     * @return static
     */
    public static function make(string $disk): static
    {
        return new static($disk); // @phpstan-ignore-line
    }

    /**
     * Get current storage disk name.
     *
     * @return string
     */
    public function disk(): string
    {
        return $this->disk();
    }

    /**
     * Get hash of the file content.
     *
     * @param string|resource|File $content
     *
     * @return string
     */
    public function hash(mixed $content): string
    {
        if (is_resource($content)) {
            $path = pathOf($content);
            return md5_file($path);
        }

        if ($content instanceof File) {
            $path = $content->getRealPath();
            return md5_file($path);
        }

        return md5($content);
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
        return (new StorageReader($this->disk))
            ->exists($path);
    }

    /**
     * Write file to the disk.
     *
     * @param string $path Path without filename
     * @param string|resource|File $content
     * @param string|null $name If null, then hash will be used
     * @param bool $overwrite
     *
     * @return false|array{
     *      path: string,
     *      name: string,
     *      disk: string,
     *      url: string,
     *      metadata: array
     * }
     * @throws Exception
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function write(
        string $path,
        mixed $content,
        ?string $name = null,
        bool $overwrite = false
    ): array|false {
        $name = $name ?? $this->hash($content);

        $folder = Str::of($path)
            ->beforeLast($name)
            ->finish('/')
            ->value();

        if ($this->exists($folder . $name)) {
            if (!$overwrite) {
                throw new Exception("File already exists ($folder$name)");
            }

            $this->delete($folder . $name);
        }

        $metadata = [];

        if (is_string($content) || is_resource($content)) {
            if (is_resource($content)) {
                $reader = new StorageReader($this->disk);
                $metadata = $reader->metadata($content);
            }

            $isStored = Storage::disk($this->disk)
                ->put($folder . $name, $content);
        }

        if ($content instanceof File) {
            $reader = new StorageReader($this->disk);
            $metadata = $reader->metadata($content);

            // @phpstan-ignore-next-line
            $isStored = Storage::disk($this->disk)
                ->putFileAs($folder, $content, $name);
        }

        if (!isset($isStored) || !$isStored) {
            return false;
        }

        $disk = $this->disk;

        // @phpstan-ignore-next-line
        $url = Storage::disk($this->disk)
            ->url($path);

        return compact('name', 'path', 'disk', 'url', 'metadata');
    }

    /**
     * Delete file from the disk.
     *
     * @param string $path Path (with filename)
     */
    public function delete(string $path): bool
    {
        return Storage::disk($this->disk)
            ->delete($path);
    }

    /**
     * Move file on the disk.
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     * @throws Exception
     */
    public function move(string $from, string $to): bool
    {
        if ($this->exists($to)) {
            throw new Exception("File already exists ($to)");
        }

        return Storage::disk($this->disk)
            ->move($from, $to);
    }
}
