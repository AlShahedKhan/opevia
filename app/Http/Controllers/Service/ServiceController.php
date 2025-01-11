<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller; // Import the base Controller class
use App\Jobs\Service\BookServiceJob;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Service;
use Illuminate\Support\Facades\Log;
use App\Traits\HandlesApiResponse;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    use HandlesApiResponse;

    /**
     * Book a service.
     */
    public function bookService()
    {
        return $this->safeCall(function () {

            if (!Auth::user() || Auth::user()->role !== 'client') {
                return $this->errorResponse('You are note authorized to book a service.Please create or login as a client.', 401);
            } else {
                BookServiceJob::dispatchSync(request()->all());

                $service = Service::latest()->first();
            }


            return $this->successResponse('Service successfully booked.', [
                'service_id' => $service->id,
            ], 201);
        });
    }
}
