<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    public function testLog(Client $client)
    {
        Log::info("This is a test log.");
        return response()->json(['message' => 'Check the logs']);
    }
}
