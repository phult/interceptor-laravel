<?php

namespace Megaads\Interceptor\Commands;

use Illuminate\Console\Command;
use Megaads\Interceptor\Cache\CacheStore;
use Megaads\Interceptor\Cache\CacheWorker;
use Symfony\Component\Console\Input\InputArgument;

class GarbageCacheCommand extends AbtractCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'interceptor:garbage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'garbage collector';

    /**
     * Cache instance.
     *
     */
    protected $cacheStore;
    protected $cacheWorker;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->cacheStore = new CacheStore();
        $this->cacheWorker = new CacheWorker();
    }
    /**
     * Get the console command arguments.
     *
     * @return array
     */
    public function getArguments()
    {
        return [
            ['type', InputArgument::REQUIRED, 'clear | list'],
            ['maxCacheSize', InputArgument::OPTIONAL, 'max cache size'],
        ];
    }
    /**
     * Execute the console command.
     */
    public function fire()
    {
        $result = null;
        $type = $this->argument('type');
        $maxCacheSize = $this->argument('maxCacheSize');
        if ($type == 'clear') {
            $result = $this->cacheWorker->clearGarbageCache($maxCacheSize);
        } else if ($type == 'list') {
        }
        $this->response([
            'status' => 'successful',
            'result' => $result,
        ]);
    }
}
