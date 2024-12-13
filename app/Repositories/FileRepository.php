<?php

namespace App\Repositories;

use App\Helpers\StorageReader;
use App\Helpers\StorageWriter;
use App\Models\File as FileModel;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Class FileRepository.
 */
class FileRepository
{
    /**
     * Create model with given attributes.
     *
     * @param array{
     *     context?: Model|null,
     *     context_id?: int|null,
     *     context_type?: string|null,
     *     file_id?: string|null,
     *     unique_id?: string|null,
     *     file: resource|File|UploadedFile,
     *     disk_path: string,
     *     disk_name: string,
     *     disk: string,
     *     path: ?string,
     *     name: ?string,
     *     type: ?string,
     *     extension: ?string,
     *     size: ?int,
     * } $attributes
     * @param bool $overwrite
     *
     * @return FileModel
     * @throws Exception
     * @SuppressWarnings(PHPMD)
     */
    public function create(array $attributes, bool $overwrite = false): FileModel
    {
        $writer = new StorageWriter($attributes['disk']);

        $file = $attributes['file'];
        $path = $attributes['disk_path'];
        $name = $attributes['disk_name'] ?? $writer->hash($file);

        if (is_resource($file) || $file instanceof File || $file instanceof UploadedFile) {
            $stored = $writer->write($path, $file, $name, $overwrite);
        }

        $context = $attributes['context'] ?? null;

        if ($context instanceof Model) {
            $contextId = $context->getKey();
            $contextType = $context->getMorphClass();
        }

        if (isset($stored) && is_array($stored)) {
            $metadata = array_merge(
                data_get($attributes, 'metadata') ?? [],
                data_get($stored, 'metadata') ?? []
            );

            // @phpstan-ignore-next-line
            return FileModel::query()
                ->create([
                    'context_id' => $attributes['context_id'] ?? $contextId ?? null,
                    'context_type' => $attributes['context_type'] ?? $contextType ?? null,
                    'file_id' => $attributes['file_id'] ?? null,
                    'unique_id' => $attributes['unique_id'] ?? null,
                    'disk_name' => $stored['name'],
                    'disk_path' => $stored['path'],
                    'disk' => $stored['disk'],
                    'path' => $attributes['path'] ?? null,
                    'type' => $attributes['type'] ?? null,
                    'extension' => $attributes['extension'] ?? null,
                    'size' => $attributes['size'] ?? null,
                    'metadata' => $metadata,
                ]);
        }

        throw new Exception('Failed to create ' . static::class);
    }

    /**
     * Update model with given attributes.
     *
     * @param FileModel $model
     * @param array $attributes
     *
     * @return bool
     * @throws Exception
     */
    public function update(FileModel $model, array $attributes): bool
    {
        $name = $attributes['name'] ?? null;
        $disk = $attributes['disk'] ?? $model->disk;

        if ($name && $name !== $model->disk_name) {
            $newPath = Str::of($model->folder)
                ->finish('/')
                ->append($name)
                ->value();

            if ($disk && $disk !== $model->disk) {
                throw new Exception('Can\'t change disks ' . static::class);
            }

            $writer = new StorageWriter($disk);

            if (!$writer->move($model->full_path, $newPath)) {
                throw new Exception('Failed to move ' . static::class);
            }
        }

        return $model->update($attributes);
    }

    /**
     * Delete given model.
     *
     * @param FileModel $model
     *
     * @return bool
     * @throws Exception
     */
    public function delete(FileModel $model): bool
    {
        $writer = new StorageWriter($model->disk);
        $deleted = $writer->delete($model->full_path);

        if (!$deleted) {
            throw new Exception('Failed to delete file:  ' . $model->full_path);
        }

        return $model->delete();
    }

    /**
     * Refresh metadata for the given file model.
     *
     * @param FileModel $model
     *
     * @return bool
     * @throws Exception
     */
    public function refreshMetadata(FileModel $model): bool
    {
        $reader = new StorageReader($model->disk);
        $file = $reader->asTempFile($model->full_path);

        $model->metadata = (object) array_merge(
            (array) ($model->metadata ?? []),
            $reader->metadata($file) ?? [],
        );

        return $model->save();
    }

    /**
     * @param FileModel $original
     * @param mixed $variant
     *
     * @return FileModel
     * @throws FileNotFoundException|Exception
     */
    public function createVariant(FileModel $original, mixed $variant): FileModel
    {
        $attributes = [
            'file' => $variant,
            'context' => $original,
            'disk' => $original->disk,
            'disk_name' => Str::of($original->disk_name)
                ->beforeLast('.')
                ->append('_' . Str::random(4))
                ->value(),
        ];

        if (is_resource($variant)) {
            $attributes['type'] = mime_content_type(pathOf($variant));
            $attributes['extension'] = extensionOfMime($attributes['type']);
            $attributes['disk_name'] .= '.' . $attributes['extension'];
        }

        if ($variant instanceof File || $variant instanceof UploadedFile) {
            $attributes['type'] = mime_content_type($variant->getPathname());
            $attributes['extension'] = extensionOfMime($attributes['type']);
            $attributes['disk_name'] .= '.' . $attributes['extension'];
        }

        $attributes['disk_path'] = Str::of($original->folder)
            ->finish('/')
            ->append($attributes['disk_name'])
            ->value();

        $file = $this->create($attributes, true);

        if (empty($file->size)) {
            $file->size = data_get($file, 'metadata.size');
            $file->save();
        }

        return $file;
    }
}
