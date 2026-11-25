@extends('site.app')
@php
    $book = $data['book'];
    $author = trim((string) $book->display_author);
    $pageTitle = 'کتاب ' . $book->title . ($author !== '' ? ' اثر ' . $author : '');

    if ($book->isScholarWork()) {
        $crumbData = [
            route('scholar_works.index') => 'آثار علما و محققین',
            $author !== '' ? $author : $book->title,
        ];
    } elseif ($book->writer) {
        $crumbData = [
            route('library') => 'کتابخانه',
            route('library.writer', ['id' => $book->writer->id]) => ' کتب ' . $book->writer->full_name,
        ];
    } else {
        $crumbData = [
            route('library') => 'کتابخانه',
            $author !== '' ? $author : $book->title,
        ];
    }
@endphp
@section('title', $pageTitle)
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
                        <h3 class="float-end"> {{ $book->title }}</h3>
                        @include('site.partials.breadcrumb', ['crumb_data' => $crumbData])
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="our-services" class="padding bglight">
        <div class="container">
            <div class="row whitebox top15">
                <div class="col-md-12">
                    <div class="widget heading_space text-center text-md-start">
                        <div class="book-content">
                            <book-content :writers="{{$writers}}" :book-id="{{$data['book']->id}}"
                                          :download="{{$data['book']->downloadable}}"
                                          :book-content="{{ Illuminate\Support\Js::from($data['book_content']) }}"
                                          :target-id="{{ Illuminate\Support\Js::from($targetId) }}"
                                          :target-query="{{ Illuminate\Support\Js::from($targetQuery) }}"
                                          :initial-page="{{ $initialPage }}"></book-content>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
