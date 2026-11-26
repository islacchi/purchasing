{{-- Shown when PHP's post_max_size rejects the request before the app sees
     it (ValidatePostSize throws PostTooLargeException → 413). Deliberately
     standalone — no dependencies on the authenticated layout — but mirrors
     the amber modal style used by the 403 page. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <title>Submission too large - Purchasing</title>
    <script src="{{ asset('js/tailwind-play-cdn.js') }}"></script>
</head>
<body class="bg-gray-100">
    <main class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-sm rounded-lg bg-white shadow-xl" role="dialog" aria-modal="true" aria-labelledby="submissionTooLargeTitle">
            <div class="flex items-start gap-3 px-5 py-4">
                @include('partials.restricted-notice', [
                    'title' => 'Submission too large',
                    'reason' => 'The server rejected this request before the application saw it, so nothing was saved. If you were uploading a quotation file, it is larger than the server allows — ask the administrator to raise the post_max_size / upload_max_filesize limits, or send a smaller file.',
                    'titleId' => 'submissionTooLargeTitle',
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
