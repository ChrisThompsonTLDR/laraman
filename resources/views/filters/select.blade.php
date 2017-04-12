<div class="form-group filter">
    <div id="filter{{ title_case($filter->field) }}">
        {!! Form::label('filter[' . $filter->field . ']', $filter->display) !!}
        {!! Form::select('filter[' . $filter->field . ']', $filter->options['values'], isset($params['filter'][$filter->field]) ? $params['filter'][$filter->field] : null, ['class' => 'form-control', 'placeholder' => '-']) !!}
    </div>
</div>