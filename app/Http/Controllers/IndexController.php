<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;

class IndexController extends Controller
{
    public function clear()
    {
        Artisan::call('optimize:clear');
        Session::flush();
        return 'Config and Route Cached. All Cache Cleared';
    }
}
