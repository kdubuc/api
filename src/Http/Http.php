<?php

namespace API\Http;

use League\Fractal;
use RuntimeException;
use API\Domain\Collection;
use Pagerfanta\Pagerfanta;
use API\Feature\KernelAccess;
use API\Transformer\Transformer;
use API\Domain\ValueObject\ValueObject;
use Psr\Http\Message\ResponseInterface as Response;
use League\Fractal\Pagination\PagerfantaPaginatorAdapter as FractalPaginatorAdapter;

abstract class Http
{
    use KernelAccess;

    // HTTP Statuses : 1×× Informational
    public const STATUS_CONTINUE            = 100;
    public const STATUS_SWITCHING_PROTOCOLS = 101;
    public const STATUS_PROCESSING          = 102;

    // HTTP Statuses : 2×× Success
    public const STATUS_OK                            = 200;
    public const STATUS_CREATED                       = 201;
    public const STATUS_ACCEPTED                      = 202;
    public const STATUS_NON_AUTHORITATIVE_INFORMATION = 203;
    public const STATUS_NO_CONTENT                    = 204;
    public const STATUS_RESET_CONTENT                 = 205;
    public const STATUS_PARTIAL_CONTENT               = 206;
    public const STATUS_MULTI_STATUS                  = 207;
    public const STATUS_ALREADY_REPORTED              = 208;
    public const STATUS_IM_USED                       = 226;

    // HTTP Statuses : 3×× Redirection
    public const STATUS_MULTIPLE_CHOICES   = 300;
    public const STATUS_MOVED_PERMANENTLY  = 301;
    public const STATUS_FOUND              = 302;
    public const STATUS_SEE_OTHER          = 303;
    public const STATUS_NOT_MODIFIED       = 304;
    public const STATUS_USE_PROXY          = 305;
    public const STATUS_TEMPORARY_REDIRECT = 307;
    public const STATUS_PERMANENT_REDIRECT = 308;

    // HTTP Statuses : 4×× Client Error
    public const STATUS_BAD_REQUEST                     = 400;
    public const STATUS_UNAUTHORIZED                    = 401;
    public const STATUS_PAYMENT_REQUIRED                = 402;
    public const STATUS_FORBIDDEN                       = 403;
    public const STATUS_NOT_FOUND                       = 404;
    public const STATUS_METHOD_NOT_ALLOWED              = 405;
    public const STATUS_NOT_ACCEPTABLE                  = 406;
    public const STATUS_PROXY_AUTHENTICATION_REQUIRED   = 407;
    public const STATUS_REQUEST_TIMEOUT                 = 408;
    public const STATUS_CONFLICT                        = 409;
    public const STATUS_GONE                            = 410;
    public const STATUS_LENGTH_REQUIRED                 = 411;
    public const STATUS_PRECONDITION_FAILED             = 412;
    public const STATUS_PAYLOAD_TOO_LARGE               = 413;
    public const STATUS_REQUEST_URI_TOO_LONG            = 414;
    public const STATUS_UNSUPPORTED_MEDIA_TYPE          = 415;
    public const STATUS_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    public const STATUS_EXPECTATION_FAILED              = 417;
    public const STATUS_IM_A_TEAPOT                     = 418;
    public const STATUS_MISDIRECTED_REQUEST             = 421;
    public const STATUS_UNPROCESSABLE_ENTITY            = 422;
    public const STATUS_LOCKED                          = 423;
    public const STATUS_FAILED_DEPENDENCY               = 424;
    public const STATUS_UPGRADE_REQUIRED                = 426;
    public const STATUS_PRECONDITION_REQUIRED           = 428;
    public const STATUS_TOO_MANY_REQUESTS               = 429;
    public const STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    public const STATUS_UNAVAILABLE_FOR_LEGAL_REASONS   = 451;
    public const STATUS_CLIENT_CLOSED_REQUEST           = 499;

    // HTTP Statuses : 5×× Server Error
    public const STATUS_INTERNAL_SERVER_ERROR           = 500;
    public const STATUS_NOT_IMPLEMENTED                 = 501;
    public const STATUS_BAD_GATEWAY                     = 502;
    public const STATUS_SERVICE_UNAVAILABLE             = 503;
    public const STATUS_GATEWAY_TIMEOUT                 = 504;
    public const STATUS_HTTP_VERSION_NOT_SUPPORTED      = 505;
    public const STATUS_VARIANT_ALSO_NEGOTIATES         = 506;
    public const STATUS_INSUFFICIENT_STORAGE            = 507;
    public const STATUS_LOOP_DETECTED                   = 508;
    public const STATUS_NOT_EXTENDED                    = 510;
    public const STATUS_NETWORK_AUTHENTICATION_REQUIRED = 511;
    public const STATUS_NETWORK_CONNECT_TIMEOUT_ERROR   = 599;

    /**
     * Bind a collection to a transformer and start building a response.
     */
    public function collection(Response $response, Collection $collection, Transformer $transformer, int $status = self::STATUS_OK) : Response
    {
        $resource = new Fractal\Resource\Collection($collection, $transformer);

        $resource->setMeta($collection->getMeta());

        $data = $this->getKernel()->get('fractal.json')->createData($resource)->toArray();

        return $this->json($response, $data, $status);
    }

    /**
     * Bind a model to a transformer and start building a response.
     */
    public function item(Response $response, ValueObject $data, Transformer $transformer, int $status = self::STATUS_OK) : Response
    {
        $resource = new Fractal\Resource\Item($data, $transformer);

        $data = $this->getKernel()->get('fractal.json')->createData($resource)->toArray();

        return $this->json($response, $data, $status);
    }

    /**
     * Json.
     */
    public function json(Response $response, array $data, int $status = self::STATUS_OK) : Response
    {
        $body = $response->getBody();
        $body->rewind();
        $body->write($json = json_encode($data));

        if (false === $json) {
            throw new RuntimeException(json_last_error_msg(), json_last_error());
        }

        $response_with_json = $response->withHeader('Content-Type', 'application/json;charset=utf-8');

        return $response_with_json->withStatus($status);
    }

    /**
     * Paginate.
     */
    public function pagination(Response $response, Pagerfanta $paginator, Transformer $transformer, int $status = self::STATUS_OK) : Response
    {
        $collection = $paginator->getCurrentPageResults();

        $resource = new Fractal\Resource\Collection($collection, $transformer);

        if($collection instanceof Collection) {
            $resource->setMeta($collection->getMeta());
        }

        $resource->setPaginator(new FractalPaginatorAdapter($paginator, function (int $page) {
            return null;
        }));

        $data = $this->getKernel()->get('fractal.json')->createData($resource)->toArray();

        unset($data['meta']['pagination']['links']);

        return $this->json($response, $data, $status);
    }
}
