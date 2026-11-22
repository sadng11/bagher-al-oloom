<?php

namespace App\Http\Controllers;

use App\Models\BottomText;
use App\Models\ImageSlider;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $homeSlider = ImageSlider::getSlider();
        $footerText = BottomText::getActive();
        return view('site.home', ['sliders' => $homeSlider, 'footer_text' => $footerText]);
    }
}
