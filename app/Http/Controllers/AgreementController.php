<?php

namespace App\Http\Controllers;

use App\Mail\AgreementSigned;
use App\Models\AgreementSignature;
use App\Models\Share;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AgreementController extends Controller
{
    /**
     * Whether this share's agreement has already been signed — the report's own JS
     * calls this on load (via postMessage, since it runs in a sandboxed iframe) to
     * decide whether to render the form or a locked, already-signed receipt.
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $share = Share::findPublicBySlug($slug);

        abort_unless($share->isAccessible(), 410);
        $this->abortUnlessUnlocked($request, $share);

        return response()->json($this->payload($share));
    }

    public function store(Request $request, string $slug): JsonResponse
    {
        $share = Share::findPublicBySlug($slug);

        abort_unless($share->isAccessible(), 410);
        $this->abortUnlessUnlocked($request, $share);

        // Already signed via this link — the record is immutable, so we don't overwrite
        // it, we just hand back the existing signature as if this were a fresh check.
        if ($share->agreementSignature) {
            return response()->json($this->payload($share), 409);
        }

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:180'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'signature_text' => ['required', 'string', 'max:150'],
            'terms_url' => ['nullable', 'string', 'max:2000'],
            'agree_terms' => ['required', 'accepted'],
        ]);

        try {
            $signature = AgreementSignature::create([
                'share_id' => $share->id,
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'company_name' => $data['company_name'] ?? null,
                'signature_text' => $data['signature_text'],
                'terms_url' => $data['terms_url'] ?? null,
                'ip_address' => $request->ip(),
                'signed_at' => now(),
            ]);
        } catch (QueryException $e) {
            // Two tabs submitting at once both pass the check above — the unique
            // constraint on share_id is the real guard against a duplicate signature.
            return response()->json($this->payload($share->fresh()), 409);
        }

        $this->notifySigned($signature);

        return response()->json($this->payload($share->fresh()));
    }

    private function notifySigned(AgreementSignature $signature): void
    {
        $org = $signature->share->organization;

        if ($org->notification_email) {
            try {
                Mail::to($org->notification_email)->send(new AgreementSigned($signature));
            } catch (\Throwable $e) {
                Log::warning('Failed to send agreement-signed notification', ['signature_id' => $signature->id, 'error' => $e->getMessage()]);
            }
        }

        try {
            Mail::to($signature->email)->send(new AgreementSigned($signature, isSignerCopy: true));
        } catch (\Throwable $e) {
            Log::warning('Failed to send agreement-signed receipt to signer', ['signature_id' => $signature->id, 'error' => $e->getMessage()]);
        }
    }

    private function abortUnlessUnlocked(Request $request, Share $share): void
    {
        if ($share->visibility === 'password' && ! $request->session()->get("share_unlocked_{$share->id}", false)) {
            abort(403);
        }
    }

    private function payload(Share $share): array
    {
        $signature = $share->agreementSignature;

        if (! $signature) {
            return ['signed' => false];
        }

        return [
            'signed' => true,
            'full_name' => $signature->full_name,
            'email' => $signature->email,
            'company_name' => $signature->company_name,
            'signature_text' => $signature->signature_text,
            'signed_at' => $signature->signed_at->format('M j, Y g:ia'),
        ];
    }
}
