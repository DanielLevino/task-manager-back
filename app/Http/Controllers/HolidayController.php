<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\HolidayService;

class HolidayController extends Controller
{
    public function check(Request $req, HolidayService $svc)
    {
        $data = $req->validate([
            'date' => ['required', 'date'],
            'uf'   => ['nullable', 'string', 'size:2'],
        ]);
        $uf = strtoupper($data['uf'] ?? config('app.holiday_uf', env('HOLIDAY_DEFAULT_UF', 'PE')));
        return response()->json($svc->check($data['date'], $uf));
    }
}
