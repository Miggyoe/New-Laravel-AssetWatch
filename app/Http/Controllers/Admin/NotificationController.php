<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function read(Request $request)
    {
        $user = $request->user();
        $user->notifications_read_at = now();
        $user->save();

        return back();
    }
}
