<?php

namespace Illuminate\Foundation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrustHosts
{
    /**
     * The trusted host names.
     *
     * @var array
     */
    protected $trustedHosts = [];

    /**
     * Gets the trusted host names.
     *
     * @return array
     */
    public function getTrustedHosts()
    {
        return $this->trustedHosts;
    }

    /**
     * Sets trusted host names.
     *
     * @param array $trustedHosts
     */
    public function setTrustedHosts($trustedHosts)
    {
        $this->trustedHosts = $trustedHosts;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $request->setTrustedHosts($this->getTrustedHosts());

        return $next($request);
    }
}
