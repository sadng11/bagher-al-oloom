@extends('site.app')
@section('title',$category->title)
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
                        <h3 class="float-end">{{$category->title}}</h3>
                        @include('site.partials.breadcrumb', ['crumb_data'=>[route('sound_category')=>'مدرس مهدیه باقر العلوم',$category->title]])
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="our-services" class="padding bglight">
        <div class="container">
            <div class="row whitebox top10">
                @if (count($data)>0)
                    <div class="col-md-12 mt-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <form id="sorter">
                                        <div class="col-12">ترتیب :
                                            <select name="sort" id="sort">
                                                <option value="+id">تاریخ صعودی</option>
                                                <option value="-id">تاریخ نزولی</option>
                                                <option value="+title">عنوان صعودی</option>
                                                <option value="-title">عنوان نزولی</option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                @forelse($data as $item)
                    <div class="col-md-12 mt-3">
                        <a href="{{route('sound.detail',['id'=>$item->id])}}">
                            <div class="card">
                                <div class="card-body">
                                    {{$item->title}}
                                </div>
                                <div class="card-footer text-start">تاریخ انتشار : {{$item->human_time}}</div>
                            </div>
                        </a>
                    </div>
                @empty
                    @include('site.partials.empty_result')
                @endforelse
            </div>
{{--            <div class="row">--}}
{{--                {{$data->links()}}--}}
{{--            </div>--}}
        </div>
    </section>

    @push('script')
        <script>
            $(document).ready(function () {
                const params = new Proxy(new URLSearchParams(window.location.search), {
                    get: (searchParams, prop) => searchParams.get(prop),
                });
                $('#sort').children().each(function (index, item) {
                    if (params.sort === $(item).val()) {
                        $(item).prop('selected', true);
                    }
                });
                $('#sort').change(function (e) {
                    const data = $('#sorter').serialize();
                    window.location.href = "{{route('sound_category.detail',['id'=>$category->id])}}" + '?' + data;
                })
            })
        </script>
    @endpush
@endsection
