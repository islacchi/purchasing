<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

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
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (($config['driver'] ?? null) !== 'mysql') {
            $this->error('The db:dump command only supports MySQL connections.');

            return self::FAILURE;
        }

        $database = $config['database'];
        $outputPath = base_path($this->option('path'));
        $schemaPath = $outputPath.'.schema.tmp';
        $dataPath = $outputPath.'.data.tmp';

        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        $mysqldump = env('MYSQLDUMP_PATH', 'mysqldump');
        $baseOptions = [
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
        ];

        if (! empty($config['password'])) {
            $baseOptions[] = '--password='.$config['password'];
        }

        $ephemeralTables = [
            'cache',
            'cache_locks',
            'failed_jobs',
            'job_batches',
            'jobs',
            'sessions',
        ];

        $schemaProcess = new Process([
            $mysqldump,
            ...$baseOptions,
            '--databases',
            $database,
            '--add-drop-database',
            '--single-transaction',
            '--routines',
            '--triggers',
            '--default-character-set=utf8mb4',
            '--no-data',
            '--result-file='.$schemaPath,
        ]);

        $dataProcess = new Process([
            $mysqldump,
            ...$baseOptions,
            '--single-transaction',
            '--skip-triggers',
            '--default-character-set=utf8mb4',
            '--no-create-info',
            ...array_map(
                fn (string $table): string => '--ignore-table='.$database.'.'.$table,
                $ephemeralTables,
            ),
            '--result-file='.$dataPath,
            $database,
        ]);

        foreach ([$schemaProcess, $dataProcess] as $process) {
            $process->setTimeout(300);
            $process->run();

            if (! $process->isSuccessful()) {
                @unlink($schemaPath);
                @unlink($dataPath);

                $this->error('Database dump failed: '.trim($process->getErrorOutput()));

                return self::FAILURE;
            }
        }

        file_put_contents(
            $outputPath,
            file_get_contents($schemaPath).file_get_contents($dataPath),
        );

        @unlink($schemaPath);
        @unlink($dataPath);

        $this->info(sprintf(
            'Database dumped to %s (%s bytes).',
            $this->option('path'),
            number_format(filesize($outputPath)),
        ));

        return self::SUCCESS;
    }
}
