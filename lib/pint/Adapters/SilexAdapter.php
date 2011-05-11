<?php

namespace pint\Adapters;

use \pint\App\AppAbstract;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

class SilexAdapter extends AppAbstract
{
    /**
     * @var  \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    protected $kernel = null;

    public function __construct(HttpKernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    final public function call(\pint\Request $env)
    {
        $kernel  = clone $this->kernel;
        $request = $this->createRequestFromEnv($env);

        try {
            $response = $kernel->handle($request);
        } catch(\Exception $e) {
            return array(500, array(), 'Symfony2Adapter: ' . $e->__toString());
        }

        $headers = array_merge(array(
            "Content-Type"    => 'text/html'
        ), $response->headers->all());

        return $this->next($env, array(
            $response->getStatusCode(),
            $headers,
            $response->getContent()
        ));
    }

    protected function createRequestFromEnv(\pint\Request $env)
    {
        $query      = $env->paramsGet();
        $request    = $env->paramsPost();
        $attributes = array();
        $cookies    = array();
        $files      = $env->files();
        $server     = $env->server();
        $content    = $env->body();

        return new Request(
            $query, $request, $attributes, $cookies, $files, $server, $content);
    }
}
