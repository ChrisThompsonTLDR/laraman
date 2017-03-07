@extends('laraman::layout')

@push('after-styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.0/bootstrap-table.min.css">
@endpush

@push('after-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.0/bootstrap-table.min.js"></script>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col col-xs-4">
            @if ($prev)
            <a href="{{ route($location . '.show', $prev->id) }}" class="btn btn-info">{{ $prev->id }}</a>
            @endif
        </div>
        <div class="col col-xs-4 text-center">
            <a href="{{ route($location . '.index') }}" class="btn btn-info">Index</a>
        </div>
        <div class="col col-xs-4">
            @if ($next)
            <div class="pull-right">
                <a href="{{ route($location . '.show', $next->id) }}" class="btn btn-info">{{ $next->id }}</a>
            </div>
            @endif
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col col-xs-12">
            <b>ID:</b> {{ $row->id }}<br />
            <b>Title:</b> {{ $row->title }}<br />
            <b>Content:</b> {!! $row->content !!}<br />
        </div>
    </div>
    <hr />
    @foreach ($related as $relation => $set)
        @push('after-scripts')
        <script>
        $(function() {
            $('#table-{{ $relation }}').bootstrapTable({!! $set['json'] !!});
        });
        </script>
        @endpush
    <div class="row">
        <div class="col col-xs-12">
            <h3 class="pull-left">{{ $set['display'] }}</h3>
            <table id="table-{{ $relation }}" {!! implode(' ', $set['bootstrap-table']) !!}>
                <?php /*<thead>
                @foreach ($set['fields'] as $column)
                    <th>{{ $column->display }}</th>
                @endforeach
                </thead>
                <tbody>
                @foreach ($set['rows'] as $row)
                    <tr>
                        @foreach ($set['fields'] as $column)
                        <td>{{ $row->{$column->field} }}</td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>*/ ?>
            </table>
        </div>
    </div>
    @endforeach
</div>
@endsection