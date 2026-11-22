<?php

namespace App\Http\Controllers;

use App\Models\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    public function index(Request $request, $type)
    {
//        $type = constant("\App\Models\SiteContent::" . Str::upper($type));
        $data = SiteContent::getContent($type);
        return view('site.content', ['data' => $data, 'type' => $type]);
    }
}
