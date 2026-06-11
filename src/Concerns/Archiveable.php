<?php

namespace Grafite\Archive\Concerns;

use Illuminate\Support\Str;

trait Archiveable
{
    public function archiveDateColumn(): string
    {
        if (property_exists($this, 'archiveDateColumn')) {
            return (string) $this->archiveDateColumn;
        }

        return (string) config('archive.query.date_column', 'created_at');
    }

    public function archiveScopes(): array
    {
        if (property_exists($this, 'archiveScopes')) {
            return (array) $this->archiveScopes;
        }

        return (array) config('archive.query.scopes', []);
    }

    public function archiveRelationships(): array
    {
        if (property_exists($this, 'archiveRelationships')) {
            return (array) $this->archiveRelationships;
        }

        return (array) config('archive.query.relationships', []);
    }

    public function archiveFixtureName(): string
    {
        return Str::snake(class_basename($this));
    }

    public function shouldArchive(): bool
    {
        if (property_exists($this, 'shouldArchive')) {
            return (bool) $this->shouldArchive;
        }

        return true;
    }
}