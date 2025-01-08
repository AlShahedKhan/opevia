<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\HandlesApiResponse;
use App\Jobs\Contact\ContactStoreJob;
use App\Http\Requests\Contact\ContactStoreRequest;

class ContactController extends Controller
{
    use HandlesApiResponse;
    public function store(ContactStoreRequest $request)
    {
        return $this->safeCall(function () use ($request) {
            $validated = $request->validated();
            if (!$validated) {
                return $this->errorResponse('Validation failed', 422, $validated);
            }

            dispatch(new ContactStoreJob($validated));

            return $this->successResponse(
                'Contact form submited successfully',
                [
                    'date' => $validated,
                ]
            );
        });

    }
}
