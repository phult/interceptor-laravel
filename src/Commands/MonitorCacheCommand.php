<?php

namespace Megaads\Interceptor\Commands;

use Illuminate\Console\Command;
use Megaads\Interceptor\Cache\CacheStore;
use Symfony\Component\Console\Input\InputArgument;

class MonitorCacheCommand extends AbtractCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'interceptor:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache monitor';

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
            ['type', InputArgument::REQUIRED, 'list | check | outofdate'],
            ['typeValue', InputArgument::OPTIONAL, 'null | url | refreshRate'],
        ];
    }
    /**
     * Execute the console command.
     */
    public function fire()
    {
        $result = null;
        $type = $this->argument('type');
        $typeValue = $this->argument('typeValue');
        if ($type === 'list') {
            $result = $this->cacheStore->listResponseKeys($typeValue);
        } else if ($type === 'check') {
            $result = $this->cacheStore->checkResponseKeys($typeValue);
        } else if ($type === 'outofdate') {
            $refreshRate = $typeValue != null ? $typeValue : \Config::get('interceptor.refreshRate', 86400);
            $result = $this->cacheStore->getOutOfDateResponses(0, (time() - $refreshRate), -1);
        }
        $this->response([
            'status' => 'successful',
            'result' => [
                'count' => count($result),
                'data' => $result,
            ],
        ]);
    }
}
