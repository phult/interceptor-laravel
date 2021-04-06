<?php

namespace Megaads\Interceptor\Commands;

use Illuminate\Console\Command;
use Megaads\Interceptor\Cache\CacheStore;
use Symfony\Component\Console\Input\InputArgument;

class RemoveCacheCommand extends AbtractCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'interceptor:remove';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove cache data';

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
     * Get the console command arguments.
     *
     * @return array
     */
    public function getArguments()
    {
        return [
            ['url', InputArgument::REQUIRED, 'The cached URL to refresh, place blank value to refresh expired data'],
        ];
    }
    /**
     * Execute the console command.
     */
    public function fire()
    {
        $url = $this->argument('url');
        $result = $this->cacheStore->remove($url);
        $this->response([
            'status' => $result ? 'successful' : 'failed',
        ]);
    }
}
