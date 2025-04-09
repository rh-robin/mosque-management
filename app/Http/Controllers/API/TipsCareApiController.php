<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TipsCare;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class TipsCareApiController extends Controller
{
    use ResponseTrait;
    public function index(){
        $data = TipsCare::all();
        $message = '';
        return $this->sendResponse($data, $message, '', 200);
    }

    public function terms(){
        return view('backend.layouts.terms_and_conditions');
    }

    public function policy(){
        return view('backend.layouts.privacy_policy');
    }

}
