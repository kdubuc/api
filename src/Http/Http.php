<?php

namespace API\Http;

use API\Domain\Collection;
use API\Domain\Model;
use API\Feature\ContainerAccess;
use API\Transformer\Transformer;
use League\Fractal;
use Psr\Http\Message\ResponseInterface as Response;
use RuntimeException;

abstract class Http
{
    use ContainerAccess;

    // HTTP Statuses : 1×× Informational
    const STATUS_CONTINUE            = 100;
    const STATUS_SWITCHING_PROTOCOLS = 101;
    const STATUS_PROCESSING          = 102;

    // HTTP Statuses : 2×× Success
    const STATUS_OK                            = 200;
    const STATUS_CREATED                       = 201;
    const STATUS_ACCEPTED                      = 202;
    const STATUS_NON_AUTHORITATIVE_INFORMATION = 203;
    const STATUS_NO_CONTENT                    = 204;
    const STATUS_RESET_CONTENT                 = 205;
    const STATUS_PARTIAL_CONTENT               = 206;
    const STATUS_MULTI_STATUS                  = 207;
    const STATUS_ALREADY_REPORTED              = 208;
    const STATUS_IM_USED                       = 226;

    // HTTP Statuses : 3×× Redirection
    const STATUS_MULTIPLE_CHOICES   = 300;
    const STATUS_MOVED_PERMANENTLY  = 301;
    const STATUS_FOUND              = 302;
    const STATUS_SEE_OTHER          = 303;
    const STATUS_NOT_MODIFIED       = 304;
    const STATUS_USE_PROXY          = 305;
    const STATUS_TEMPORARY_REDIRECT = 307;
    const STATUS_PERMANENT_REDIRECT = 308;

    // HTTP Statuses : 4×× Client Error
    const STATUS_BAD_REQUEST                     = 400;
    const STATUS_UNAUTHORIZED                    = 401;
    const STATUS_PAYMENT_REQUIRED                = 402;
    const STATUS_FORBIDDEN                       = 403;
    const STATUS_NOT_FOUND                       = 404;
    const STATUS_METHOD_NOT_ALLOWED              = 405;
    const STATUS_NOT_ACCEPTABLE                  = 406;
    const STATUS_PROXY_AUTHENTICATION_REQUIRED   = 407;
    const STATUS_REQUEST_TIMEOUT                 = 408;
    const STATUS_CONFLICT                        = 409;
    const STATUS_GONE                            = 410;
    const STATUS_LENGTH_REQUIRED                 = 411;
    const STATUS_PRECONDITION_FAILED             = 412;
    const STATUS_PAYLOAD_TOO_LARGE               = 413;
    const STATUS_REQUEST_URI_TOO_LONG            = 414;
    const STATUS_UNSUPPORTED_MEDIA_TYPE          = 415;
    const STATUS_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const STATUS_EXPECTATION_FAILED              = 417;
    const STATUS_IM_A_TEAPOT                     = 418;
    const STATUS_MISDIRECTED_REQUEST             = 421;
    const STATUS_UNPROCESSABLE_ENTITY            = 422;
    const STATUS_LOCKED                          = 423;
    const STATUS_FAILED_DEPENDENCY               = 424;
    const STATUS_UPGRADE_REQUIRED                = 426;
    const STATUS_PRECONDITION_REQUIRED           = 428;
    const STATUS_TOO_MANY_REQUESTS               = 429;
    const STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    const STATUS_UNAVAILABLE_FOR_LEGAL_REASONS   = 451;
    const STATUS_CLIENT_CLOSED_REQUEST           = 499;

    // HTTP Statuses : 5×× Server Error
    const STATUS_INTERNAL_SERVER_ERROR           = 500;
    const STATUS_NOT_IMPLEMENTED                 = 501;
    const STATUS_BAD_GATEWAY                     = 502;
    const STATUS_SERVICE_UNAVAILABLE             = 503;
    const STATUS_GATEWAY_TIMEOUT                 = 504;
    const STATUS_HTTP_VERSION_NOT_SUPPORTED      = 505;
    const STATUS_VARIANT_ALSO_NEGOTIATES         = 506;
    const STATUS_INSUFFICIENT_STORAGE            = 507;
    const STATUS_LOOP_DETECTED                   = 508;
    const STATUS_NOT_EXTENDED                    = 510;
    const STATUS_NETWORK_AUTHENTICATION_REQUIRED = 511;
    const STATUS_NETWORK_CONNECT_TIMEOUT_ERROR   = 599;

    /**
     * Bind a collection to a transformer and start building a response.
     *
     * @param Psr\Http\Message\ResponseInterface $response
     * @param API\Domain\Collection              $collection
     * @param API\Transformer\Transformer        $transformer
     * @param int                                $status
     *
     * @return Psr\Http\Message\ResponseInterface
     */
    public function collection(Response $response, Collection $collection, Transformer $transformer, int $status = self::STATUS_OK) : Response
    {
        $resource = new Fractal\Resource\Collection($collection->toArray(), $transformer);

        /*
        $resource->setMetaValue('ok', [
            'ok' => 'okay'
        ]);
        */

        $data = $this->getContainer()->get('fractal')->createData($resource)->toArray();

        return $this->json($response, $data, $status);
    }

    /**
     * Bind a model to a transformer and start building a response.
     *
     * @param Psr\Http\Message\ResponseInterface      $response
     * @param API\Domain\Model\Api\Domain\ValueObject $data
     * @param API\Transformer\Transformer             $transformer
     * @param int                                     $status
     *
     * @return Psr\Http\Message\ResponseInterface
     */
    public function item(Response $response, $data, Transformer $transformer, int $status = self::STATUS_OK) : Response
    {
        $resource = new Fractal\Resource\Item($data, $transformer);

        $data = $this->getContainer()->get('fractal')->createData($resource)->toArray();

        return $this->json($response, $data, $status);
    }

    /**
     * Json.
     *
     * @param Psr\Http\Message\ResponseInterface $response
     * @param array                              $data
     * @param int                                $status
     *
     * @return Psr\Http\Message\ResponseInterface
     */
    public function json(Response $response, array $data, int $status = self::STATUS_OK) : Response
    {
        $body = $response->getBody();
        $body->rewind();
        $body->write($json = json_encode($data));

        if ($json === false) {
            throw new RuntimeException(json_last_error_msg(), json_last_error());
        }

        $response_with_json = $response->withHeader('Content-Type', 'application/json;charset=utf-8');

        return $response_with_json->withStatus($status);
    }
}
