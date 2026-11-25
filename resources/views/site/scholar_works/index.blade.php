@extends('site.app')
@section('title', 'آثار علما و محققین')
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
                        <h3 class="float-end">آثار علما و محققین</h3>
                        @include('site.partials.breadcrumb', ['crumb_data' => ['آثار علما و محققین']])
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="our-services" class="padding bglight">
        <div class="container">
            <div class="row whitebox top10">
                <div class="col-md-12 mt-3">
                    <div class="card">
                        <div class="card-body">
                            <form id="scholar-works-filter"
                                  method="GET"
                                  action="{{ route('scholar_works.index') }}"
                                  class="row g-3 align-items-end">
                                <div class="col-md-6">
                                    <label for="scholar-search" class="form-label">جستجو</label>
                                    <input type="text"
                                           name="q"
                                           id="scholar-search"
                                           class="form-control"
                                           value="{{ $search }}"
                                           placeholder="جستجو بر اساس عنوان اثر یا نام صاحب اثر">
                                </div>
                                <div class="col-md-4">
                                    <label for="sort" class="form-label">ترتیب</label>
                                    <select name="sort" id="sort" class="form-select form-control">
                                        <option value="-id" @selected($sort === '-id')>جدیدترین</option>
                                        <option value="+id" @selected($sort === '+id')>قدیمی‌ترین</option>
                                        <option value="+title" @selected($sort === '+title')>عنوان صعودی</option>
                                        <option value="-title" @selected($sort === '-title')>عنوان نزولی</option>
                                        <option value="+author_name" @selected($sort === '+author_name')>نام صاحب اثر صعودی</option>
                                        <option value="-author_name" @selected($sort === '-author_name')>نام صاحب اثر نزولی</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="button gradient-btn w-100">جستجو</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                @forelse($data as $item)
                    <div class="col-md-12 mt-3">
                        <a href="{{ route('library.book', ['id' => $item->id]) }}">
                            <div class="card">
                                <div class="card-body">
                                    <div>{{ $item->title }}</div>
                                    <div class="text-muted small mt-1">
                                        صاحب اثر: {{ $item->display_author !== '' ? $item->display_author : 'نامشخص' }}
                                    </div>
                                </div>
                                <div class="card-footer text-muted d-flex justify-content-between align-items-center small">
                                    <span><i class="fa fa-eye me-1"></i> بازدید: {{ number_format((int) $item->views) }}</span>
                                    <span><i class="fa fa-download me-1"></i> دانلود: {{ number_format((int) $item->downloads) }}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @empty
                    @include('site.partials.empty_result')
                @endforelse
            </div>

            <div class="row mt-4">
                {{ $data->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </section>

    @push('script')
        <script>
            $(document).ready(function () {
                $('#sort').on('change', function () {
                    $('#scholar-works-filter').submit();
                });
            });
        </script>
    @endpush
@endsection
