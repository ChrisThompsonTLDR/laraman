<form action="{{ route($location . '.destroy', $row->id) }}" method="POST" style="display: inline;">
    {{ method_field('DELETE') }}
    {{ csrf_field() }}
    <button type="submit" class="@if($class){{ $class }}@else{{ 'btn btn-xs btn-danger' }}@endif" value="delete">Delete</button>
</form>