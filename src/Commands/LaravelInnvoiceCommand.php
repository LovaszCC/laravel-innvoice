<?php

declare(strict_types=1);

namespace LovaszCC\LaravelInnvoice\Commands;

use Illuminate\Console\Command;

final class LaravelInnvoiceCommand extends Command
{
    public $signature = 'laravel-innvoice';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
