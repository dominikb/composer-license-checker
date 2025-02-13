<?php

declare(strict_types=1);

if (! function_exists('array_map_keys')) {
    /**
     * @param  array  $input
     * @param  callable  $callback
     * @return array
     */
    function array_map_keys($input, $callback)
    {
        $output = [];

        foreach ($input as $key => $value) {
            $output[] = $callback($key, $value);
        }

        return $output;
    }
}

if (! function_exists('array_flatten')) {
    /**
     * @param  array  $array
     * @return array
     */
    function array_flatten($array): array
    {
        $return = [];

        array_walk_recursive($array, function ($x) use (&$return) {
            $return[] = $x;
        });

        return $return;
    }
}
