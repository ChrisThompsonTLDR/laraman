@if (session('success')
     || session('danger')
     || session('error')
     || (isset($errors) && count($errors) > 0))
<div id="alerts" class="container">
    <div class="row">
        <div class="col col-xs-12">
        @if (session('success'))
            <div class="alert alert-success">
                {!! session('success') !!}
            </div>
        @endif
        @if (session('danger'))
            <div class="alert alert-danger">
                {!! session('danger') !!}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">
                {!! session('error') !!}
            </div>
        @endif
        @if (isset($errors) && count($errors) > 0)
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        </div>
    </div>
</div>
@endif