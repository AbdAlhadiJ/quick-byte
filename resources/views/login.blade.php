<!-- resources/views/auth/login.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - QuickByte Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .header-gradient { background: linear-gradient(90deg, #ff7e5f, #feb47b); }
    </style>
    {!! RecaptchaV3::initJs() !!}

</head>
<body class="min-h-screen flex items-center justify-center bg-gray-100">
<div class="w-full max-w-md">
    <!-- Card -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="header-gradient p-6">
            <h1 class="text-white text-2xl font-bold text-center">QuickByte</h1>
            <p class="text-white text-center mt-1">Sign in to your account</p>
        </div>
        <!-- Form -->
        <div class="p-6">
            <form method="POST" action="{{ route('login') }}">
                {!! RecaptchaV3::field('login') !!}

                @csrf

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input id="email" name="email" type="email" required autofocus
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2" />
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input id="password" name="password" type="password" required
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2" />
                </div>

                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center text-sm">
                        <input type="checkbox" name="remember" class="form-checkbox h-4 w-4 text-indigo-600">
                        <span class="ml-2 text-gray-700">Remember me</span>
                    </label>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-red-400 to-red-600 text-white font-medium py-2 rounded-lg hover:opacity-90 transition">
                    Sign In
                </button>
            </form>
        </div>
    </div>
    <!-- Footer -->
    <p class="text-center text-sm text-gray-500 mt-4">
        © {{ date('Y') }} QuickByte. All rights reserved.
    </p>
</div>
<script>


</script>
</body>
</html>
