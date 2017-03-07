<div class="form-group filter">
    <div id="filter{{ title_case($filter->field) }}">
        {!! Form::label('filter_' . $filter->field, $filter->display) !!}
        {!! Form::select('filter_' . $filter->field, $filter->options['values'], isset($params['filter_' . $filter->field]) ? $params['filter_' . $filter->field] : null, ['class' => 'form-control', 'placeholder' => '-']) !!}
    </div>
</div>