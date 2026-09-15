<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class LandingController extends Controller
{
    public function index()
    {
        return view('landing');
    }

    public function qrcode()
    {
        $qrcode = QrCode::format('svg')
                         ->size(250)
                         ->color(15, 52, 96)
                         ->backgroundColor(255, 255, 255)
                         ->margin(1)
                         ->generate('https://kejapad.co.ke');

        return view('qrcode', compact('qrcode'));
    }
}
