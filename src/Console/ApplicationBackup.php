<?php

namespace Larawiz\Larawiz\Console;

use Illuminate\Support\Str;
use Larawiz\Larawiz\Larawiz;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use const DIRECTORY_SEPARATOR;

class ApplicationBackup
{
    /**
     * Application filesystem.
     *
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Create a new console command instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     */
    public function __construct(Application $app, Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->app = $app;
    }

    /**
     * Backups project files.
     *
     * @return void
     */
    public function backup()
    {
        $backupDir = $this->backupDir();

        $this->makeBackupDirectory($backupDir);

        $this->copyProjectFiles($backupDir);

        $this->cleanMigrationDirectory();
    }

    /**
     * Returns the target backup directory.
     *
     * @return string
     */
    protected function backupDir()
    {
        return implode(DIRECTORY_SEPARATOR, [
            $this->app->storagePath(), Larawiz::BACKUPS_DIR, now()->format('Y-m-d_His'),
        ]);
    }

    /**
     * Creates the backup directory.
     *
     * @param  string  $dir
     */
    protected function makeBackupDirectory(string $dir)
    {
        $this->filesystem->ensureDirectoryExists($dir, 0755, true);
    }

    /**
     * Copy the project files into the backup directory.
     *
     * @param  string  $backupDir
     */
    protected function copyProjectFiles(string $backupDir)
    {
        foreach ($this->getDirectoriesToBackup() as $dir) {
            $this->filesystem->copyDirectory($dir, $this->backupDirectory($backupDir, $dir));
        }
    }

    /**
     * Returns a list of directories to backup.
     *
     * @return array
     */
    protected function getDirectoriesToBackup()
    {
        return [
            $this->app->path(),
            $this->app->databasePath('migrations'),
            $this->app->databasePath('factories'),
            $this->app->databasePath('seeds'),
        ];
    }

    /**
     * Inject the Larawiz Back up directory to the final path.
     *
     * @param  string  $backupDir
     * @param  string  $path
     * @return string
     */
    protected function backupDirectory(string $backupDir, string $path)
    {
        return Str::of(rtrim($path, DIRECTORY_SEPARATOR))
            ->replace($this->app->basePath(), $backupDir)
            ->__toString();
    }

    /**
     * Clear the migrations directory.
     *
     * This will avoid running old scaffolded migrations.
     *
     * @return void
     */
    protected function cleanMigrationDirectory()
    {
        $this->filesystem->deleteDirectory($this->app->databasePath('migrations'), true);
    }
}
