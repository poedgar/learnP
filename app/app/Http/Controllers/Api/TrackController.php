<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Track;
use Illuminate\Http\JsonResponse;

class TrackController extends Controller
{
    /** Every learning path, with its courses' slugs in order. */
    public function index(): JsonResponse
    {
        $tracks = Track::with('courses:id,slug')->orderBy('position')->get()->map(fn (Track $track) => [
            'slug' => $track->slug,
            'title' => $track->title,
            'description' => $track->description,
            'courses' => $track->courses->pluck('slug'),
        ]);

        return response()->json(['data' => $tracks]);
    }
}
