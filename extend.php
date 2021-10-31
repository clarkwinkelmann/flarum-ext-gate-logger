<?php

namespace ClarkWinkelmann\GateLogger;

use Flarum\Extend;

return [
    (new Extend\ServiceProvider())
        ->register(ServiceProvider::class),
];
