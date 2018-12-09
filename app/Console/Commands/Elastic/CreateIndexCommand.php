<?php

namespace App\Console\Commands;

use Elasticsearch\Client;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateIndexCommand extends Command
{
    /**
     * Search index name
     */
    const INDEX_PREFIX = 'world';

    /**
     * Search type
     */
    const TYPE = '_doc';

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
     * Index name
     *
     * @var string
     */
    protected $index;

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
     * @return mixed
     */
    public function handle()
    {
        $this->generateIndexName();

        try {
            $index = $this->client->indices()->exists(['index' => $this->index]);

            if (!$index) {
                $this->client->indices()->create($this->params());

                //$this->addWriteAlias();
                //$this->addReadAlias();
            }
        } catch (Exception $e) {
            Log::error('Unable to generate search index', [
                'message' => $e->getMessage(),
                'index' => $this->index,
            ]);
        }
    }

    /**
     * Add Write alias to index
     *
     * @return void
     */
    protected function addWriteAlias()
    {
        $alias = self::INDEX_PREFIX . '_write';

        $this->addAlias($alias);
    }

    /**
     * Add Read alias to index
     *
     * @return void
     */
    protected function addReadAlias()
    {
        $alias = self::INDEX_PREFIX . '_read';

        $this->addAlias($alias);
    }

    /**
     * Add an alias to index
     *
     * @param string $alias
     * @return void
     */
    protected function addAlias($alias)
    {
        $this->client->indices()->putAlias([
            'index' => $this->index,
            'name' => $alias,
        ]);
    }

    /**
     * Generate index name
     *
     * @return void
     */
    protected function generateIndexName()
    {
        $this->index = self::INDEX_PREFIX . '_' . time();
    }

    /**
     * Index params
     *
     * @return array
     */
    protected function params()
    {
        return [
            'index' => $this->index,
            'body' => [
                'mappings' => [
                    self::TYPE => [
                        '_source' => [
                            'enabled' => true,
                        ],
                        'properties' => [
                            'Name' => [
                                'type' => 'keyword',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
