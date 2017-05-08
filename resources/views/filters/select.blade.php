<div class="form-group filter">
    <div id="filter{{ title_case($filter->field) }}">
        @if(!isset($filter->label) || $filter->label === true)<div><strong>{!! $filter->display !!}</strong></div>@endif
        {!! Form::select('filter[' . $filter->field . ']', $filter->values, isset($params['filter'][$filter->field]) ? $params['filter'][$filter->field] : null, ['class' => 'form-control', 'placeholder' => isset($filter->placeholder) ? $filter->placeholder : '']) !!}
    </div>
</div>