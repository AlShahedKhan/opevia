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
     * Fetch all services.
     */
    public function index()
    {
        return $this->safeCall(function () {
            $user = Auth::user();

            // Check if the user has the role of 'worker'
            if (!$user || $user->role !== 'worker') {
                return $this->errorResponse('Unauthorized access', 403);
            }

            $services = Service::where('worker_id', $user->id)
            ->with('client')->get();
            return $this->successResponse('Services fetched successfully', [
                'data' => $services,
            ]);
        });
    }
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

    public function acceptService(Service $service)
    {
        return $this->safeCall(function () use ($service) {
            $user = Auth::user();

            // Check if the authenticated user's ID matches the worker_id
            if ($user->id !== $service->worker_id) {
                return $this->errorResponse('Unauthorized access', 403);
            }

            $service->update(['status' => 'processing']);
            return $this->successResponse('Service successfully accepted.', [
                'service_id' => $service->id,
            ]);
        });
    }

    /**
     * Cancel a service.
     */
    public function cancelService(Service $service)
    {
        return $this->safeCall(function () use ($service) {
            $user = Auth::user();

            // Check if the authenticated user's ID matches the worker_id
            if ($user->id !== $service->worker_id) {
                return $this->errorResponse('Unauthorized access', 403);
            }

            $service->update(['status' => 'canceled']);
            return $this->successResponse('Service successfully canceled.', [
                'service_id' => $service->id,
            ]);
        });
    }

    public function completeService(Service $service)
    {
        return $this->safeCall(function () use ($service) {
            $user = Auth::user();

            // Check if the authenticated user's ID matches the worker_id
            if ($user->id !== $service->worker_id) {
                return $this->errorResponse('Unauthorized access', 403);
            }

            $service->update(['status' => 'completed']);
            return $this->successResponse('Service successfully completed.', [
                'service_id' => $service->id,
            ]);
        });
    }

    public function show(Service $service)
    {
        return $this->safeCall(function () use ($service) {
            $user = Auth::user();

            // Check if the authenticated user's ID matches the worker_id
            if ($user->id !== $service->worker_id) {
                return $this->errorResponse('Unauthorized access', 403);
            }

            // Load the related client data
            $service->load('client');

            return $this->successResponse('Service fetched successfully', [
                'data' => $service,
            ]);
        });
    }

    public function pendingServices(){
        return $this->safeCall(function () {
            $user = Auth::user();

            // Check if the user has the role of 'worker'
            if (!$user || $user->role !== 'worker') {
                return $this->errorResponse('Unauthorized access', 403);
            }

            // Fetch pending services where the worker_id matches the authenticated user's ID and include related client data
            $services = Service::where('worker_id', $user->id)
                ->where('status', 'pending')
                ->with('client')
                ->get();

            if ($services->isEmpty()) {
                return $this->successResponse('No pending services found.', [
                    'data' => [],
                ]);
            }

            return $this->successResponse('Pending services fetched successfully', [
                'data' => $services,
            ]);
        });
    }
    public function completedServices(){
        return $this->safeCall(function () {
            $user = Auth::user();

            // Check if the user has the role of 'worker'
            if (!$user || $user->role !== 'worker') {
                return $this->errorResponse('Unauthorized access', 403);
            }

            // Fetch pending services where the worker_id matches the authenticated user's ID and include related client data
            $services = Service::where('worker_id', $user->id)
                ->where('status', 'completed')
                ->with('client')
                ->get();

            if ($services->isEmpty()) {
                return $this->successResponse('No completed services found.', [
                    'data' => [],
                ]);
            }

            return $this->successResponse('Completed services fetched successfully', [
                'data' => $services,
            ]);
        });
    }
}
