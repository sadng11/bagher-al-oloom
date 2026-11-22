@extends('site.app')
@if ($type==\App\Models\SiteContent::ABOUT_US)
    @section('title','درباره ما')
@elseif($type==\App\Models\SiteContent::ADVICE)
    @section('title','توصیه مهم از بزرگان دین')
@endif

@section('content')
    <section id="main-banner-page"
             class="position-relative page-header service-detail-header section-nav-smooth parallax">
        <div class="overlay overlay-dark opacity-7 z-index-1"></div>
        <div class="container">
            <div class="row shift-tole">
                <div class="col-lg-8 offset-lg-2">
                    <div class="page-titles whitecolor text-center padding_top padding_bottom">
                    </div>
                </div>
            </div>
            <div class="gradient-bg title-wrap">
                <div class="row">
                    <div class="col-lg-12 col-md-12 whitecolor">
                        @if ($type==\App\Models\SiteContent::ABOUT_US)
                            <h3 class="float-end">درباره ما</h3>
                            @include('site.partials.breadcrumb', ['crumb_data'=>['درباره ما']])
                        @elseif($type==\App\Models\SiteContent::ADVICE)
                            <h3 class="float-end">توصیه مهم از بزرگان دین</h3>
                            @include('site.partials.breadcrumb', ['crumb_data'=>['توصیه مهم از بزرگان دین']])
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="our-services" class="padding bglight">
        <div class="container">
            <div class="row whitebox top15">
                @foreach($data as $item)
                    <div class="col-md-12">
                        <div class="widget heading_space text-center text-md-start">
                            {!! $item->content !!}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
