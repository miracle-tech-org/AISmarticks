<?php

namespace Modules\MobileNotifications\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class MobileNotificationsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        // This method is kept for potential future use, such as an admin dashboard for this module.
        // The corresponding view 'mobilenotifications::index' should be updated or created when this is implemented.
        return view('mobilenotifications::index');
    }
}
