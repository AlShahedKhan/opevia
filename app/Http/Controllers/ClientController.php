<?php

namespace App\Http\Controllers;

use App\Traits\HandlesApiResponse;
use App\Jobs\Client\ClientStoreJob;
use Illuminate\Support\Facades\Log;
use App\Jobs\Client\GetAllClientJob;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Client\ClientRequest;
use App\Models\Client;

class ClientController extends Controller
{
    use HandlesApiResponse;

    public function index()
    {
        return $this->safeCall(function () {
            // Dispatch the job synchronously and get the workers
            $workers = GetAllClientJob::dispatchSync();

            return $this->successResponse('Workers fetched successfully', [
                'data' => $workers,
            ]);
        });
    }

    public function store(ClientRequest $request)
    {
        return $this->safeCall(function () use ($request) {
            $user = Auth::user();
            if (!$user || $user->role !== 'client') {
                return $this->errorResponse('Unauthorized access', 403);
            }

            $validated = $request->validated();

            // Check if worker_id exists in the database
            $workerId = $validated['worker_id'];
            if (!\App\Models\Worker::find($workerId)) {
                return $this->errorResponse('Invalid worker_id. Worker not found.', 422);
            }

            $photoPaths = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    if ($photo->isValid()) {
                        $path = $photo->store('photos', 'public');
                        $photoPaths[] = $path;
                    }
                }
            }

            $validated['photos'] = $photoPaths;
            $validated['user_id'] = $user->id;

            // Dispatch the job and fetch the client record
            ClientStoreJob::dispatchSync($validated);
            $client = Client::where('email', $validated['email'])->latest('id')->first();

            // Log the response data
            Log::info('Response data:', [
                'id' => $client->id,
                'data' => $validated,
            ]);

            return $this->successResponse('Client created successfully', [
                'id' => $client->id,
                'data' => $validated,
            ]);
        });
    }






    public function show(Client $client)
    {
        return $this->safeCall(function () use ($client) {
            return $this->successResponse('Client fetched successfully', [
                'data' => $client,
            ]);
        });
    }
}
