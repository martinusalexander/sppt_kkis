<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title') - {{ config('app.name')}}</title>

        <!-- CSS -->
        <link rel="stylesheet" href="{{ URL::asset('css/bootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ URL::asset('css/bootstrap-datetimepicker.min.css') }}">
        <link rel="stylesheet" href="{{ URL::asset('css/bootstrap-tagsinput.css') }}">
        <!-- Optional CSS -->
        <link rel="stylesheet" href="{{ URL::asset('css/bootstrap-theme.min.css') }}">
        <!-- CSS for all pages -->
        <style>
            .btn {
                white-space: normal;
            }
            pre {
                white-space: pre-wrap;       /* css-3 */
                white-space: -moz-pre-wrap;  /* Mozilla, since 1999 */
                white-space: -pre-wrap;      /* Opera 4-6 */
                white-space: -o-pre-wrap;    /* Opera 7 */
                word-wrap: break-word;       /* Internet Explorer 5.5+ */
            }

            #title-div {
                margin: auto;
            }

            #simple-title-a {
                color: #000;
                text-align: center;
                font-weight: bold;
                display: block;
            }

            #complete-title-table {
                margin: 0 auto;
            }

            #complete-title-table td {
                padding: 10px;
            }

            .logo {
                max-height: 125px;
                display: block;
            }

            #complete-title-h3 {
                font-weight: bold;
            }

            .bootstrap-tagsinput {
                width: 100% !important;
            }

        </style>
        @yield('extra_css')

        <!-- JS -->
        <script type="text/javascript" src="{{ URL::asset('js/jquery-3.2.1.min.js') }}"></script>
        <script type="text/javascript" src="{{ URL::asset('js/bootstrap.min.js') }}"></script>
        <script type="text/javascript" src="{{ URL::asset('js/ckeditor.js') }}"></script>
        <script type="text/javascript" src="{{ URL::asset('js/moment.js') }}"></script>
        <script type="text/javascript" src="{{ URL::asset('js/bootstrap-datetimepicker.min.js') }}"></script>
        <script type="text/javascript" src="{{ URL::asset('js/bootstrap-tagsinput.min.js') }}"></script>
        <!-- JS for all pages -->
        <script>
            // Make sure all images are inside their respective parent
            $(document).ready(function() {
               $("img").addClass("img-responsive");
            });
        </script>
        @yield('extra_js')
    </head>
    <body>
        @include('layout.title')
        @if(!isset($hide_menu))
            @include('public.sidebarmenu')
        @endif
        <div id="main" class="container">
            @yield('content')
        </div>
    </body>
</html>
