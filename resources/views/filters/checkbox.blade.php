<div class="form-group filter">
    <div id="filter{{ title_case($filter->field) }}" class="checkbox">
        <label>
            {!! Form::checkbox('filter_' . $filter->field, isset($filter->value) ? $filter->value : true, isset($params['filter_' . $filter->field]) ? true : false) !!} {{ $filter->display }}
        </label>
    </div>
</div>