<div class="form-group filter">
    <div id="filter{{ title_case($filter->field) }}">
        @if(!isset($filter->label) || $filter->label === true)<div><strong>{!! $filter->display !!}</strong></div>@endif
        {!! Form::text('filter_' . $filter->field . '_start', isset($params['filter_' . $filter->field . '_start']) ? $params['filter_' . $filter->field . '_start'] : null, ['class' => 'form-control']) !!}
        to
        {!! Form::text('filter_' . $filter->field . '_end', isset($params['filter_' . $filter->field . '_end']) ? $params['filter_' . $filter->field . '_end'] : null, ['class' => 'form-control']) !!}
    </div>
</div>