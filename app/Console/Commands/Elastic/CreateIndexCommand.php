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
    protected $signature = 'elastic:create-index';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create ElasticSearch index';

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
        try {
            $index = $this->client->indices()->exists(['index' => self::INDEX]);

            if (!$index) {
                $this->client->indices()->create($this->params());
            }
        } catch (Exception $e) {
            Log::error('Unable to generate search index', [
                'message' => $e->getMessage(),
                'index' => self::INDEX,
            ]);
        }
    }

    /**
     * Index params
     *
     * @return array
     */
    protected function params()
    {
        return [
            'index' => self::INDEX,
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
