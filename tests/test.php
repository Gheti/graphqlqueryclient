<?php

require '../vendor/autoload.php';

use GraphQLQueryClient\GraphQLQueryClient;

$url = 'https://fakerql.com/graphql';
$headers = [
    'Accept' => 'application/json',
    'Content-Type' => 'application/json'
];
$params = [
    'allPosts' => [
        '_arguments' => [
            'count' => 10 // Add arguments of query Ex. allPosts(count:10)
        ],
        'id',
        'title',
        'body',
        'published'
    ]
];

$variables = [];
try {
    // GraphQLQueryClient in asynchronous way.
    (new GraphQLQueryClient([
        /**
         * @var string endpoint of api
         */
        'endpoint' => $url,

        /**
         * @var string method of api request
         */
        'method' => 'POST',

        /**
         * @var array headers of api request
         */
        'headers' => [],

        /**
         * Params represents GraphQL query which can be sent as string formatted as GraphQL query, or
         * as array which contains query variables and data for query type and format.
         *
         * @var array | string
         */
        'params' => [

            /**
             * @var array GraphQL query.
             */
            'query' => $params,

            /**
             * @var array GraphQL variables.
             */
            'variables' => $variables,

            # query
            # mutation
            'type' => 'query', //Defines type of GraphQL request; optional.

            #true
            #false
            'assoc' => false //Converts array as associative which when `true` result in `{}` instead of `[]`; optional.
        ],

        /**
         * Success represents an asynchronous request and success function is executed as callback.
         *
         * $param $res      Represents result turned back from request
         */
        'success' => function ($res) {
            echo "Asynchronous response <pre>";
            print_r(json_decode($res->getBody()->getContents(), true));

        },

        /**
         * Error executes in case of error in request.
         *
         * $param $res      Represents result turned back from request
         */
        'error' => function ($err) {
            echo $err->getMessage() . "\n";
            echo $err->getRequest()->getMethod();
        }
    ]));

    echo "If im being displayed above asynchronous response means that package is working properly. :) <br>";

    // GraphQLQueryClient in synchronous way.
    $client = (new GraphQLQueryClient([
        'endpoint' => $url,
        'method' => 'POST',
        'headers' => [],
        'params' => [
            'query' => $params,
            'variables' => $variables,
        ]
    ]));
    echo "Synchronous response <br>";
    $response = $client->success();
    $error = $client->error();
    print_r($response, $error);

} catch (Exception $e) {
    echo $e->getMessage();
}



