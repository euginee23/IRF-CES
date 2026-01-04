<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogLivewireRequests
{
    public function handle(Request $request, Closure $next)
    {
        $path = $request->path();
        $isLivewire = str_contains($path, 'livewire') || $request->header('X-Livewire') !== null;

        if ($isLivewire) {
            try {
                $files = [];
                foreach ($request->allFiles() as $key => $file) {
                    if (is_array($file)) {
                        foreach ($file as $f) {
                            $files[] = [$key, $f->getClientOriginalName() ?? 'n/a', $f->getSize() ?? 0, $f->getClientMimeType() ?? 'n/a'];
                        }
                    } else {
                        $f = $file;
                        $files[] = [$key, $f->getClientOriginalName() ?? 'n/a', $f->getSize() ?? 0, $f->getClientMimeType() ?? 'n/a'];
                    }
                }

                Log::info('Livewire request', [
                    'method' => $request->method(),
                    'path' => $path,
                    'ip' => $request->ip(),
                    'content_length' => $request->header('content-length'),
                    'files' => $files,
                    'headers' => [
                        'referer' => $request->header('referer'),
                        'user-agent' => $request->header('user-agent'),
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed logging Livewire request: ' . $e->getMessage());
            }
        }

        return $next($request);
    }
}
