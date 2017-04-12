<div class="form-group filter">
    <div id="filter{{ title_case($filter->field) }}">
        {!! Form::label('filter[' . $filter->field . '-start]', $filter->display) !!}
        {!! Form::text('filter[' . $filter->field . '-start]', isset($params['filter'][$filter->field . '-start']) ? $params['filter'][$filter->field . '-start'] : null, ['class' => 'form-control']) !!}
        to
        {!! Form::text('filter[' . $filter->field . '-end]', isset($params['filter'][$filter->field . '-end']) ? $params['filter'][$filter->field . '-end'] : null, ['class' => 'form-control']) !!}
    </div>
</div>