<div class="form-group filter">
    <div id="filter{{ title_case($filter->field) }}">
        {!! Form::label('filter_' . $filter->field . '_start', $filter->display) !!}
        {!! Form::text('filter_' . $filter->field . '_start', isset($params['filter_' . $filter->field . '_start']) ? $params['filter_' . $filter->field . '_start'] : null, ['class' => 'form-control']) !!}
        to
        {!! Form::text('filter_' . $filter->field . '_end', isset($params['filter_' . $filter->field . '_end']) ? $params['filter_' . $filter->field . '_end'] : null, ['class' => 'form-control']) !!}
    </div>
</div>