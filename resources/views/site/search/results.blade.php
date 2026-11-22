@extends('site.app')

@push('style')
<style>
    .page-search {
        margin: 7px;
    }
</style>
@endpush


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
                    <h3 class="float-end">جستجو</h3>
                    @include('site.partials.breadcrumb', ['crumb_data'=>['جستجو']])
                </div>
            </div>
        </div>
    </div>
</section>

<section id="search" class="padding bglight">
    <div class="container whitebox">
        <div class="widget py-5">
            <div class="row top15">
                <div class="col-md-12 wow fadeIn mt-n3">
                    {{ Form::open(['route'=>'search','method'=>'get','class'=>'getin_form wow fadeInUp','data-wow-delay'=>'400ms']) }}
                    <div class="row px-2">
                        <div class="col-md-12 col-sm-12" id="result1"></div>
                        <div class="col-md-12 col-sm-12">
                            <div class="form-group">
                                <label for="name1" class="d-none"></label>
                                {{ Form::text('q',null, ['class'=>'form-control','id'=>'name1','placeholder'=>'عبارت خود را وارد نمایید','required']) }}
                            </div>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <button type="submit" id="submit_btn1" class="button gradient-btn w-100">جستجو
                            </button>
                        </div>
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
    @if ($search)
    <div class="container whitebox" style="margin-top: 2rem;">
        <div class="widget py-5">

            <div class="row top15">
                <div class="col-md-12 text-center wow fadeIn mt-n3 top15" data-wow-delay="300ms">
                    <h5 class="heading bottom30 darkcolor font-light2 pt-1">
                        <span class="font-normal">
                            نتایج جستجو "{{$search}}"
                        </span>
                        <span class="divider-center"></span>
                    </h5>
                    @if (session('message'))
                    <div class="alert alert-success">{{ session('message') }}</div>
                    @endif
                </div>
                <div class="col-md-12 col-sm-12 order-sm-2">
                    <div class="contact-meta px-2 text-center text-md-start">
                        @foreach ($results['hits'] as $hit)
                        <div class="result-item position-relative mb-5 p-4 border rounded">
                            <a class="stretched-link"
                               aria-label="مشاهده نتیجه در کتاب"
                               href="{{ route('library.book', [
                                   'id' => $hit['book_id'],
                                   'p' => $hit['id'],
                                   'q' => $hit['_matchedQuery'] ?? $search,
                               ]) }}"></a>
                            <h3>{!! $hit['_formatted']['book_title'] ?? $hit['book_title'] !!}</h3>
                            <p>{!! $hit['_formatted']['content'] ?? $hit['content'] !!}</p>
                        </div>
                        <hr>
                        @endforeach

                        @if(isset($results['nbHits']))
                        <div class="pagination mt-6">
                            @php
                            $totalPages = ceil($results['nbHits'] / $perPage);
                            @endphp

                            @if($page > 1)
                            <a href="{{ route('search', ['q' => $search, 'page' => $page-1]) }}"
                                class="btn btn-info page-search">قبلی</a>
                            @endif

                            @for($i = max(1, $page-3); $i <= min($totalPages, $page+3); $i++)
                                <a href="{{ route('search', ['q' => $search, 'page' => $i]) }}"
                                class="{{ $i == $page ? 'font-bold text-blue-600 btn-secondary' : 'btn-info' }} btn  page-search">
                                {{ $i }}
                                </a>
                                @endfor

                                @if($page < $totalPages)
                                    <a href="{{ route('search', ['q' => $search, 'page' => $page+1]) }}"
                                    class="btn btn-info page-search">بعدی</a>
                                    @endif
                        </div>

                        <p>نمایش {{ ($page-1)*$perPage + 1 }}
                            تا {{ min($page*$perPage, $results['nbHits']) }}
                            از {{ $results['nbHits'] }} نتیجه</p>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
    @endif
</section>
@endsection
