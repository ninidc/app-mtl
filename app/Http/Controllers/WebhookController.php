<?php

namespace App\Http\Controllers;

use Log;
use Illuminate\Http\Request;
use App\Jobs\SyncUserWithMailchimp;
use Illuminate\Contracts\Bus\Dispatcher;

class WebhookController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke($hook, Request $request, Dispatcher $dispatcher)
    {
        switch ($hook) {
            case 'member.added':
            case 'member.edited':
            case 'member.updated':
            case 'member.deleted':
                $payload = file_get_contents('php://input');

                $member = data_get(json_decode($payload), 'member');

                $dispatcher->dispatchNow(new SyncUserWithMailchimp($member));
            break;
        }
    }
}
