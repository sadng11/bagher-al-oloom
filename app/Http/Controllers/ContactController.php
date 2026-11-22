<?php

namespace App\Http\Controllers;

use App\Mail\ContactUs;
use App\Models\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $data = SiteContent::getContent(SiteContent::CONTACT);
        return view('site.contact', ['data' => $data]);
    }

    public function contact(Request $request)
    {
        $inputs = $request->all();
        Mail::to('info@bagheraloloom.com')->send(new ContactUs($inputs['email'], $inputs['name'], $inputs['message']));
        return back()->with('message', trans("messages.message.ok"));
    }
}
