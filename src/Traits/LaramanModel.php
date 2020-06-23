<?php

namespace Christhompsontldr\Laraman\Traits;

use Carbon\Carbon;
use DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Schema;

trait LaramanModel
{
    public static function formatterBoolean($params)
    {
        $value   = isset($params['value']) ? $params['value'] : null;
        $column  = isset($params['column']) ? $params['column'] : null;
        $row     = isset($params['row']) ? $params['row'] : null;
        $options = isset($params['options']) ? $params['options'] : null;

        if (isset($options['values'])) {
            if (isset($options['values'][$value])) {
                $value = $options['values'][$value];
            }
        }

        return e($value);
    }

    /**
    * Runs collection count on a related model
    *
    * @param mixed $params
    */
    public static function formatterCount($params)
    {
        return $params['value'];
    }

    public static function formatterDatetime($params)
    {
        $value   = isset($params['value']) ? $params['value'] : null;
        $column  = isset($params['column']) ? $params['column'] : null;
        $row     = isset($params['row']) ? $params['row'] : null;
        $options = isset($params['options']) ? $params['options'] : null;

        if (empty($value->timestamp) || $value->timestamp < 1) {
            return isset($options['empty']) ? $options['empty'] : '';
        }

        //  no options set
        if (empty($options)) { return $value; }

        if (!empty($options['format'])) {
            $value = Carbon::parse($value)->format($options['format']);
        }

        return e($value);
    }

    public static function formatterMoney($params)
    {
        $value   = isset($params['value']) ? $params['value'] : null;
        $column  = isset($params['column']) ? $params['column'] : null;
        $row     = isset($params['row']) ? $params['row'] : null;
        $options = isset($params['options']) ? $params['options'] : null;

        if (!empty($row->{$column['field']})) {
            return '$' . number_format($row->{$column['field']}, 2);
        }
    }

    public static function formatterRelated($params)
    {
        $value   = isset($params['value']) ? $params['value'] : null;
        $column  = isset($params['column']) ? $params['column'] : null;
        $row     = isset($params['row']) ? $params['row'] : null;
        $options = isset($params['options']) ? $params['options'] : null;

        list($model, $parts) = explode('.', $column['field']);

        return '<a href="' . route(config('laraman.route.prefixDot') . Str::plural($model) . '.show', Arr::get($row, $model . '.id')) . '">' . e($value) . '</a>';
    }
}
