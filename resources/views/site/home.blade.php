@extends('site.app')

@section('content')
    <section id="main-banner-area" class="position-relative">
        <div id="revo_main_wrapper" class="rev_slider_wrapper fullwidthbanner-container m-0 p-0 bg-dark"
             data-alias="classic4export" data-source="gallery">
            <!-- START REVOLUTION SLIDER 5.4.1 fullwidth mode -->
            <div id="rev_main" class="rev_slider fullwidthabanner white" data-version="5.4.1">
                <ul>
                    @foreach($sliders as $slider)
                        <li data-index="rs-{{$loop->iteration}}" data-transition="fade" data-slotamount="default"
                            data-easein="Power100.easeIn" data-easeout="Power100.easeOut" data-masterspeed="2000"
                            data-fsmasterspeed="1500" data-param1="{{$loop->iteration}}">
                            <!-- MAIN IMAGE {{$loop->iteration}} -->
                            <img src="{{ asset('storage/slider/images/'.$slider->source)  }}" alt=""
                                 data-bgposition="center"
                                 data-bgfit="cover"
                                 data-bgrepeat="no-repeat" data-bgparallax="10" class="rev-slidebg" data-no-retina>
                    @endforeach
                </ul>
            </div>
        </div>
        @if ($footer_text && count($footer_text)>0)
            <div style="text-align: center; margin: auto; position: relative; width: 90%">
                <div class="repeat-text">
                    <ul class="marquee">
                        @foreach($footer_text as $text)
                            <li>{{$text->text}}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </section>
@endsection
