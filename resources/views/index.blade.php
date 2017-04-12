@extends('laraman::layout')

@section('content')
<div class="container-fluid">
    <div id="toolbar" class="row">
        <div class="col col-xs-12">
            @if ($searchEnabled)
            <div class="pull-right">
                {!! Form::open(['route' => $location . '.search', 'class' => 'form-inline']) !!}
                    {!! Form::hidden('sort', $params['sort'], ['id' => 'sort']) !!}
                    {!! Form::hidden('order', $params['order'], ['id' => 'order']) !!}
                    {!! Form::hidden('page', $params['page'], ['id' => 'page']) !!}
                    {!! Form::hidden('limit', $params['limit'], ['id' => 'limit']) !!}

                    @foreach ($filters as $filter)
                        {!! Form::hidden('filter_' . $filter->field, isset($params['filter_' . $filter->field]) ? $params['filter_' . $filter->field] : null) !!}
                    @endforeach
                    <div class="form-group">
                        {!! Form::text('search', $search, ['class' => 'form-control', 'placeholder' => 'search...']) !!}
                    </div>
                    <button type="submit" class="btn btn-info">Search</button>
                {!! Form::close() !!}
            </div>
            @endif

            @if (!$filters->isEmpty())
            <div id="filters">
            {!! Form::open(['route' => $location . '.filter', 'class' => 'form-inline']) !!}
                {!! Form::hidden('sort', $params['sort'], ['id' => 'sort']) !!}
                {!! Form::hidden('order', $params['order'], ['id' => 'order']) !!}
                {!! Form::hidden('page', $params['page'], ['id' => 'page']) !!}
                {!! Form::hidden('limit', $params['limit'], ['id' => 'limit']) !!}
                @if (!empty($params['search']))
                {!! Form::hidden('search', $params['search']) !!}
                @endif

                @foreach ($filters as $filter)
                    @include('laraman::filters.' . $filter->type, compact('filter', 'params'))
                @endforeach
                <button type="submit" class="btn btn-info">Filter</button>
                <a href="{{ route($location . '.index') }}" class="btn btn-danger">Clear</a>
            {!! Form::close() !!}
            </div>
            @endif
        </div>
    </div>
    <div class="row">
        <div class="col col-xs-12">
            @if (!$rows->isEmpty())
            <table class="table table-bordered">
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                        <th nowrap="nowrap">
                            <?php
                                $pager = $params;

                                if ($pager['sort'] == $column->field) {
                                    $pager['order'] = (($pager['order'] == 'desc') ? 'asc' : 'desc');
                                } else {
                                    $pager['order'] = 'asc';
                                }

                                $pager['sort'] = $column->field;

                                $url = route($location . '.index') . '?' . http_build_query($pager);
                            ?>
                            @if (!isset($column->sortable) || $column->sortable)<a href="{{ $url }}">@endif
                                {{ $column->display }}

                                @if ($params['sort'] == $column->field)
                                <i class="fa fa-chevron-{{ (($params['order'] == 'desc') ? 'down' : 'up') }}" aria-hidden="true"></i>
                                @endif
                            @if (!isset($column->sortable) || $column->sortable)</a>@endif
                        </th>
                        @endforeach
                        @if ($buttons->count() > 0)
                        <th data-field="actions">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            @foreach ($columns as $column)
                            <td>{!! $row->{$column->field} or '' !!}</td>
                            @endforeach
                            @if ($buttons->count() > 0)
                            <td>{!! $row->actions or '' !!}</th>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="row">
                <div class="col col-xs-2">
                    <?php /*{!! Form::open(['method' => 'get', 'route' => 'manage.news.index']) !!}
                        @foreach ($params as $key => $val)
                            @if ($key == 'limit') @continue @endif
                            {!! Form::hidden($key, $val) !!}
                        @endforeach
                        {!! Form::select('limit', $limits, $params['limit'], ['class' => 'form-control', 'onchange' => 'this.form.submit()']) !!}
                    {!! Form::close() !!} */ ?>
                </div>
                <div class="col col-xs-10">
                    <div class="pull-right text-center">
                        {!! $paginator->appends($params)->links() !!}
                        <div>
                            {{ number_format($paginator->firstItem(), 0) }} to {{ number_format($paginator->lastItem(), 0) }} of {{ number_format($paginator->total(), 0) }}
                        </div>
                    </div>
                </div>
            </div>
            @else
            <p>No results!</p>
            @endif
        </div>
    </div>
</div>
@endsection