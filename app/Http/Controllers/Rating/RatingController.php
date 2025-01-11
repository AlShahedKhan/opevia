<?php

namespace App\Http\Controllers\Rating;

use App\Models\Rating;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RatingController extends Controller
{
    public function store(Request $request)
    {
        // Ensure the user is a client before proceeding
        if (auth()->user()->role !== 'client') {
            return response()->json(['error' => 'Only clients can give ratings.'], 403);
        }

        $validated = $request->validate([
            'worker_id' => 'required|exists:workers,id',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        // Prevent rating yourself (optional validation)
        // if (auth()->id() == $validated['worker_id']) {
        //     return response()->json(['error' => 'You cannot rate yourself.'], 403);
        // }

        // Update or create the rating record
        $rating = Rating::updateOrCreate(
            ['worker_id' => $validated['worker_id'], 'client_id' => auth()->id()],
            ['rating' => $validated['rating']]
        );



        return response()->json([
            'message' => 'Rating submitted successfully!',
            'rating' => $rating,
        ]);
    }

    // public function GetAvgRating(Request $request)
    // {
    //     $workerId = $request->input('worker_id');
    //     $worker = \App\Models\Worker::find($workerId);
    //     if (!$worker) {
    //         return response()->json(['error' => 'Worker not found.'], 404);
    //     }

    //     $avgRating = $worker->ratings()->avg('rating');
    //     return response()->json(['average_rating' => $avgRating]);
    // }


}
