<?php

namespace Albaroody\Staging\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Albaroody\Staging\Staging
 */
class Staging extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Albaroody\Staging\Staging::class;
    }
}
