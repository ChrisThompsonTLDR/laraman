<?php

namespace Christhompsontldr\Laraman\Traits;

use Carbon\Carbon;
use Schema;
use DB;
use Illuminate\Database\Query\Expression;

trait LaramanModel
{

    //  https://laracasts.com/discuss/channels/eloquent/eloquent-order-by-related-table
    public function scopeModelJoin($query, $relation_name, $operator = '=', $type = 'left', $where = false)
    {
        $relation = $this->$relation_name();
        $table = $relation->getRelated()->getTable();
        $one = $relation->getQualifiedParentKeyName();
        $two = $relation->getForeignKey();

        if (empty($query->columns)) {
            $query->select($this->getTable().".*");
        }

        foreach (Schema::getColumnListing($table) as $related_column) {
            $query->addSelect(new Expression("`$table`.`$related_column` AS `" . str_singular($table) . ".$related_column`"));
        }

        return $query->join($table, $table . '.' . $relation->getOwnerKey(), $operator, $this->getTable() . '.' . $two, $type, $where);
    }

    public static function formatterBoolean ($params)
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
        $value   = isset($params['value']) ? $params['value'] : null;
        $column  = isset($params['column']) ? $params['column'] : null;
        $row     = isset($params['row']) ? $params['row'] : null;
        $options = isset($params['options']) ? $params['options'] : null;

        return $row->{$column['field']}->count();
    }

    public static function formatterDatetime ($params)
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

    public static function formatterRelated ($params)
    {
        $value   = isset($params['value']) ? $params['value'] : null;
        $column  = isset($params['column']) ? $params['column'] : null;
        $row     = isset($params['row']) ? $params['row'] : null;
        $options = isset($params['options']) ? $params['options'] : null;

        list($model, $parts) = explode('.', $column['field']);

        return '<a href="' . route(config('laraman.route.prefixDot') . str_plural($model) . '.show', array_get($row, $model . '.id')) . '">' . e($value) . '</a>';
    }
}