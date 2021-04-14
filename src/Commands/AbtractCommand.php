<?php
namespace Megaads\Interceptor\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class AbtractCommand extends Command
{
    /**
     * Get the console command arguments.
     *
     * @return array
     */
    public function getArguments()
    {
        return [
            ['name', InputArgument::IS_ARRAY, 'name'],
        ];
    }
    protected function response($data)
    {
        $data['time'] = date('Y-m-d H:i:s');
        $response = json_encode($data);
        if ($data['status'] == 'successful') {
            $this->info($response);
        } else {
            $this->error($response);
        }
    }
}
