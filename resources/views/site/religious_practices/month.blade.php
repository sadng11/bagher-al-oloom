@extends('site.app')
@section('title', 'اعمال ماه ' . $section->title)
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
                    <div class="col-lg-12 col-md-12 whitecolor d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h3 class="mb-0">اعمال ماه {{ $section->title }}</h3>
                        @include('site.partials.breadcrumb', [
                            'crumb_data' => [
                                route('religious_practices.index') => 'اعمال شرعی',
                                $section->title,
                            ],
                        ])
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="our-services" class="padding bglight">
        <div class="container">
            <div class="rp-main-box mt-3">
                <div class="rp-toolbar">
                    <h2 class="rp-page-heading">انتخاب روز ماه {{ $section->title }}</h2>
                    <a href="{{ route('religious_practices.index') }}" class="rp-nav-btn">
                        <i class="fa fa-arrow-right" aria-hidden="true"></i>
                        بازگشت به همه بخش‌ها
                    </a>
                </div>

                @if(filled($section->description))
                    <p class="rp-intro">{{ $section->description }}</p>
                @endif

                <div class="rp-days-grid">
                    @foreach($days as $day)
                        <a href="{{ route('religious_practices.day', ['section' => $section->slug, 'day' => $day]) }}"
                           class="rp-day-btn">
                            <span class="rp-day-number">{{ $day }}</span>
                            <span class="rp-day-label">روز {{ $day }}</span>
                        </a>
                    @endforeach
                </div>

                @if($generalPractices->isNotEmpty())
                    <div class="rp-group mt-4">
                        <div class="rp-group-header">
                            <h4 class="rp-group-title">اعمال کلی ماه {{ $section->title }}</h4>
                        </div>
                        @include('site.religious_practices._practices_list', [
                            'practices' => $generalPractices,
                        ])
                    </div>
                @endif
            </div>
        </div>
    </section>

    @include('site.religious_practices._styles')
@endsection
