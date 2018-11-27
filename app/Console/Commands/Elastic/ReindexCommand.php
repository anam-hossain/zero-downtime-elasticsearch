<?php

namespace App\Console\Commands;

use App\Country;
use Elasticsearch\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ReindexCommand extends Command
{
    /**
     * Search index name
     */
    const INDEX = 'world';

    /**
     * Search type
     */
    const TYPE = '_doc';

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
     * @var \Elasticsearch\Client
     */
    protected $client;

    /**
     * Create a new command instance.
     *
     * @param  \Elasticsearch\Client  $client
     * @return void
     */
    public function __construct(Client $client)
    {
        parent::__construct();

        $this->client = $client;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $this->removeIndex();

            $this->createIndex();

            $this->pushDataToSearchCluster();
        } catch (Exception $e) {
            Log::critical($e->getMessage());
        }
    }

    /**
     * Remove search index
     *
     * @return array
     */
    protected function removeIndex()
    {
        return $this->client->indices()->delete(['index' => self::INDEX]);
    }

    /**
     * Recreate the search index
     *
     * @return void
     */
    protected function createIndex()
    {
        Artisan::call('elastic:create-index');
    }

    /**
     * Push data to the search cluster
     *
     * @return void
     */
    protected function pushDataToSearchCluster()
    {
        Country::with('cities', 'languages')
            ->orderBy('Code')
            ->chunk(100, function ($countries) {
                foreach ($countries as $country) {
                    $this->save($country);
                }
            });
    }

    /**
     * Index country
     *
     * @return void
     */
    protected function save($country)
    {
        try {
            $response = $this->client->index([
                'index' => self::INDEX,
                'type' => self::TYPE,
                'id' => $country->Code,
                'body' => $country->toArray(),
            ]);
        } catch (Exception $e) {
            Log::critical('Indexing failed', [
                'id' => $country->Code,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
        }
    }
}
