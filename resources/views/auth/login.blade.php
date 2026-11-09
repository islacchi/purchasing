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
            <div class="mb-4">
                <label for="login" class="block text-sm font-medium text-gray-700 mb-1">Email or Username</label>
                <input type="text" name="login" id="login" value="{{ old('login') }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                       placeholder="admin" autocomplete="username" autofocus required>
                @error('login')
                    <p id="login-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" id="password"
                       class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                       placeholder="********" autocomplete="current-password" required>
                @error('password')
                    <p id="password-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
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
