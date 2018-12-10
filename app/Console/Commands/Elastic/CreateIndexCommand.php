<?php

namespace App\Console\Commands;

use App\Handlers\IndexHandler;
use Illuminate\Console\Command;

class CreateIndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elastic:create-index';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create ElasticSearch index';

    /**
     * @var \App\Handlers\IndexHandler
     */
    protected $indexHandler;

    /**
     * Create a new command instance.
     *
     * @param  \App\Handlers\IndexHandler  $indexHandler
     * @return void
     */
    public function __construct(IndexHandler $indexHandler)
    {
        parent::__construct();

        $this->indexHandler = $indexHandler;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $index = $this->indexHandler->generateIndexName();

        $this->indexHandler->createIndex($index);

        // Before do these steps,
        // we should verify that write and read aliases not pointing to any indices
        $this->indexHandler->addWriteAlias($index);
        $this->indexHandler->addReadAlias($index);
    }
}
