<?php

namespace App\Http\Controllers\Rating;

use App\Models\Rating;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\HandlesApiResponse;

class RatingController extends Controller
{
    use HandlesApiResponse;
    public function store(Request $request)
    {
        // Ensure the user is a client before proceeding
        if (auth()->user()->role !== 'client') {
            return $this->errorResponse('Only clients can give ratings.', 403);
        }

        $validated = $request->validate([
            'worker_id' => 'required|exists:workers,id',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        // Using safeCall to handle potential errors
        return $this->safeCall(function () use ($validated) {
            // Update or create the rating record
            $rating = Rating::updateOrCreate(
                ['worker_id' => $validated['worker_id'], 'client_id' => auth()->id()],
                ['rating' => $validated['rating']]
            );

            // Return success response
            return $this->successResponse('Rating submitted successfully!', [
                'rating' => $rating
            ]);
        });
    }
}
