<?php

namespace App\Http\Controllers;

use App\Services\CpanelDeployService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeployController extends Controller
{
    public function __construct(private CpanelDeployService $cpanel) {}

    /**
     * Pulls the configured branch and kicks off the .cpanel.yml deployment tasks
     * (migrate --force, cache clears). Returns a task_id the frontend polls via
     * status() rather than blocking this request on the whole deploy completing.
     */
    public function store(): JsonResponse
    {
        try {
            $this->cpanel->pullLatest();
            $deployment = $this->cpanel->startDeployment();
        } catch (RequestException $e) {
            Log::warning('cPanel deploy trigger failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Could not reach cPanel — check CPANEL_* settings in .env.',
            ], 502);
        }

        return response()->json([
            'task_id' => $deployment['task_id'] ?? null,
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $taskId = (int) $request->query('task_id');

        try {
            $task = $this->cpanel->deploymentStatus($taskId);
        } catch (RequestException $e) {
            return response()->json(['message' => 'Could not reach cPanel.'], 502);
        }

        if (! $task) {
            return response()->json(['status' => 'unknown']);
        }

        $timestamps = $task['timestamps'] ?? [];

        return response()->json([
            'status' => $timestamps['failed'] ?? null
                ? 'failed'
                : ($timestamps['succeeded'] ?? null ? 'succeeded' : 'running'),
            'output' => $task['output'] ?? null,
        ]);
    }
}
