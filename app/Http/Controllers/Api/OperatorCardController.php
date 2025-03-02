<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OperatorCard;

class OperatorCardController extends Controller
{
    public function index(Request $request)
    {
        // Dynamic limit for pagination
        $limit = $request->query('limit') ? $request->query('limit') : 10;

        $operatorCard = OperatorCard::with('dataPlan')
        ->where('status', 'active')
        ->paginate($limit);

        $operatorCard->getCollection()->transform(function ($item) {
            $item->thumbnail = $item->thumbnail ? url($item->thumbnail) : "";
            return $item;
        });

        return response()->json($operatorCard);
    }
}
