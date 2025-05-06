<?php

namespace Albaroody\Staging\Commands;

use Illuminate\Console\Command;

class StagingCommand extends Command
{
    public $signature = 'staging';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
