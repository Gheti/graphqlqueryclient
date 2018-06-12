<?php
namespace GraphQLQueryClient;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\RequestException;

class GraphQLQueryClient
{
    /**
     * Request methods.
     *
     * @const METHODS
     */
    const METHODS = [
        'get',
        'delete',
        'head',
        'options',
        'patch',
        'post',
        'put'
    ];

    /**
     * Request parameters.
     *
     * @var array
     */
    private $options = [
        'endpoint' => null,
        'method' => 'POST',
        'headers' =>  [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ],
        'params' => null,
    ];

    /**
     * Filled with data returned from request.
     *
     * @var $response | null
     */
    private $response = null;

    /**
     * Filled with error returned from request.
     *
     * @var $err | null
     */
    private $err = null;

    /**
     * Get method Type.
     *
     * @return string
     */
    private function getMethod(): string
    {
        if (in_array(strtolower($this->options['method']), self::METHODS)) {
            $method = strtolower($this->options['method']);
            $method .= (isset($this->options['success']) && !empty($this->options['success'])) ? 'Async' : '';
            return $method;
        }

        return 'post';
    }

    /**
     * Parse headers.
     *
     * @return array
     */
    private function parseHeaders(): array
    {
       if(isset($this->options['headers']) && empty($this->options['headers'])) {
            $this->options['headers'] = [
               'Accept' => 'application/json',
               'Content-Type' => 'application/json',
           ];
       }
       return $this->options['headers'];
    }

    /**
     * Parse params represents GraphQL query which can be sent as string formatted as GraphQL query, or
     * as array which contains query variables and data for query type and format.
     *
     * @return string
     * @throws \Exception
     */
    private function parseParams(): string
    {
        if (is_array($this->options['params'])) {
            if (!isset($this->options['params']['query'])) {
                throw new \Exception('Please define query.');
            }
            $type = (@$this->options['params']['type'] == 'mutation') ? 'buildMutation' : 'buildQuery';
            return json_encode(
                QueryBuilder::{$type}($this->options['params']['query'], $this->options['params']['variables'])->array,
                ($this->options['params']['assoc'] ?? false) ? JSON_FORCE_OBJECT : null
            );
        } else {
            return $this->options['params'];
        }
    }

    /**
     * Send request.
     *
     * @return void
     * @throws \Exception
     */
    private function send()
    {
        try {
            $client = new Client();
            $result = $client->{$this->getMethod()}(
                $this->options['endpoint'],
                [
                    'headers' => $this->parseHeaders(),
                    'body' => $this->parseParams()
                ]
            );

            Promise\settle($result)->wait();

            if (isset($this->options['success']) && !empty($this->options['success'])) {
                $result->then($this->options['success'], $this->options['error'] ?? null);
            } else {
                $this->response = $result->getBody()->getContents();
            }
        } catch (RequestException $e) {
            $this->err = $e->getResponse();
            if (isset($this->options['error'])) {
                call_user_func_array($this->options['error'], [$this->err]);
            }
        }
    }

    /**
     * GraphQLHttp constructor.
     *
     * @param $options
     * @throws \Exception
     */
    public function __construct($options)
    {
        $this->options  = array_merge($this->options, $options);
        $this->send();
    }

    /**
     * Success response.
     *
     * @param boolean $assoc
     * @return mixed
     */
    public function success($assoc = true)
    {
        return json_decode($this->response, $assoc);
    }

    /**
     * Error response.
     *
     * @return mixed
     */
    public function error()
    {
        return $this->err;
    }


}