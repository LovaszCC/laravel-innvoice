<?php

namespace LovaszCC\LaravelInnvoice\Commands;

use Illuminate\Console\Command;

class LaravelInnvoiceCommand extends Command
{
    public $signature = 'laravel-innvoice';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
