<?php

namespace Grafite\Archive\Commands;

use Grafite\Archive\Services\ArchiveManager;
use Illuminate\Console\Command;
use Throwable;

class ArchiveRunCommand extends Command
{
    protected $signature = 'archive:run
        {--model= : Fully qualified model class to archive}
        {--before= : Archive rows with the archive date column before this timestamp}
        {--after= : Archive rows with the archive date column after this timestamp}
        {--scope=* : Apply one or more local scopes, optionally with colon-delimited args}
        {--without-scopes : Ignore model or config default scopes}
        {--with=* : Eager load one or more relationships}
        {--without-relationships : Ignore model or config default relationships}
        {--disk= : Filesystem disk override}
        {--path= : Target archive path override}
        {--unencrypted : Store the payload without encryption}
        {--dry-run : Resolve records and display the result without writing a file}';

    protected $description = 'Archive model records into encrypted export files.';

    public function __construct(protected ArchiveManager $archiveManager)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $models = $this->resolveModels();

        if ($models === []) {
            $this->error('No archiveable models were provided. Pass --model or configure archive.models.');

            return self::INVALID;
        }

        try {
            foreach ($models as $modelClass) {
                $result = $this->archiveManager->archive($modelClass, [
                    'before' => $this->option('before'),
                    'after' => $this->option('after'),
                    'scopes' => (array) $this->option('scope'),
                    'without_scopes' => (bool) $this->option('without-scopes'),
                    'relationships' => (array) $this->option('with'),
                    'without_relationships' => (bool) $this->option('without-relationships'),
                    'disk' => $this->option('disk'),
                    'path' => $this->option('path'),
                    'encrypt' => ! $this->option('unencrypted'),
                    'dry_run' => (bool) $this->option('dry-run'),
                ]);

                $this->line(sprintf(
                    '%s archived %d record(s)%s',
                    $result['model'],
                    $result['record_count'],
                    $result['path'] ? ' to '.$result['path'] : ' (dry-run)'
                ));
            }
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function resolveModels(): array
    {
        if ($model = $this->option('model')) {
            return [$model];
        }

        return array_values(array_filter((array) config('archive.models', [])));
    }
}