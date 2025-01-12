<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use App\Models\Rating;
use Illuminate\Http\Request;

class WorkerSearchController extends Controller
{
    /**
     * Search for workers based on query parameters and include their ratings.
     */
    public function search(Request $request)
    {
        // Validate input
        $request->validate([
            'query' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'service_type' => 'nullable|string|max:255',
        ]);

        // Fetch query parameters
        $query = $request->input('query');
        $location = $request->input('location');
        $serviceType = $request->input('service_type');

        // Perform search with weighted relevance
        $workers = Worker::query()
            ->when($query, function ($q) use ($query) {
                $q->where(function ($q) use ($query) {
                    $q->where('company_name', 'LIKE', "%{$query}%")
                      ->orWhere('description', 'LIKE', "%{$query}%");
                });
            })
            ->when($location, function ($q) use ($location) {
                $q->where('service_location', 'LIKE', "%{$location}%");
            })
            ->when($serviceType, function ($q) use ($serviceType) {
                $q->where('service_type', 'LIKE', "%{$serviceType}%");
            })
            ->paginate(10);

        // Add ratings for each worker
        $workers->getCollection()->transform(function ($worker) {
            // Fetch ratings for the current worker based on their ID (work_id)
            $ratings = Rating::where('work_id', $worker->id)->pluck('rating');

            // Add the ratings to the worker object
            $worker->ratings = $ratings;

            return $worker;
        });

        // Return results
        return response()->json([
            'success' => true,
            'data' => $workers,
        ]);
    }
}
