<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Sponsor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SponsorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $placement = (string) $request->string('placement', 'footer');

        $sponsors = Sponsor::query()
            ->with('image')
            ->active()
            ->where('placement', $placement)
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $sponsors,
        ]);
    }
}
