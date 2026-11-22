<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="{{ asset('site/js/jquery-3.6.0.min.js') }}"></script>
<!--Bootstrap Core-->
<script src="{{ asset('site/js/propper.min.js') }}"></script>
<script src="{{ asset('site/js/bootstrap.min.js') }}"></script>
<!--to view items on reach-->
<script src="{{ asset('site/js/jquery.appear.js') }}"></script>
<!--Owl Slider-->
<script src="{{ asset('site/js/owl.carousel.min.js') }}"></script>
<!--number counters-->
<script src="{{ asset('site/js/jquery-countTo.js') }}"></script>
<!--Parallax Background-->
<script src="{{ asset('site/js/parallaxie.js') }}"></script>
<!--Cubefolio Gallery-->
<script src="{{ asset('site/js/jquery.cubeportfolio.min.js') }}"></script>
<!--Fancybox js-->
<script src="{{ asset('site/js/jquery.fancybox.min.js') }}"></script>
<!--tooltip js-->
<script src="{{ asset('site/js/tooltipster.min.js') }}"></script>
<!--wow js-->
<script src="{{ asset('site/js/wow.js') }}"></script>
<!--Revolution SLider-->
<script src="{{ asset('site/js/revolution/jquery.themepunch.tools.min.js') }}"></script>
<script src="{{ asset('site/js/revolution/jquery.themepunch.revolution.min.js') }}"></script>
<!-- SLIDER REVOLUTION 5.0 EXTENSIONS -->
<script src="{{ asset('site/js/revolution/extensions/revolution.extension.actions.min.js') }}"></script>
<script src="{{ asset('site/js/revolution/extensions/revolution.extension.carousel.min.js') }}"></script>
<script src="{{ asset('site/js/revolution/extensions/revolution.extension.kenburn.min.js') }}"></script>
<script src="{{ asset('site/js/revolution/extensions/revolution.extension.layeranimation.min.js') }}"></script>
<script src="{{ asset('site/js/revolution/extensions/revolution.extension.migration.min.js') }}"></script>
<script src="{{ asset('site/js/revolution/extensions/revolution.extension.navigation.min.js') }}"></script>
<script src="{{ asset('site/js/revolution/extensions/revolution.extension.parallax.min.js') }}"></script>
<script src="{{ asset('site/js/revolution/extensions/revolution.extension.slideanims.min.js') }}"></script>
<script src="{{ asset('site/js/revolution/extensions/revolution.extension.video.min.js') }}"></script>
<script src="{{asset('site/js/jquery.marquee.min.js')}}"></script>
<!--custom functions and script-->
<script src="{{ asset('site/js/functions.js') }}?v={{Str::random(10)}}{{time()}}"></script>
@vite('resources/js/app.js')
@stack('script')
