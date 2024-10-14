<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Authorizations\AuthInternalMiddleware;
use App\Http\Middleware\Authorizations\AuthPrivateMiddleware;
use App\Http\Middleware\Authorizations\AuthPublicMiddleware;
use App\Http\Middleware\Cors;
use Illuminate\Foundation\Configuration\Middleware as BaseMiddleware;
use \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

class Handler
{
    protected array $aliases = [
        'auth.private'  => AuthPrivateMiddleware::class,
        'auth.public'   => AuthPublicMiddleware::class,
        'auth.internal' => AuthInternalMiddleware::class,
    ];

    public function __invoke(BaseMiddleware $middleware): BaseMiddleware
    {
//        $middleware->append(Cors::class);
//        $middleware->append(StripTags::class);

        if ($this->aliases) {
            $middleware->alias($this->aliases);
        }

        return $middleware;
    }
}
