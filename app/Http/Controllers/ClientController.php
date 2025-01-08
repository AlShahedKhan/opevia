<?php
namespace App\Http\Controllers;

use App\Traits\HandlesApiResponse;
use App\Jobs\Client\ClientStoreJob;
use App\Http\Requests\Client\ClientRequest;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    use HandlesApiResponse;

    public function store(ClientRequest $request)
    {
        return $this->safeCall(function () use ($request) {
            // Log all incoming request data
            Log::info('Full Request Data:', ['all' => $request->all()]);
            Log::info('Uploaded Files:', ['files' => $request->file()]);

            $validated = $request->validated();

            // Handle both single and multiple file uploads
            $photoPaths = [];
            if ($request->hasFile('photos')) {
                $photoFiles = $request->file('photos');
                foreach ($photoFiles as $photo) {
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


             elseif ($request->hasFile('photo')) {
                Log::info('Single photo field is present in the request');
                $photo = $request->file('photo');
                if ($photo->isValid()) {
                    $path = $photo->store('photos', 'public');
                    $photoPaths[] = $path;
                    Log::info("Single photo uploaded successfully", [
                        'original_name' => $photo->getClientOriginalName(),
                        'stored_path' => $path,
                    ]);
                } else {
                    Log::warning('Invalid single photo file');
                }
            } else {
                Log::warning('No photos were uploaded');
            }

            // Replace photos with their stored paths in the validated data
            $validated['photos'] = $photoPaths;

            // Remove the `photo` field from the validated data to avoid serialization issues
            unset($validated['photo']);

            // Log final validated data
            Log::info('Final validated data passed to ClientStoreJob:', ['data' => $validated]);

            // Dispatch the job
            ClientStoreJob::dispatchSync($validated);

            return $this->successResponse('Client created successfully');
        });
    }
}
