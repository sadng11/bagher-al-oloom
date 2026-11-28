<!-- header -->

<header class="site-header" id="header">
    <nav class="navbar navbar-expand-lg transparent-bg bg-transparent darkcolor static-nav mt-sm-4 mt-2">
        <div class="container bg-white border-radius-50 position-relative">
            <a class="navbar-brand" href="{{URL::to('/')}}">
                <img src="{{ asset('site/images/logo.png')}}" alt="logo" class="logo-default">
                <img src="{{ asset('site/images/logo.png')}}" alt="logo" class="logo-scrolled">
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{URL::to('/')}}">صفحه اصلی</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('search')}}">جستجو</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('content',['type'=>'advices'])}}">توصیه های مهم از بزرگان
                            دین</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('religious_practices.index')}}">اعمال شرعی</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('library',['sort'=>'-id'])}}">کتابخانه</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('scholar_works.index')}}">آثار علما و محققین</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('sound_category',['sort'=>'-id'])}}">مدرس مهدیه باقر
                            العلوم</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('images.index')}}">تصاویر</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('content',['type'=>'about_us'])}}">درباره ما</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('contact')}}">تماس با ما</a>
                    </li>
                </ul>
            </div>
            <!--side menu open button-->

            <a href="javascript:void(0)" class="d-inline-block d-lg-none sidemenu_btn mr-0" id="sidemenu_toggle">
                منوی اصلی
{{--                <span class="bg-dark"></span> <span class="bg-dark"></span> <span class="bg-dark"></span>--}}
            </a>
        </div>
    </nav>
    <!-- side menu -->
    <div class="side-menu opacity-0 gradient-bg">
        <div class="overlay"></div>
        <div class="inner-wrapper">
            <span class="btn-close btn-close-no-padding" id="btn_sideNavClose"><i></i><i></i></span>
            <nav class="side-nav w-100">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="{{URL::to('/')}}">صفحه اصلی</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('search')}}">جستجو</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('content',['type'=>'about_us'])}}">درباره ما</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('content',['type'=>'advices'])}}">توصیه های مهم از بزرگان
                            دین</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('religious_practices.index')}}">اعمال شرعی</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('library',['sort'=>'-id'])}}">کتابخانه</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('scholar_works.index')}}">آثار علما و محققین</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('sound_category',['sort'=>'-id'])}}">مدرس مهدیه باقر
                            العلوم</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('images.index')}}">تصاویر</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('contact')}}">تماس با ما</a>
                    </li>
                </ul>
            </nav>
            <div class="side-footer w-100">
                @include('site.partials.footer')
            </div>
        </div>
    </div>
    <div id="close_side_menu" class="tooltip"></div>
    <!-- End side menu -->
</header>


<!-- header -->

@push('script')
    <script>
        function active_menu() {
            var url = window.location.href;
            $('.nav-item a').filter(function () {
                const s = this.href === url;
            }).addClass('active');
        }

        active_menu();

    </script>
@endpush
