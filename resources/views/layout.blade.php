<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laraman</title>

        @stack('before-styles')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <style>
        .pagination {
            margin-top: 0;
        }
        #toolbar {
            margin-bottom: 20px;
        }
        body {
            margin: 80px auto;
        }
        .form-inline .form-group {
            margin-right: 20px;
        }
        </style>
        @stack('after-styles')
    </head>
    <body>
        @include(config('laraman.view.hintpath') . '::blocks.flash')

        @yield('content')

        @stack('before-scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js"></script>
        @stack('after-scripts')
    </body>
</html>
