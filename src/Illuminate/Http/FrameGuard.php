<?php namespace Illuminate\Http; use Symfony\Component\HttpKernel\HttpKernelInterface; use Symfony\Component\HttpFoundation\Request as SymfonyRequest; class FrameGuard implements HttpKernelInterface { protected $app; public function __construct(HttpKernelInterface $app) { $this->app = $app; } public function handle(SymfonyRequest $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true) { $response = $this->app->handle($request, $type, $catch); $response->headers->set('X-Frame-Options', 'SAMEORIGIN', false); return $response; } }
