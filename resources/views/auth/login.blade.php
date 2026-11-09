<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Purchasing</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans antialiased flex items-center justify-center min-h-screen">

    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-sm">
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Purchasing Login</h1>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div>
                <label for="login" class="block text-sm font-medium text-gray-700 mb-1">Email or Username</label>
                <input type="text" name="login" id="login" value="{{ old('login') }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                       placeholder="admin" autocomplete="username" autofocus required aria-describedby="login-error">
                {{-- Keep the original field spacing while reserving room for a one-line error. --}}
                <p id="login-error" class="mb-1 min-h-4 leading-4 text-xs text-red-600" aria-live="polite">{{ $errors->first('login') }}</p>
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" id="password"
                       class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                       placeholder="********" autocomplete="current-password" required aria-describedby="password-error">
                {{-- Keep the original field spacing while reserving room for a one-line error. --}}
                <p id="password-error" class="mb-1 min-h-6 leading-6 text-xs text-red-600" aria-live="polite">{{ $errors->first('password') }}</p>
            </div>
            <button type="submit"
                    class="w-full bg-teal-600 text-white font-semibold py-2 px-4 rounded hover:bg-teal-700 transition-colors">
                Sign In
            </button>
        </form>

        <p class="text-xs text-gray-400 text-center mt-4">Sign in with your account credentials.</p>
    </div>

</body>
</html>
