<?php

namespace Grafite\Archive\Services;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Grafite\Archive\Concerns\Archiveable;
use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class ArchiveManager
{
    public function __construct(
        protected Repository $config,
        protected Filesystem $files,
    ) {
    }

    public function archive(string $modelClass, array $options = []): array
    {
        $model = $this->resolveModel($modelClass);
        $relationships = $this->resolveRelationships($model, $options);
        $scopes = $this->resolveScopes($model, $options);
        $query = $this->buildQuery($model, $options, $scopes, $relationships);
        $records = $query->get()->map(fn (Model $record) => $record->toArray())->values()->all();

        $manifest = [
            'version' => 1,
            'model' => $modelClass,
            'record_count' => count($records),
            'exported_at' => now()->toIso8601String(),
            'date_column' => $model->archiveDateColumn(),
            'filters' => [
                'before' => $this->normalizeDateValue($options['before'] ?? null),
                'after' => $this->normalizeDateValue($options['after'] ?? null),
                'scopes' => $scopes,
                'relationships' => $relationships,
            ],
        ];

        $payload = json_encode(['records' => $records], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $encrypted = (bool) ($options['encrypt'] ?? $this->config->get('archive.encryption.enabled', true));
        $archive = [
            'manifest' => $manifest,
            'encrypted' => $encrypted,
            'payload' => $encrypted ? Crypt::encryptString($payload) : $payload,
        ];

        if ((bool) ($options['dry_run'] ?? false)) {
            return [
                'model' => $modelClass,
                'path' => null,
                'record_count' => $manifest['record_count'],
                'manifest' => $manifest,
            ];
        }

        $disk = (string) ($options['disk'] ?: $this->config->get('archive.disk', 'local'));
        $path = $this->archivePath($modelClass, $encrypted, $options['path'] ?? null);

        Storage::disk($disk)->put(
            $path,
            json_encode($archive, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );

        return [
            'model' => $modelClass,
            'path' => $path,
            'record_count' => $manifest['record_count'],
            'manifest' => $manifest,
        ];
    }

    protected function resolveModel(string $modelClass): Model
    {
        if (! class_exists($modelClass)) {
            throw new InvalidArgumentException("Model [{$modelClass}] was not found.");
        }

        $model = new $modelClass();

        if (! $model instanceof Model) {
            throw new InvalidArgumentException("Class [{$modelClass}] is not an Eloquent model.");
        }

        if (! in_array(Archiveable::class, class_uses_recursive($modelClass), true)) {
            throw new RuntimeException("Model [{$modelClass}] must use the Archiveable trait.");
        }

        return $model;
    }

    protected function buildQuery(Model $model, array $options, array $scopes, array $relationships): Builder
    {
        $query = $model->newQuery();
        $dateColumn = $model->archiveDateColumn();

        if ($relationships !== []) {
            $query->with($relationships);
        }

        if (! empty($options['after'])) {
            $query->where($dateColumn, '>=', $this->parseDateValue($options['after']));
        }

        if (! empty($options['before'])) {
            $query->where($dateColumn, '<=', $this->parseDateValue($options['before']));
        }

        foreach ($scopes as $scope) {
            [$scopeName, $scopeArgs] = $this->parseScope($scope);
            $query->{$scopeName}(...$scopeArgs);
        }

        return $query;
    }

    protected function resolveScopes(Model $model, array $options): array
    {
        if ((bool) ($options['without_scopes'] ?? false)) {
            return [];
        }

        $scopes = array_values(array_filter((array) ($options['scopes'] ?? [])));

        if ($scopes !== []) {
            return $scopes;
        }

        return array_values(array_filter($model->archiveScopes()));
    }

    protected function resolveRelationships(Model $model, array $options): array
    {
        if ((bool) ($options['without_relationships'] ?? false)) {
            return [];
        }

        $relationships = array_values(array_filter((array) ($options['relationships'] ?? [])));

        if ($relationships !== []) {
            return $relationships;
        }

        return array_values(array_filter($model->archiveRelationships()));
    }

    protected function parseScope(string $scope): array
    {
        [$name, $serializedArgs] = array_pad(explode(':', $scope, 2), 2, null);

        return [
            Str::camel(trim($name)),
            $serializedArgs === null || $serializedArgs === ''
                ? []
                : array_map('trim', explode(',', $serializedArgs)),
        ];
    }

    protected function parseDateValue(DateTimeInterface|string $value): CarbonInterface
    {
        return $value instanceof DateTimeInterface
            ? Carbon::instance($value)
            : Carbon::parse($value);
    }

    protected function normalizeDateValue(DateTimeInterface|string|null $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->parseDateValue($value)->toIso8601String();
    }

    protected function archivePath(string $modelClass, bool $encrypted, ?string $pathOverride = null): string
    {
        $basePath = trim((string) ($pathOverride ?: $this->config->get('archive.path', 'archives')), '/');
        $stamp = now()->format('YmdHis');
        $modelName = Str::snake(class_basename($modelClass));
        $extension = $encrypted ? 'archive.enc' : 'archive.json';

        return $basePath.'/'.$modelName.'-'.$stamp.'.'.$extension;
    }
}