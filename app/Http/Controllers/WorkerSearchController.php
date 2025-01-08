<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WorkerSearchController extends Controller
{
    /**
     * Search for workers based on query parameters.
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
            ->orderByRaw("CASE
                WHEN service_type = ? THEN 1
                ELSE 2
            END", [$serviceType])
            ->paginate(10);

        // Return results
        return response()->json([
            'success' => true,
            'data' => $workers,
        ]);
    }
}
