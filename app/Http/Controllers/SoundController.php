<?php

namespace App\Http\Controllers;

use App\Models\Sound;
use App\Models\SoundCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SoundController extends Controller
{
    public function index(Request $request)
    {

        if (!$request->get('sort', null)) {
            return redirect()->route('sound_category', ['sort' => '-id']);
        }
        $sort = $request->get('sort');
        $type = ($request->get('sort')[0] == '-' ? 'desc' : 'asc');
        $field = Str::replace($request->get('sort')[0], '', $sort);
        $sounds = SoundCategory::getAllCategory($field, $type);
        return view('site.sound.category.list', ['data' => $sounds]);
    }

    public function show_category(Request $request, $id)
    {
        $category = SoundCategory::findOrFail($id);
        if (!$request->get('sort', null)) {
            return redirect()->route('sound_category.detail', ['id' => $id, 'sort' => '-id']);
        }
        $sort = $request->get('sort');
        $type = ($request->get('sort')[0] == '-' ? 'desc' : 'asc');
        $field = Str::replace($request->get('sort')[0], '', $sort);
        $sounds = Sound::getSoundCategory($id, $field, $type);
        return view('site.sound.category.detail', ['data' => $sounds, 'category' => $category]);
    }

    public function show(Request $request, $id)
    {
        $sound = Sound::findOrFail($id);
        return view('site.sound.detail', ['data' => $sound]);
    }
}
