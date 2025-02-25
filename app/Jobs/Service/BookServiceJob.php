<?php

namespace App\Jobs\Service;

use App\Models\User;
use App\Models\Service;
use Illuminate\Http\Request;
use App\Traits\HandlesApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class BookServiceJob implements ShouldQueue
{
    use Queueable, HandlesApiResponse;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(Request $request)
    {

        $request->validate([
            'client_id' => 'required|exists:users,id',
            'worker_id' => 'required|exists:users,id',
            'client_work_req_id' => 'required|exists:clients,id',
        ]);

        $clientId = $request->input('client_id');
        $workerId = $request->input('worker_id');
        $clientWorkReqId = $request->input('client_work_req_id');

        $service = Service::create([
            'client_id' => $clientId,
            'worker_id' => $workerId,
            'client_work_req_id' => $clientWorkReqId,
            'status' => 'pending', // Default status
        ]);

        return $service;
    }
}
