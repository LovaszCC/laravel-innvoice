<?php

declare(strict_types=1);

namespace LovaszCC\LaravelInnvoice\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \LovaszCC\LaravelInnvoice\LaravelInnvoice
 */
final class LaravelInnvoice extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \LovaszCC\LaravelInnvoice\LaravelInnvoice::class;
    }
}
