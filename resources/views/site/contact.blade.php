@extends('site.app')

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
                        <h3 class="float-end">تماس با ما</h3>
                        @include('site.partials.breadcrumb', ['crumb_data'=>['تماس با ما']])
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="our-services" class="padding bglight">
        <div class="container whitebox">
            <div class="widget py-5">
                <div class="row top15">
                    <div class="col-md-12 text-center wow fadeIn mt-n3" data-wow-delay="300ms">
                        <h2 class="heading bottom30 darkcolor font-light2 pt-1"><span class="font-normal">تماس </span>
                            با ما
                            <span class="divider-center"></span>
                        </h2>
                        <div class="col-md-8 offset-md-2-rtl bottom35">
                            <p>در صورت لزوم می توانید از طریق فرم زیر جهت ارتباط با ما استفاده نمایید</p>
                        </div>
                        @if (session('message'))
                            <div class="alert alert-success">{{ session('message') }}</div>
                        @endif
                    </div>
                    <div class="col-md-6 col-sm-6 order-sm-2">
                        <div class="contact-meta px-2 text-center text-md-start">
                            {!! $data[0]->content !!}
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-6">
                        <div class="heading-title  wow fadeInUp" data-wow-delay="300ms">
                            {{ Form::open(['route'=>'contact.post','class'=>'getin_form wow fadeInUp','data-wow-delay'=>'400ms']) }}
                            <div class="row px-2">
                                <div class="col-md-12 col-sm-12" id="result1"></div>
                                <div class="col-md-12 col-sm-12">
                                    <div class="form-group">
                                        <label for="name1" class="d-none"></label>
                                        {{ Form::text('name',null, ['class'=>'form-control','id'=>'name1','placeholder'=>'نام','required']) }}
                                    </div>
                                </div>
                                <div class="col-md-12 col-sm-12">
                                    <div class="form-group">
                                        <label for="email1" class="d-none"></label>
                                        {{ Form::email('email',null, ['class'=>'form-control','id'=>'email1','placeholder'=>'ایمیل','required']) }}
                                    </div>
                                </div>
                                <div class="col-md-12 col-sm-12">
                                    <div class="form-group">
                                        <label for="message1" class="d-none"></label>
                                        {{ Form::textarea('message',null, ['class'=>'form-control','id'=>'message1','placeholder'=>'پیام','required']) }}
                                    </div>
                                </div>
                                <div class="col-md-12 col-sm-12">
                                    <button type="submit" id="submit_btn1" class="button gradient-btn w-100">ارسال
                                    </button>
                                </div>
                            </div>
                            {{ Form::close() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
