<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Email;
use Illuminate\Http\JsonResponse;

class EmailController extends Controller
{
    public function reprocess(Email $email): JsonResponse
    {
        $email->resetForReprocessing();

        \App\Jobs\AnalyzeEmailJob::dispatch($email);

        return response()->json([
            'message' => "Re-analysis queued for email #{$email->id}.",
            'email' => [
                'id' => $email->id,
                'status' => $email->fresh()->status,
            ],
        ], 202);
    }
}