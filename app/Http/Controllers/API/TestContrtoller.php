<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestContrtoller extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'Hello World']);
    }
}
