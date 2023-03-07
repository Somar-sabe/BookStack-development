<?php

namespace BookStack\Console\Commands;

use BookStack\References\ReferenceStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RegenerateReferences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookstack:regenerate-references {--database= : The database connection to use.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate all the cross-item model reference index';

    protected ReferenceStore $references;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ReferenceStore $references)
    {
        $this->references = $references;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $connection = DB::getDefaultConnection();

        if ($this->option('database')) {
            DB::setDefaultConnection($this->option('database'));
        }

        $this->references->updateForAllPages();

        DB::setDefaultConnection($connection);

        $this->comment('References have been regenerated');

        return 0;
    }
}
