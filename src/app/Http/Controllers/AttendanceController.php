<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function stamp(): View
    {
        return view('attendance.stamp');
    }
}