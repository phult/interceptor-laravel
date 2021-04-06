<?php

namespace Megaads\Interceptor\Commands;

use Illuminate\Console\Command;
use Megaads\Interceptor\Cache\CacheStore;

class FlushCacheCommand extends AbtractCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'interceptor:flush';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush all cache data';

    /**
     * Cache instance.
     *
     */
    protected $cacheStore;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->cacheStore = new CacheStore();
    }

    /**
     * Execute the console command.
     */
    public function fire()
    {
        $this->cacheStore->flush();
        $this->response([
            'status' => 'successful'
        ]);
    }
}
