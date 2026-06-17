<?php

namespace App\Http\Controllers;

abstract class Controller
{
    public function clear()
    {
        Artisan::call('optimize:clear');
        Session::flush();
        return 'Config and Route Cached. All Cache Cleared';
    }
}
