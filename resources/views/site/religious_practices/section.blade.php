@extends('site.app')
@section('title', 'اعمال ' . $section->title)
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
                        <h3 class="mb-0">اعمال {{ $section->title }}</h3>
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
                    <h2 class="rp-page-heading">اعمال {{ $section->title }}</h2>
                    <a href="{{ route('religious_practices.index') }}" class="rp-nav-btn">
                        <i class="fa fa-arrow-right" aria-hidden="true"></i>
                        بازگشت به همه بخش‌ها
                    </a>
                </div>

                @if($weekdays->isNotEmpty())
                    <nav class="rp-weekday-pills" aria-label="انتخاب روز هفته">
                        @foreach($weekdays as $weekday)
                            <a href="{{ route('religious_practices.section', ['section' => $weekday->slug]) }}"
                               class="rp-pill-link {{ $weekday->id === $section->id ? 'is-active' : '' }}">
                                {{ $weekday->title }}
                            </a>
                        @endforeach
                    </nav>
                @endif

                @if(filled($section->description))
                    <p class="rp-intro">{{ $section->description }}</p>
                @endif

                @include('site.religious_practices._practices_list', [
                    'practices' => $practices,
                    'emptyMessage' => 'در حال حاضر عملی برای انجام در این بخش ثبت نشده است.',
                ])
            </div>
        </div>
    </section>

    @include('site.religious_practices._styles')
@endsection
