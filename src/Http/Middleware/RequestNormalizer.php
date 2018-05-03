<?php

namespace API\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/*
 * If multiple fields of the same name exist in a query string, getQueryParams()
 * silently overwrites them (because of parse_str()). To handle uri like :
 * ?id=1&id=2, we normalize the input into a PHP compatible one
 * (eg : ?id[]=1&id[]=2).
 * GitHub Discussion : https://github.com/slimphp/Slim/issues/2378
 * Heavily inspired of https://secure.php.net/manual/fr/function.parse-str.php#76792 (thanks Evan K !)
 */
class RequestNormalizer extends Middleware
{
    /**
     * Middleware handler.
     */
    public function __invoke(Request $request, Response $response, callable $next) : Response
    {
        // Get the current query string in the request
        $query_string = $request->getUri()->getQuery();

        // If the query is empty, we return the $request unedited
        if (empty($query_string) || false === strpos($query_string, '=')) {
            return $next($request, $response);
        }

        // Split on outer delimiter
        $pairs = explode('&', $query_string);

        // Initialize the results array
        $results = [];

        // Loop through each pair
        foreach ($pairs as $pair) {
            // Split into name and value
            [$name, $value] = explode('=', $pair, 2);

            // If name already exists
            if (isset($results[$name])) {
                // Stick multiple values into an array
                if (is_array($results[$name])) {
                    $results[$name][] = $value;
                } else {
                    $results[$name] = [$results[$name], $value];
                }
            }
            // Otherwise, simply stick it in a scalar
            else {
                $results[$name] = $value;
            }
        }

        // Build the new request with the correct query string
        $request = $request->withUri($request->getUri()->withQuery(http_build_query($results)));

        return $next($request, $response);
    }
}
