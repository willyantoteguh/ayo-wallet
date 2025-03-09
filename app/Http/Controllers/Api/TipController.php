<?php

namespace App\Http\Controllers\Api;

use App\Models\Tip;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TipController extends Controller
{
    public function index(Request $request)
    {
        // Dynamic limit for pagination
        $limit = $request->query('limit') ? $request->query('limit') : 10;
        
        $tips = Tip::select('id', 'title', 'url', 'thumbnail')->paginate($limit);

        $tips->getCollection()->transform(function ($item) {
            $item->thumbnail = $item->thumbnail ? url('tips/'.$item->thumbnail) : '';
            return $item;
        });

        return response()->json($tips);
    }
}
