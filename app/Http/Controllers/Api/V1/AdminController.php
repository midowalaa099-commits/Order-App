<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function overview(Request $request)
    {
        return response()->json([
            'message' => 'Admin access granted.',
            'user' => [
                'id' => $request->user()->id,
                'role' => $request->user()->role,
                'name' => $request->user()->name,
            ],
        ]);
    }
}
