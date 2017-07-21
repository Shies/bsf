<?php

namespace bsf;

use bsf\Http\Request as HttpRequest;

/**
 * 路由类，封装server路由.
 *
 * @author  gukai@bilibili.com
 */
class Router
{
    protected static $routes = array();
    protected $notFound;

    public function __call($method, $params)
    {
        $accept_method = array('get', 'post', 'patch', 'delete', 'put', 'options', 'console'); // console is just for tcp request
        if (in_array($method, $accept_method) && count($params) >= 2) {
            $this->match($method, $params[0], $params[1]);
        }
    }

    // set for 404
    public function set404($fn)
    {
        if (is_string($fn) && strstr($fn, '@')) {
            $fn = explode('@', $fn);
        }
        $this->notFound = $fn;
    }

    public function match($methods, $pattern, $argvs)
    {
        $pattern = '/'.trim($pattern, '/');
        $fn = $argvs;
        $name = $middleware = null;
        if (is_array($argvs) && isset($argvs['uses'])) {
            $fn = $argvs['uses'];
            $middleware = isset($argvs['middleware']) ? $argvs['middleware'] : '';
        }
        if (is_string($fn) && strstr($fn, '@')) {
            $fn = explode('@', $fn);
        }

        foreach (explode('|', $methods) as $method) {
            static::$routes[$method][] = [
                'middleware' => $middleware,
                'pattern' => $pattern,
                'uses' => $fn,
            ];
        }
    }

    /**
     * Execute the router: Loop all defined before middlewares and routes, and execute the handling function if a match was found.
     *
     * @param object   $request  HTTP/TCP request
     * @param object   $response HTTP/TCP response
     * @param callable $callback Function to be executed after a matching route was handled (= after router handled)
     */
    public function run($request, $response, $callback = null)
    {
        $method = ($request instanceof HttpRequest) ? $request->getMethod() : 'console';

        $response = $this->handle(static::$routes[$method], $request, $response);
        if ($response === false) {
            // handle 404
            $notFound = $this->notFound;
            if (!$notFound) {
                throw new \Exception('404 not found', 404);
            }
            if (is_array($notFound)) {
                $notFound[0] = new $notFound[0]();
            }
            if (!is_callable($notFound)) {
                throw new \Exception('404 not found', 404);
            }
            call_user_func($notFound, $response);
        } else {
            // end of this request
            if (is_callable($callback)) {
                call_user_func($callback, $response);
            } else {
                return $response;
            }
        }
    }

    /**
     * Handle a a set of routes: if a match is found, execute the relating handling function.
     *
     * @param array $routes Collection of route patterns and their handling functions
     *
     * @return int The number of routes handled
     */
    protected function handle($routes, $request, $response)
    {
        // request path
        $path = ($request instanceof HttpRequest) ? $request->getRequestUri() : $request->getPath();

        // Loop all routes
        foreach ($routes as $route) {

            // we have a match!
            if (preg_match_all('#^'.$route['pattern'].'$#', $path, $matches, PREG_OFFSET_CAPTURE)) {

                // Rework matches to only contain the matches, not the orig string
                $matches = array_slice($matches, 1);

                // Extract the matched URL parameters (and only the parameters)
                $params = array_map(
                    function ($match, $index) use ($matches) {
                        // We have a following parameter: take the substring from the current param position until the next one's position (thank you PREG_OFFSET_CAPTURE)
                        if (isset($matches[$index + 1]) && isset($matches[$index + 1][0]) && is_array($matches[$index + 1][0])) {
                            return trim(substr($match[0][0], 0, $matches[$index + 1][0][1] - $match[0][1]), '/');
                        } else {
                            // We have no following parameters: return the whole lot
                            return isset($match[0][0]) ? trim($match[0][0], '/') : null;
                        }

                    }, $matches, array_keys($matches)
                );
                $params = array_merge([$request, $response], $params);

                // call the handling function with the URL parameters
                if ($route['middleware']) {
                    $before = $route['middleware'];
                    if (is_string($before) && strstr($before, '@')) {
                        $before = explode('@', $before);
                        $before[0] = new $before[0]();
                    }
                    call_user_func_array($before, $params);
                }
                if (is_array($route['uses'])) {
                    $route['uses'][0] = new $route['uses'][0]();
                }

                return call_user_func_array($route['uses'], $params);
            }
        }

        return false;
    }
}
