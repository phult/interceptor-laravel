<?php

namespace Megaads\Interceptor\Commands;

use Illuminate\Console\Command;
use Megaads\Interceptor\Cache\CacheStore;
use Megaads\Interceptor\Cache\CacheWorker;
use Symfony\Component\Console\Input\InputArgument;

class RefreshCacheCommand extends AbtractCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'interceptor:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh cache data';

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
            ['type', InputArgument::REQUIRED, 'outofdate | url'],
            ['value', InputArgument::REQUIRED, 'limit | url'],
        ];
    }
    /**
     * Execute the console command.
     */
    public function fire()
    {
        $result = null;
        $type = $this->argument('type');
        $value = $this->argument('value');
        if ($type == 'outofdate') {
            $result = $this->cacheWorker->refreshOutOfDateCache($value);
        } else if ($type == 'url') {
            $this->cacheWorker->refreshCache($value);
        }
        $this->response([
            'status' => 'successful',
            'result' => $result,
        ]);
    }
}
