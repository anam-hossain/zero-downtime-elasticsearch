<?php

namespace App\Handlers;

use Elasticsearch\Client;
use Exception;

class IndexHandler
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
     * ElasticSearch client
     *
     * @var \Elasticsearch\Client
     */
    protected $client;

    /**
     * IndexHandler's constructor
     *
     * @param  \Elasticsearch\Client  $client
     * @return void
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Create new index
     *
     * @param string $index
     * @return void
     */
    public function createIndex($index)
    {
        try {
            $index = $this->client->indices()->exists(['index' => $this->index]);

            if (!$index) {
                $this->client->indices()->create($this->params());
            }
        } catch (Exception $e) {
            Log::error('Unable to create index', [
                'message' => $e->getMessage(),
                'index' => $index,
            ]);
        }
    }

    /**
     * Add write alias to index
     *
     * @return void
     */
    public function addWriteAlias($index)
    {
        $alias = self::INDEX_PREFIX . '_write';

        $this->addAlias($index, $alias);
    }

    /**
     * Add read alias to index
     *
     * @return void
     */
    public function addReadAlias($index)
    {
        $alias = self::INDEX_PREFIX . '_read';

        $this->addAlias($index, $alias);
    }

    /**
     * Add an alias to index
     *
     * @param string $index
     * @param string $alias
     * @return void
     */
    public function addAlias($index, $alias)
    {
        $this->client->indices()->putAlias([
            'index' => $index,
            'name' => $alias,
        ]);
    }

    /**
     * Switch index alias
     *
     * @param string $fromIndex
     * @param string $toIndex
     * @param string $alias
     * @return void
     */
    public function switchAlias($fromIndex, $toIndex, $alias)
    {
        $params['body'] = [
            'actions' => [
                [
                    'remove' => [
                        'index' => $fromIndex,
                        'alias' => $alias,
                    ],
                ],
                [
                    'add' => [
                        'index' => $toIndex,
                        'alias' => $alias,
                    ],
                ],
            ],
        ];

        $this->client->indices()->updateAliases($params);
    }

    /**
     * Generate index name
     *
     * @return string
     */
    public function generateIndexName()
    {
        return self::INDEX_PREFIX . '_' . time();
    }
}
