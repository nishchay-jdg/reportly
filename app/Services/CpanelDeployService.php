<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Triggers a deploy on shared cPanel hosting without SSH — pulls the configured
 * branch via cPanel's Git Version Control, then runs the .cpanel.yml deployment
 * tasks (migrate --force, cache clears — see .cpanel.yml at the repo root).
 *
 * Talks to cPanel's UAPI directly over HTTPS using an API token, so the token
 * never reaches the browser — the admin panel button just calls our own
 * server, which calls cPanel server-to-server.
 */
class CpanelDeployService
{
    /**
     * @throws RequestException
     */
    public function pullLatest(): array
    {
        return $this->call('VersionControl', 'update', [
            'repository_root' => config('services.cpanel.repository_root'),
            'branch' => config('services.cpanel.branch'),
        ]);
    }

    /**
     * @throws RequestException
     */
    public function startDeployment(): array
    {
        $result = $this->call('VersionControlDeployment', 'create', [
            'repository_root' => config('services.cpanel.repository_root'),
        ]);

        return $result['data'] ?? [];
    }

    /**
     * @throws RequestException
     */
    public function deploymentStatus(int $taskId): ?array
    {
        $result = $this->call('VersionControlDeployment', 'retrieve');

        $tasks = $result['data'] ?? [];

        foreach ($tasks as $task) {
            if ((int) ($task['task_id'] ?? 0) === $taskId) {
                return $task;
            }
        }

        return null;
    }

    /**
     * @throws RequestException
     */
    private function call(string $module, string $function, array $params = []): array
    {
        $host = config('services.cpanel.host');
        $port = config('services.cpanel.port', 2083);
        $username = config('services.cpanel.username');
        $token = config('services.cpanel.api_token');

        $response = Http::withHeaders([
            'Authorization' => "cpanel {$username}:{$token}",
        ])->get("https://{$host}:{$port}/execute/{$module}/{$function}", $params);

        $response->throw();

        return $response->json();
    }
}
