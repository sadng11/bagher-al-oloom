<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
{{--    <title>{{$book->title}}</title>--}}
    <style>
        body {
        }

        .title {
            width: 100%;
            text-align: center;
        }

        .line {
            line-height: 25px;
        }

        p {
            text-align: justify;
            direction: rtl;
            margin-top: 0;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
{{--<htmlpageheader name="MyHeader1">--}}
{{--    <div style="text-align: right">My document</div>--}}
{{--</htmlpageheader>--}}
{{--<htmlpageheader name="myHeader2">--}}
{{--    <div style="border-bottom: 1px solid #000000; font-weight: bold;  font-size: 10pt;">--}}
{{--        My document--}}
{{--    </div>--}}
{{--</htmlpageheader>--}}
{{--<sethtmlpageheader name="MyHeader1" value="on" />--}}
{{--<sethtmlpageheader name="myHeader2" value="on"/>--}}

<htmlpagefooter name="myFooter1">
    <table width="100%" style="border-top: 1px solid #000000; margin-top: 40px;">
        <tr>
            <td width="50%" style="text-align: right; width: 50%;">{PAGENO}/{nbpg}</td>
            <td width="50%" style="text-align: left; width: 50%;">
{{--                <a href="{{config('app.url')}}">{{env('SITE_TITLE')}}</a>--}}
            </td>
        </tr>
    </table>
</htmlpagefooter>
<sethtmlpagefooter name="myFooter1"/>
<div id="app">
    <h3 class="title">{{$book->title}}</h3>
    @foreach($book->contents() as $book_content)
        <div class="line">{!! $book_content->content !!}</div>
    @endforeach
</div>

</body>
</html>
