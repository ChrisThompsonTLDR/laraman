<div class="form-group filter">
    <div id="filter{{ Str::of($filter->field)->title() }}">
        @if(!isset($filter->label) || $filter->label === true)<div><strong>{!! $filter->display !!}</strong></div>@endif
        {!! Form::text('filter[' . $filter->field . '-start]', isset($params['filter'][$filter->field . '-start']) ? $params['filter'][$filter->field . '-start'] : null, ['class' => 'form-control']) !!}
        to
        {!! Form::text('filter[' . $filter->field . '-end]', isset($params['filter'][$filter->field . '-end']) ? $params['filter'][$filter->field . '-end'] : null, ['class' => 'form-control']) !!}
    </div>
</div>
