<?php

namespace App\Handlers;

use Elasticsearch\Client;
use Exception;
use Illuminate\Support\Facades\Log;

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
     * Write alias
     */
    const WRITE_ALIAS = 'world_write';

    /**
     * Read alias
     */
    const READ_ALIAS = 'world_read';

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
            $isIndexExist = $this->client->indices()->exists(['index' => $index]);

            if (!$isIndexExist) {
                $this->client->indices()->create($this->params($index));
            }
        } catch (Exception $e) {
            Log::error('Unable to create index', [
                'message' => $e->getMessage(),
                'index' => $index,
            ]);

            throw $e;
        }
    }

    /**
     * Add write alias to index
     *
     * @param string $index
     * @return void
     */
    public function addWriteAlias($index)
    {
        $this->addAlias($index, self::WRITE_ALIAS);
    }

    /**
     * Add read alias to index
     *
     * @param string $index
     * @return void
     */
    public function addReadAlias($index)
    {
        $this->addAlias($index, self::READ_ALIAS);
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
        try {
            $this->client->indices()->putAlias([
                'index' => $index,
                'name' => $alias,
            ]);
        } catch (Exception $e) {
            Log::error('Unable to add index alias', [
                'message' => $e->getMessage(),
                'index' => $index,
                'alias' => $alias,
            ]);

            throw $e;
        }
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

        try {
            $this->client->indices()->updateAliases($params);
        } catch (Exception $e) {
            Log::error('Index alias switching failed', [
                'message' => $e->getMessage(),
                'fromIndex' => $fromIndex,
                'toIndex' => $toIndex,
                'alias' => $alias,
            ]);

            throw $e;
        }
    }

    /**
     * Get index by alias
     *
     * @return string
     */
    public function getIndexByAlias($alias)
    {
        try {
            $indices = $this->client->indices()->getAlias(['name' => $alias]);

            list($index) = array_keys($indices);

        } catch (Exception $e) {
            Log::error('Unable to get index by alias', [
                'message' => $e->getMessage(),
                'alias' => $alias,
            ]);

            throw $e;
        }

        return $index;
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

    /**
     * Index data
     *
     * @param integer $id
     * @param array $data
     * @return void
     */
    public function indexDataUsingAlias($id, array $data)
    {
        try {
            $this->client->index([
                'index' => self::WRITE_ALIAS,
                'type' => self::TYPE,
                'id' => $id,
                'body' => $data,
            ]);
        } catch (Exception $e) {
            Log::critical('Indexing failed', [
                'id' => $id,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
        }
    }

    /**
     * Remove search index
     *
     * @param string $index
     * @return array
     */
    public function removeIndex($index)
    {
        return $this->client->indices()->delete(['index' => $index]);
    }

    /**
     * Index params
     *
     * @param string $index
     * @return array
     */
    protected function params($index)
    {
        return [
            'index' => $index,
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
                            'LocalName' => [
                                'type' => 'keyword',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
