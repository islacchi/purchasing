{{-- Shown when the role lacks access to a module (see EnsureModuleAccess), or for
     any other 403 in the app. Deliberately standalone — no dependencies on the
     authenticated layout — but mirrors the amber modal style. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access restricted - Purchasing</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <main class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-sm rounded-lg bg-white shadow-xl" role="dialog" aria-modal="true" aria-labelledby="accessRestrictedTitle">
            <div class="flex items-start gap-3 px-5 py-4">
                @include('partials.restricted-notice', [
                    'title' => 'Access restricted',
                    'reason' => $exception->getMessage() ?: 'Your role does not have access to that module.',
                    'titleId' => 'accessRestrictedTitle',
                    'as' => 'h1',
                ])
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-3">
                <button type="button"
                        onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = '/'; }"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">
                    Okay
                </button>
            </div>
        </div>
    </main>
</body>
</html>
