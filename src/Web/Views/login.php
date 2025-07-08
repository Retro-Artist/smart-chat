<?php
// File 1: src/Web/Views/login.php - Updated to use shared layout
$pageTitle = 'Login - OpenAI Webchat';
ob_start();
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header Section -->
        <div class="text-center">
            <!-- User Icon -->
            <div class="flex justify-center mb-6">
                <div class="w-16 h-16 rounded-2xl bg-brand-500 flex items-center justify-center shadow-theme-md">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>

            <!-- Title and Subtitle -->
            <div class="space-y-2">
                <h1 class="text-title-lg font-bold text-gray-900 dark:text-white">
                    Welcome back
                </h1>
                <p class="text-theme-sm text-gray-600 dark:text-gray-400">
                    Sign in to your OpenAI Webchat account to continue your conversations
                </p>
            </div>
        </div>

        <!-- Error Alert -->
        <?php if (isset($error) && $error): ?>
            <div class="rounded-2xl border border-error-200 bg-error-50 p-4 shadow-theme-xs dark:border-error-800 dark:bg-error-900/20">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-error-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-theme-sm font-medium text-error-700 dark:text-error-400">
                            <?= htmlspecialchars($error) ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
            <div class="p-8">
                <form class="space-y-6" action="/login" method="POST">
                    <!-- Username Field -->
                    <div>
                        <label for="username" class="block text-theme-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Username
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <input
                                id="username"
                                name="username"
                                type="text"
                                required
                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg shadow-theme-xs placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-brand-400 dark:focus:border-brand-400 transition-colors"
                                placeholder="Enter your username">
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-theme-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Password
                        </label>
                        <div class="relative" x-data="{ showPassword: false }">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <input
                                id="password"
                                name="password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                class="block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg shadow-theme-xs placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-brand-400 dark:focus:border-brand-400 transition-colors"
                                placeholder="Enter your password">
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg x-show="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me and Forgot Password -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input
                                id="remember-me"
                                name="remember-me"
                                type="checkbox"
                                class="h-4 w-4 text-brand-600 focus:ring-brand-500 border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700">
                            <label for="remember-me" class="ml-2 block text-theme-sm text-gray-700 dark:text-gray-300">
                                Remember me
                            </label>
                        </div>

                        <div class="text-theme-sm">
                            <a href="#" class="font-medium text-brand-600 hover:text-brand-500 dark:text-brand-400 dark:hover:text-brand-300 transition-colors">
                                Forgot your password?
                            </a>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-theme-sm font-medium rounded-lg text-white bg-brand-500 hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 shadow-theme-sm transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98] dark:ring-offset-gray-800">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-brand-300 group-hover:text-brand-200 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                </svg>
                            </span>
                            Sign in to your account
                        </button>
                    </div>

                    <!-- Sign Up Link -->
                    <div class="text-center pt-4 border-t border-gray-100 dark:border-gray-700">
                        <p class="text-theme-sm text-gray-600 dark:text-gray-400">
                            Don't have an account?
                            <a href="/register" class="font-medium text-brand-600 hover:text-brand-500 dark:text-brand-400 dark:hover:text-brand-300 transition-colors">
                                Create one now
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Demo Account Info -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-800">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-theme-sm font-medium text-gray-900 dark:text-white">
                        Try the demo account
                    </h3>
                    <div class="mt-2 space-y-1">
                        <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                            <span class="font-medium">Username:</span> demo
                        </p>
                        <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                            <span class="font-medium">Password:</span> password
                        </p>
                    </div>
                    <p class="mt-2 text-theme-xs text-gray-600 dark:text-gray-400">
                        Perfect for testing all features without creating an account.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?>