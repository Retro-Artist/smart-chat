<?php
$pageTitle = 'Register - OpenAI Webchat';
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        // Suppress Tailwind production warning
        const originalWarn = console.warn;
        console.warn = function(...args) {
            const message = args[0] ? args[0].toString() : '';
            if (message.includes('tailwindcss.com should not be used in production')) {
                return;
            }
            originalWarn.apply(console, args);
        };

        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand': {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                            950: '#082f49'
                        },
                        'gray': {
                            50: '#f9fafb',
                            100: '#f3f4f6',
                            200: '#e5e7eb',
                            300: '#d1d5db',
                            400: '#9ca3af',
                            500: '#6b7280',
                            600: '#4b5563',
                            700: '#374151',
                            800: '#1f2937',
                            900: '#111827'
                        },
                        'success': {
                            50: '#f0fdf4',
                            500: '#22c55e',
                        },
                        'error': {
                            50: '#fef2f2',
                            500: '#ef4444',
                        }
                    },
                    fontSize: {
                        'theme-xs': ['0.75rem', '1rem'],
                        'theme-sm': ['0.875rem', '1.25rem'],
                        'title-sm': ['1.5rem', '2rem'],
                        'title-md': ['1.875rem', '2.25rem'],
                        'title-lg': ['2.25rem', '2.5rem'],
                    },
                    boxShadow: {
                        'theme-xs': '0 1px 2px 0 rgb(0 0 0 / 0.05)',
                        'theme-sm': '0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)',
                        'theme-md': '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
                    }
                }
            },
            darkMode: 'class'
        };
    </script>
</head>

<body
    x-data="{ 
        darkMode: false, 
        showPassword: false,
        showConfirmPassword: false
    }"
    x-init="
        darkMode = JSON.parse(localStorage.getItem('darkMode') || 'false');
        $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)));
    "
    :class="{'dark bg-gray-900': darkMode === true}"
    class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header Section -->
            <div class="text-center">
                <!-- Logo -->
                <div class="flex justify-center mb-6">
                    <div class="w-16 h-16 rounded-2xl bg-brand-500 flex items-center justify-center shadow-theme-md">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Title and Subtitle -->
                <div class="space-y-2">
                    <h1 class="text-title-lg font-bold text-gray-900 dark:text-white">
                        Create your account
                    </h1>
                    <p class="text-theme-sm text-gray-600 dark:text-gray-400">
                        Join OpenAI Webchat and start having intelligent conversations
                    </p>
                </div>
            </div>

            <!-- Dark Mode Toggle -->
            <div class="flex justify-center">
                <button
                    @click="darkMode = !darkMode"
                    class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-theme-sm font-medium text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                    <svg x-show="!darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                    <svg x-show="darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span x-text="darkMode ? 'Light mode' : 'Dark mode'"></span>
                </button>
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

            <!-- Registration Form -->
            <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
                <div class="p-8">
                    <form class="space-y-6" action="/register" method="POST">
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
                                    placeholder="Choose a username">
                            </div>
                        </div>

                        <!-- Email Field -->
                        <div>
                            <label for="email" class="block text-theme-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Email address
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                    </svg>
                                </div>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    required
                                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg shadow-theme-xs placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-brand-400 dark:focus:border-brand-400 transition-colors"
                                    placeholder="Enter your email address">
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div>
                            <label for="password" class="block text-theme-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Password
                            </label>
                            <div class="relative">
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
                                    placeholder="Create a secure password (min 6 characters)">
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

                        <!-- Confirm Password Field -->
                        <div>
                            <label for="confirm_password" class="block text-theme-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Confirm password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <input
                                    id="confirm_password"
                                    name="confirm_password"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    required
                                    class="block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg shadow-theme-xs placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-brand-400 dark:focus:border-brand-400 transition-colors"
                                    placeholder="Confirm your password">
                                <button
                                    type="button"
                                    @click="showConfirmPassword = !showConfirmPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <svg x-show="!showConfirmPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <svg x-show="showConfirmPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button
                                type="submit"
                                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-theme-sm font-medium rounded-lg text-white bg-brand-500 hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 shadow-theme-sm transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98] dark:ring-offset-gray-800">
                                <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                    <svg class="h-5 w-5 text-brand-300 group-hover:text-brand-200 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </span>
                                Create your account
                            </button>
                        </div>

                        <!-- Sign In Link -->
                        <div class="text-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <p class="text-theme-sm text-gray-600 dark:text-gray-400">
                                Already have an account?
                                <a href="/login" class="font-medium text-brand-600 hover:text-brand-500 dark:text-brand-400 dark:hover:text-brand-300 transition-colors">
                                    Sign in here
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Security Info -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-800">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full bg-success-100 dark:bg-success-900/20 flex items-center justify-center">
                            <svg class="w-4 h-4 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-theme-sm font-medium text-gray-900 dark:text-white">
                            Your data is secure
                        </h3>
                        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                            We use industry-standard encryption to protect your conversations and personal information. Your data is never shared with third parties.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center">
                <p class="text-theme-xs text-gray-500 dark:text-gray-400">
                    By creating an account, you agree to our
                    <a href="#" class="text-brand-600 hover:text-brand-500 dark:text-brand-400 transition-colors">Terms of Service</a>
                    and
                    <a href="#" class="text-brand-600 hover:text-brand-500 dark:text-brand-400 transition-colors">Privacy Policy</a>
                </p>
            </div>
        </div>
    </div>
</body>

</html>

<?php
$content = ob_get_clean();
echo $content;
?>