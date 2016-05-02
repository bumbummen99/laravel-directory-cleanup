<?php

namespace Spatie\DirectoryCleanup;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class DirectoryCleanupCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'clean:directories';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up directories.';

    /** @var \Illuminate\Filesystem\Filesystem */
    protected $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
    }

    public function handle()
    {
        $this->comment('Cleaning directories...');

        $directories = collect(config('laravel-directory-cleanup.directories'));

        collect($directories)->each(function ($config, $directory) {

            $this->deleteFilesIfOlderThanMinutes($directory, $config['deleteAllOlderThanMinutes']);

        });

        $this->comment('All done!');
    }

    protected function deleteFilesIfOlderThanMinutes(string $directory, int $minutes)
    {
        $timeInPast = Carbon::now()->subMinutes($minutes);

        $files = collect($this->filesystem->files($directory))
            ->filter(function ($file) use ($timeInPast) {

                $timeWhenFileWasModified = Carbon::createFromTimestamp(filemtime($file));

                return $timeWhenFileWasModified->lt($timeInPast);

            })
            ->each(function ($file) {
                $this->filesystem->delete($file);
            });

        $this->info("Deleted {$files->count()} file(s) from {$directory}.");
    }
}
