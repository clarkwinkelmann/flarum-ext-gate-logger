<?php

namespace ClarkWinkelmann\GateLogger;

use Flarum\Foundation\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->bind(\Flarum\User\Access\Gate::class, Gate::class);
    }
}
