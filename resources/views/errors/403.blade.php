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
                <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-50">
                    <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m0 3.75h.008M10.29 3.86 1.82 18a1.5 1.5 0 0 0 1.29 2.25h17.78a1.5 1.5 0 0 0 1.29-2.25L13.71 3.86a1.5 1.5 0 0 0-2.58 0Z"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 id="accessRestrictedTitle" class="text-base font-bold text-gray-900">Access restricted</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ $exception->getMessage() ?: 'Your role does not have access to that module.' }}</p>
                </div>
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
