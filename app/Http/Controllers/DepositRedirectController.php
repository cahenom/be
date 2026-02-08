<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DepositRedirectController extends Controller
{
    /**
     * Deposit success page
     */
    public function success(Request $request)
    {
        return view('deposit.success');
    }

    /**
     * Deposit failed page
     */
    public function failed(Request $request)
    {
        return view('deposit.failed');
    }
}
