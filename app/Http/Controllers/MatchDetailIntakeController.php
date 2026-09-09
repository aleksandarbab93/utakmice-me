<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\MatchDetailRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Takes match details fetched somewhere they can actually be fetched.
 *
 * SStats never finishes a response past ~14.6 KB for a datacenter IP, so
 * production cannot pull most match details itself (see the match_details
 * migration). A machine on an ordinary connection can, and this is where it
 * hands them over — `php artisan sstats:push-details` on that machine talks
 * to this.
 *
 * Off unless DETAIL_INTAKE_TOKEN is set, and 404s rather than 403s without
 * it: an endpoint nobody has configured shouldn't advertise that it exists.
 */
class MatchDetailIntakeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $token = config('services.detail_intake.token');

        abort_if(! $token, 404);
        abort_unless(hash_equals($token, (string) $request->header('X-Detail-Token')), 404);

        $validated = $request->validate([
            'details' => ['required', 'array', 'max:50'],
            'details.*.external_id' => ['required', 'string'],
            'details.*.payload' => ['required', 'array'],
        ]);

        $fixtures = Fixture::where('external_source', 'sstats')
            ->whereIn('external_id', collect($validated['details'])->pluck('external_id'))
            ->pluck('id', 'external_id');

        $stored = 0;
        $unknown = [];

        foreach ($validated['details'] as $detail) {
            $fixtureId = $fixtures->get($detail['external_id']);

            if (! $fixtureId) {
                $unknown[] = $detail['external_id'];

                continue;
            }

            MatchDetailRecord::updateOrCreate(
                ['fixture_id' => $fixtureId],
                ['payload' => $detail['payload'], 'fetched_at' => now()],
            );

            $stored++;
        }

        return response()->json([
            'stored' => $stored,
            'unknown' => $unknown,
        ]);
    }
}
