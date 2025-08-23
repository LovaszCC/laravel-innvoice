<?php

namespace LovaszCC\LaravelInnvoice\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \LovaszCC\LaravelInnvoice\LaravelInnvoice
 */
class LaravelInnvoice extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \LovaszCC\LaravelInnvoice\LaravelInnvoice::class;
    }
}
