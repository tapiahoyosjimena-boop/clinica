<?php

namespace App\Http\Controllers;

use App\Services\HomeSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeSearchController
{
    public function __invoke(Request $request, HomeSearchService $homeSearch): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:80'],
        ]);

        $items = $homeSearch->search($request->user(), $validated['q']);

        return response()->json([
            'items' => $items,
        ]);
    }
}
