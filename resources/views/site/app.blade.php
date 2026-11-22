<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ config('app.name') }} - @yield('title')</title>
    <meta name="base_url" content="{{URL::to('/')}}">
    <link href="images/favicon.ico" rel="icon">
    @include('site.partials.style')


</head>
<body>
<!--PreLoader-->
<div class="loader">
    <div class="loader-inner">
        <div class="cssload-loader"></div>
    </div>
</div>
<!--PreLoader Ends-->
@include('site.partials.header')
<div id="app">
    @yield('content')
</div>

<div class="copyright">
    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center wow fadeIn animated" data-wow-delay="300ms">
                @include('site.partials.footer')
            </div>
        </div>
    </div>
</div>
@include('site.partials.script')

</body>
</html>
