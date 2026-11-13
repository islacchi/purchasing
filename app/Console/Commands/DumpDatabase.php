<?php

namespace App\Console\Commands;

use App\Support\DatabaseDumper;
use Illuminate\Console\Command;
use RuntimeException;

class DumpDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:dump
                            {--path=database/purchasing_db.sql : Output path relative to the project root}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export the configured MySQL database to a portable SQL dump';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = (string) $this->option('path');
        $isAbsolute = str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:/', $path) === 1;

        $outputPath = $isAbsolute ? $path : base_path($path);

        try {
            (new DatabaseDumper)->dump($outputPath);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Database dumped to %s (%s bytes).',
            $this->option('path'),
            number_format(filesize($outputPath)),
        ));

        return self::SUCCESS;
    }
}
