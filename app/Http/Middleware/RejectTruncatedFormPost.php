<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectTruncatedFormPost
{
    /**
     * Refuse form posts that PHP has already cut off at max_input_vars.
     *
     * The high-volume forms submit one JSON payload, so the PHP limit never
     * applies to them. When JavaScript is unavailable they fall back to
     * classic field arrays, and a form above the cap would otherwise save
     * only its first batch of fields without any error.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getRealMethod() === 'POST' && $this->looksTruncated($request)) {
            // No withInput(): the parsed payload is partial, and flashing a
            // thousand fields could overflow the session store too.
            return back()->withErrors([
                'form' => 'This form is larger than the server can accept in one piece. Please enable JavaScript and submit again.',
            ]);
        }

        return $next($request);
    }

    private function looksTruncated(Request $request): bool
    {
        $max = (int) config('app.max_form_vars', (int) ini_get('max_input_vars'));

        // A limit of 0 means unlimited; JSON requests are one field anyway.
        if ($max < 1 || $request->isJson()) {
            return false;
        }

        // The high-volume forms also keep classic fallback inputs in the DOM.
        // PHP may truncate those, but an intact JSON payload already carries
        // every row, so this is a complete submission, not a lossy one.
        if ($this->hasJsonPayload($request)) {
            return false;
        }

        return $this->countInputVariables($request->request->all()) >= $max;
    }

    private function hasJsonPayload(Request $request): bool
    {
        foreach ($request->request->all() as $key => $value) {
            if (! is_string($key) || ! str_ends_with($key, '_json')) {
                continue;
            }

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            json_decode($value);

            if (json_last_error() === JSON_ERROR_NONE) {
                return true;
            }
        }

        return false;
    }

    /**
     * Count input variables the way PHP counts them for max_input_vars:
     * every scalar leaf of every array, not the arrays themselves.
     */
    private function countInputVariables(array $input): int
    {
        $count = 0;

        foreach ($input as $value) {
            $count += is_array($value) ? $this->countInputVariables($value) : 1;
        }

        return $count;
    }
}
