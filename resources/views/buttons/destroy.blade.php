<button type="button" class="btn btn-xs btn-danger" data-toggle="modal" data-target="#deleteModal{{ $row->id }}">
    Delete
</button>

<div class="modal fade" id="deleteModal{{ $row->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteModal{{ $row->id }}Label">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="deleteModal{{ $row->id }}Label">Delete {{ $row->id }}</h4>
            </div>
            <div class="modal-body">
                Are you sure you want to delete row with id {{ $row->id }}?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <form action="{{ route($location . '.destroy', $row->id) }}" method="POST" style="display: inline;">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button type="submit" class="@if($class){{ $class }}@else{{ 'btn btn-danger' }}@endif" value="delete">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>