<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    /**
     * __invoke
     *
     * @param  mixed $path
     * @param  mixed $request
     * @param  mixed $dispatcher
     * @return void
     */
    public function __invoke($path, Request $request)
    {
        $disk = Storage::disk('do');

        echo $disk->get(base64_decode($path));
    }
}
