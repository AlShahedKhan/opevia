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
            'work_id' => 'required|exists:workers,id',
            'worker_id' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string'
        ]);

        // Using safeCall to handle potential errors
        return $this->safeCall(function () use ($validated) {
            // Update or create the rating record
            $rating = Rating::updateOrCreate(
                [
                    'work_id' => $validated['work_id'],
                    'client_id' => auth()->id()
                ],
                [
                    'worker_id' => $validated['worker_id'],
                    'rating' => $validated['rating'],
                    'feedback' => $validated['feedback']
                ]
            );
            // Return success response
            return $this->successResponse('Rating submitted successfully!', [
                'rating' => $rating
            ]);
        });
    }
    public function getAverageRating($worker_id)
    {
        return $this->safeCall(function () use ($worker_id) {
            $ratings = Rating::where('worker_id', $worker_id)->pluck('rating');

            if ($ratings->isEmpty()) {
                return $this->errorResponse('No ratings found for this worker.', 404);
            }

            // Count the total number of ratings for the worker
            $totalRatings = $ratings->count();

            $sumOfRatings = $ratings->sum();

            // Calculate the average rating
            $averageRating = $sumOfRatings / $totalRatings;
            $formattedAverage = number_format($averageRating, 1, '.', '');

            return $this->successResponse('Average rating retrieved successfully.', [
                'worker_id' => $worker_id,
                'averageRating' => $formattedAverage
            ]);
        });
    }

}
