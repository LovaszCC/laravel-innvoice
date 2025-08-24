<?php

declare(strict_types=1);

namespace LovaszCC\LaravelInnvoice\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use LovaszCC\LaravelInnvoice\LaravelInnvoiceServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'LovaszCC\\LaravelInnvoice\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelInnvoiceServiceProvider::class,
        ];
    }
}
