<div class="form-group filter">
    <div id="filter{{ title_case($filter->field) }}">
        @if(!isset($filter->label) || $filter->label === true)<div><strong>{!! $filter->display !!}</strong></div>@endif
        {{ Form::text('filter[' . $filter->field . ']', isset($params['filter'][$filter->field]) ? $params['filter'][$filter->field] : null, ['class' => 'form-control']) }}
    </div>
</div>