<?php

namespace App\Http\Controllers;

use App\Traits\HandlesApiResponse;
use App\Jobs\Worker\WorkerStoreJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Worker\WorkerStoreRequest;
use App\Jobs\Worker\GetAllWorkerJob;
use App\Models\Worker;

class WorkerController extends Controller
{
    use HandlesApiResponse;

    /**
     * Fetch all workers.
     */
    public function index()
    {
        return $this->safeCall(function () {
            // Dispatch the job synchronously and get the workers
            $workers = GetAllWorkerJob::dispatchSync();

            return $this->successResponse('Workers fetched successfully', [
                'data' => $workers,
            ]);
        });
    }

    /**
     * Create a new worker.
     */
    public function store(WorkerStoreRequest $request)
    {
        return $this->safeCall(function () use ($request) {
            // Check if the user is authenticated and has the role of a worker
            $user = Auth::user();
            if (!$user || $user->role !== 'worker') {
                return $this->errorResponse('Unauthorized access', 403);
            }

            // Log all incoming request data
            Log::info('Full Request Data:', ['all' => $request->all()]);
            Log::info('Uploaded Files:', ['files' => $request->file()]);

            $validated = $request->validated();

            // Handle both single and multiple file uploads
            $photoPaths = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    if ($photo->isValid()) {
                        $path = $photo->store('photos', 'public');
                        $photoPaths[] = $path;
                        Log::info('Photo uploaded successfully', [
                            'original_name' => $photo->getClientOriginalName(),
                            'stored_path' => $path,
                        ]);
                    }
                }
            }

            $validated['photos'] = $photoPaths;
            $validated['user_id'] = $user->id;

            // Log final validated data
            Log::info('Final validated data passed to WorkerStoreJob:', ['data' => $validated]);

            // Dispatch the job
            WorkerStoreJob::dispatchSync($validated);

            // Retrieve the newly created worker by email (or any unique identifier)
            $worker = Worker::where('email', $validated['email'])->latest('id')->first();

            return $this->successResponse('Worker created successfully', [
                'id' => $worker->id,
                'data' => $validated,
            ]);
        });
    }


    /**
     * Fetch a specific worker.
     */
    public function show(Worker $worker)
    {
        return $this->safeCall(function () use ($worker) {
            return $this->successResponse('Worker fetched successfully', [
                'data' => $worker,
            ]);
        });
    }
}
