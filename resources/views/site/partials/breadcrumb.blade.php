<ul class="breadcrumb top10 bottom10 float-start">
    <li class="breadcrumb-item hover-light"><a href="{{URL::to('/')}}">خانه</a></li>
    @foreach($crumb_data as $key=>$item)
        @if (is_numeric($key))
            <li class="breadcrumb-item hover-light">{{$item}}</li>
        @else
            <li class="breadcrumb-item hover-light">
                <a href="{{$key}}">
                    {{$item}}
                </a>
            </li>
        @endif
    @endforeach
</ul>
