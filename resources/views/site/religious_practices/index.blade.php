@extends('site.app')
@section('title', 'اعمال شرعی')
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
                        <h3 class="mb-0">اعمال شرعی</h3>
                        @include('site.partials.breadcrumb', ['crumb_data' => ['اعمال شرعی']])
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="our-services" class="padding bglight">
        <div class="container">
            <div class="rp-main-box mt-3">
                <p class="rp-intro">
                    برای مشاهده اعمال، ماه قمری، روز هفته یا بخش مورد نظر خود را انتخاب کنید.
                </p>

                <div class="rp-group">
                    <div class="rp-group-header">
                        <h4 class="rp-group-title">ماه‌های قمری</h4>
                        <span class="rp-group-hint">۱۲ ماه (از رمضان تا شعبان)</span>
                    </div>
                    <div class="row g-3">
                        @foreach($months as $month)
                            <div class="col-6 col-sm-4 col-md-3">
                                <a href="{{ route('religious_practices.section', ['section' => $month->slug]) }}"
                                   class="rp-section-link">
                                    <div>
                                        <div class="rp-section-name">{{ $month->title }}</div>
                                        <div class="rp-section-meta">۳۰ روز</div>
                                    </div>
                                    <i class="fa fa-angle-left rp-section-arrow" aria-hidden="true"></i>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rp-group">
                    <div class="rp-group-header">
                        <h4 class="rp-group-title">ایام هفته</h4>
                        <span class="rp-group-hint">شنبه تا جمعه</span>
                    </div>
                    <div class="row g-3">
                        @foreach($weekdays as $weekday)
                            <div class="col-6 col-sm-4 col-md-3">
                                <a href="{{ route('religious_practices.section', ['section' => $weekday->slug]) }}"
                                   class="rp-section-link">
                                    <span class="rp-section-name">{{ $weekday->title }}</span>
                                    <i class="fa fa-angle-left rp-section-arrow" aria-hidden="true"></i>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rp-group">
                    <div class="rp-group-header">
                        <h4 class="rp-group-title">بخش‌های ویژه</h4>
                    </div>
                    <div class="row g-3">
                        @if($commonSection)
                            <div class="col-md-6">
                                <a href="{{ route('religious_practices.section', ['section' => $commonSection->slug]) }}"
                                   class="rp-section-link rp-special-link">
                                    <div>
                                        <div class="rp-section-name">{{ $commonSection->title }}</div>
                                        <div class="rp-section-meta">اعمال مشترک و عمومی</div>
                                    </div>
                                    <i class="fa fa-angle-left rp-section-arrow" aria-hidden="true"></i>
                                </a>
                            </div>
                        @endif

                        @if($nowruzSection)
                            <div class="col-md-6">
                                <a href="{{ route('religious_practices.section', ['section' => $nowruzSection->slug]) }}"
                                   class="rp-section-link rp-special-link">
                                    <div>
                                        <div class="rp-section-name">{{ $nowruzSection->title }}</div>
                                        <div class="rp-section-meta">اعمال مخصوص عید نوروز</div>
                                    </div>
                                    <i class="fa fa-angle-left rp-section-arrow" aria-hidden="true"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('site.religious_practices._styles')
@endsection
