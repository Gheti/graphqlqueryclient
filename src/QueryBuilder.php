<?php

namespace GraphQLQueryClient;

class QueryBuilder
{
    /**
     * Data.
     *
     * @var array
     */
    private static $data;

    /**
     * Parse parameters.
     *
     * @param array $parameters
     * @return string
     */
    private static function parseParameters(array $parameters): string
    {
        $parametersQuery = '(';
        $lastKey = key(array_slice($parameters, -1, 1, TRUE));
        foreach ($parameters as $key => $parameter) {
            $parametersQuery .= $key . ': ' . $parameter;
            $parametersQuery .= ($lastKey != $key) ? ', ' : ') ';
        }
        return $parametersQuery;
    }

    /**
     * Parse query.
     *
     * @param array $data
     *      # for same index on array add underscore (_) before or after name of index.
     * @return string
     */
    private static function parseQuery(array $data): string
    {
        $query = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $query .= str_replace("_", "", $key) . ' ';
                if (array_key_exists('_arguments', $value)) {
                    $query .= self::parseParameters($value['_arguments']);
                    unset($value['_arguments']);
                }
                $query .= "{" . self::parseQuery($value) . "}";
            } else {
                $query .= $value . ' ';
            }
        }
        return $query;
    }

    /**
     * GraphQl query builder.
     *
     * @param array $query
     * @param array|null $variables
     * @return QueryBuilder
     */
    public static function buildQuery(array $query, array $variables = null): QueryBuilder
    {
        $query = ['query' => $query];
        $parsedQuery = self::parseQuery($query);
        $queryData = [
            'query' => $parsedQuery,
        ];
        if($variables && !empty($variables)) {
            $queryData['variables'] = $variables;
        }
        self::$data = $queryData;
        return new static();
    }

    /**
     *  GraphQl mutation builder.
     *
     * @param array $mutation
     * @param array $variables
     * @return QueryBuilder
     */
    public static function buildMutation(array $mutation, array $variables): QueryBuilder
    {
        $mutation = ['mutation' => $mutation];
        $parsedQuery = self::parseQuery($mutation);
        $queryData = [
            'query' => $parsedQuery,
            'variables' => $variables
        ];

        self::$data = $queryData;
        return new static();
    }

    /**
     * Get array.
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if ($name == 'array') {
            return self::$data;
        }
        return $this->{$name};
    }

    /**
     * Get string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return json_encode(self::$data);
    }
}