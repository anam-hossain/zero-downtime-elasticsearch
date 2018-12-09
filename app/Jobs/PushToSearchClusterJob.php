<?php

namespace App\Jobs;

use App\Handlers\IndexHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PushToSearchClusterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var integer
     */
    public $id;

    /**
     * @var array
     */
    public $data;

    /**
     * Create a new job instance.
     *
     * @param integer $id
     * @param array $data
     * @return void
     */
    public function __construct($id, array $data)
    {
        $this->id = $id;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(IndexHandler $indexHandler)
    {
        $indexHandler->indexDataUsingAlias($this->id, $this->data);
    }
}
