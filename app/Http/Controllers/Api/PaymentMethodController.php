<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;

class PaymentMethodController extends Controller
{
    public function index() 
    {
        $bank = PaymentMethod::where('status', 'active')
                ->where('code', '!=', 'ayo')
                ->get()
                ->map(function ($item) {
                    $item->thumbnail = $item->thumbnail ? url('banks/'.$item->thumbnail) : "";
                    return $item;
                });
        
        return response()->json($bank); 
    }
}
