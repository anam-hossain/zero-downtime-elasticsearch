<?php

namespace App\Console\Commands;

use App\Country;
use App\Handlers\IndexHandler;
use App\Jobs\PushToSearchClusterJob;
use Illuminate\Console\Command;

class ReindexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elastic:reindex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop existing index and push all data to search cluster';

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
     * Reindexing in 5 steps
     * 1. Create new index
     * 2. Change write alias to point newly created index
     * 3. Index all data using write index
     * 4. After finish indexing, change read alias to point new index
     * 5. Remove old index
     *
     * @return void
     */
    public function handle()
    {
        $newIndex = $this->indexHandler->generateIndexName();

        // 1. Create new index
        $this->indexHandler->createIndex($newIndex);

        $currentIndex = $this->indexHandler->getIndexByAlias(IndexHandler::WRITE_ALIAS);

        // 2. Change write alias to point newly created index
        $this->indexHandler->switchAlias($currentIndex, $newIndex, IndexHandler::WRITE_ALIAS);

        // 3. index all data using write alias
        $this->reindexAllData();

        // 4. Change read alias to point new index
        $this->indexHandler->switchAlias($currentIndex, $newIndex, IndexHandler::READ_ALIAS);

        // 5. Remove old index
        $this->indexHandler->removeIndex($currentIndex);
    }

    /**
     * Push data to the search cluster
     *
     * @return void
     */
    protected function reindexAllData()
    {
        Country::with('cities', 'languages')
            ->orderBy('Code')
            ->chunk(100, function ($countries) {
                foreach ($countries as $country) {
                    dispatch(new PushToSearchClusterJob($country->Code, $country->toArray()));
                }
            });
    }
}
