<?php

namespace App\Http\Middleware;

use App\Support\PageVisitKey;
use App\Support\PageVisitRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RecordPageVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = PageVisitKey::resolve($request);

        if ($key !== null) {
            PageVisitRecorder::record($key);
        }

        return $next($request);
    }
}
