<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class IndexController extends Controller
{
    public static function clear()
    {
        Artisan::call('optimize:clear');
        Session::flush();
        return 'Config and Route Cached. All Cache Cleared';
    }
}
