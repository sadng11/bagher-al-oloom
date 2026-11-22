@extends('site.app')
@section('title',$data->title)
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
                        <h3 class="float-end">{{$data->title}}</h3>
                        @include('site.partials.breadcrumb', ['crumb_data'=>[route('sound_category')=>'مدرس مهدیه باقر العلوم',route('sound_category.detail',['id'=>$data->category->id])=>$data->category->title,$data->title]])
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="our-services" class="padding bglight">
        <div class="container">
            <div class="row whitebox top10">
                <div class="col-lg-4 col-md-5">
                    <div class="image widget bottom20">
                        <h5>جهت دانلود صوت مورد نظر خود بر روی لینک زیر کلیک نمایید:</h5>
                        <div class="mt-5">
                            <a href="{{$data->link}}" class="btn btn-block btn-outline-info btn-sound-download">
                                <i class="fa fa-download"></i>
                                دانلود
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 col-md-7">
                    <div class="widget heading_space text-center text-md-start">
                        <div>
                            {!! $data->description !!}
                        </div>
                        <p class="text-start">تاریخ انتشار‌ : {{$data->human_time}}</p>
                        <hr>
                        <p>
                            دسته بندی : <a href="{{route('sound_category.detail',['id'=>$data->category->id])}}"
                                           class="button-readmore">{{ $data->category->title }}</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
