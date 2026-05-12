<?php

namespace App\Http\Controllers;

// use Illuminate\Http\Request;

class LocalizationController extends Controller
{
    //
    public function __invoke($locale)
{
    if (! in_array($locale, ['ar', 'en'])) {
        abort(400);
    }

    session()->put('locale', $locale);

    return redirect()->back();
}
}
