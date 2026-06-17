<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Artisan;

class IndexController extends Controller
{
    public function clear()
    {
        Artisan::call('optimize:clear');
        Session::flush();
        return 'Config and Route Cached. All Cache Cleared';
    }
}
