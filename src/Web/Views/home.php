<?php
$pageTitle = 'OpenAI Webchat - AI-Powered Conversations';
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $pageTitle ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <script>
    // Suppress Tailwind production warning
    const originalWarn = console.warn;
    console.warn = function(...args) {
      const message = args[0] ? args[0].toString() : "";
      if (message.includes("tailwindcss.com should not be used in production")) {
        return;
      }
      originalWarn.apply(console, args);
    };

    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              50: "#f0f9ff",
              100: "#e0f2fe",
              200: "#bae6fd",
              300: "#7dd3fc",
              400: "#38bdf8",
              500: "#0ea5e9",
              600: "#0284c7",
              700: "#0369a1",
              800: "#075985",
              900: "#0c4a6e",
              950: "#082f49",
            },
            gray: {
              50: "#f9fafb",
              100: "#f3f4f6",
              200: "#e5e7eb",
              300: "#d1d5db",
              400: "#9ca3af",
              500: "#6b7280",
              600: "#4b5563",
              700: "#374151",
              800: "#1f2937",
              900: "#111827",
            },
          },
          fontSize: {
            "title-sm": ["1.5rem", "2rem"],
            "title-md": ["1.875rem", "2.25rem"],
            "title-lg": ["2.25rem", "2.5rem"],
            "title-xl": ["3rem", "1"],
            "title-2xl": ["3.75rem", "1"],
          },
          boxShadow: {
            "theme-xs": "0 1px 2px 0 rgb(0 0 0 / 0.05)",
            "theme-sm": "0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)",
            "theme-md": "0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)",
            "theme-lg": "0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)",
            "theme-xl": "0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)",
          },
        },
      },
      darkMode: "class",
    };
  </script>
</head>

<body
  x-data="{ darkMode: false }"
  x-init="
        darkMode = JSON.parse(localStorage.getItem('darkMode') || 'false');
        $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)));
    "
  :class="{'dark': darkMode === true}"
  class="bg-gray-50 dark:bg-gray-900">
  <!-- Navigation -->
  <nav
    class="sticky top-0 z-50 bg-white/80 backdrop-blur-sm border-b border-gray-200 dark:bg-gray-900/80 dark:border-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <!-- Logo -->
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-brand-500 flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
          </div>
          <span class="text-xl font-bold text-gray-900 dark:text-white">OpenAI Webchat</span>
        </div>

        <!-- Right Side -->
        <div class="flex items-center gap-4">
          <!-- Dark Mode Toggle -->
          <button
            @click="darkMode = !darkMode"
            class="rounded-lg bg-gray-100 p-2 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
            title="Toggle dark mode">
            <svg
              x-show="!darkMode"
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
            <svg
              x-show="darkMode"
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
          </button>

          <!-- Auth Buttons -->
          <div class="flex items-center gap-3">
            <a
              href="/login"
              class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 transition-colors">
              Sign In
            </a>
            <a
              href="/register"
              class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition-colors">
              Get Started
            </a>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section
    class="relative overflow-hidden bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800">
    <!-- Background Pattern -->
    <div
      class="absolute inset-0 bg-grid-gray-900/[0.04] bg-[size:20px_20px] dark:bg-grid-white/[0.04]"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 lg:py-32">
      <div class="text-center">
        <!-- Hero Badge -->
        <div
          class="inline-flex items-center rounded-full bg-brand-100 px-3 py-1 text-sm font-medium text-brand-800 ring-1 ring-inset ring-brand-200 dark:bg-brand-900/20 dark:text-brand-300 dark:ring-brand-800 mb-8">
          <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 10V3L4 14h7v7l9-11h-7z"></path>
          </svg>
          Powered by OpenAI GPT-4
        </div>

        <!-- Hero Title -->
        <h1
          class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-6xl lg:text-7xl">
          AI-Powered
          <span
            class="text-transparent bg-clip-text bg-gradient-to-r from-brand-600 to-brand-400">
            Conversations
          </span>
        </h1>

        <!-- Hero Description -->
        <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
          Experience the future of AI conversation with our clean, maintainable OpenAI-powered
          webchat. Create dynamic agents, use powerful tools, and engage in meaningful
          conversations.
        </p>

        <!-- Hero CTA -->
        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
          <a
            href="/register"
            class="group inline-flex items-center justify-center rounded-lg bg-brand-600 px-6 py-3 text-base font-semibold text-white shadow-theme-sm hover:bg-brand-500 hover:shadow-theme-md focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition-all duration-200">
            Get Started Free
            <svg
              class="ml-2 h-5 w-5 group-hover:translate-x-1 transition-transform"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
            </svg>
          </a>
          <a
            href="/login"
            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-6 py-3 text-base font-semibold text-gray-900 shadow-theme-sm hover:bg-gray-50 hover:shadow-theme-md dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700 transition-all duration-200">
            Sign In
          </a>
        </div>

        <!-- Hero Stats -->
        <div class="mt-16 grid grid-cols-1 gap-8 sm:grid-cols-3 lg:grid-cols-3">
          <div class="flex flex-col items-center">
            <div
              class="flex h-12 w-12 items-center justify-center rounded-lg bg-brand-100 dark:bg-brand-900/20">
              <svg
                class="h-6 w-6 text-brand-600 dark:text-brand-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Smart Agents</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 text-center">
              Create custom AI agents with specialized tools
            </p>
          </div>

          <div class="flex flex-col items-center">
            <div
              class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/20">
              <svg
                class="h-6 w-6 text-purple-600 dark:text-purple-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
              Powerful Tools
            </h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 text-center">
              Math, search, weather, and file processing
            </p>
          </div>

          <div class="flex flex-col items-center">
            <div
              class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/20">
              <svg
                class="h-6 w-6 text-green-600 dark:text-green-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
              </svg>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
              Real-time Chat
            </h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 text-center">
              Instant responses with conversation history
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section class="py-24 bg-white dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-16">
        <h2
          class="text-base text-brand-600 font-semibold tracking-wide uppercase dark:text-brand-400">
          Features
        </h2>
        <p
          class="mt-2 text-3xl leading-8 font-bold tracking-tight text-gray-900 sm:text-4xl dark:text-white">
          Built for Modern AI Conversations
        </p>
        <p class="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto dark:text-gray-400">
          Our system combines simplicity with powerful features, following our core philosophy of
          clarity over complexity.
        </p>
      </div>

      <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 xl:gap-16">
        <!-- Dynamic Agents -->
        <div class="flex gap-6">
          <div class="flex-shrink-0">
            <div
              class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-100 dark:bg-brand-900/20">
              <svg
                class="h-6 w-6 text-brand-600 dark:text-brand-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
            </div>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Dynamic Agent Creation
            </h3>
            <p class="mt-2 text-base text-gray-600 dark:text-gray-400">
              Create custom AI agents on-the-fly with simple code. No complex factories or
              configurations - just clean, direct agent creation.
            </p>
          </div>
        </div>

        <!-- Tool System -->
        <div class="flex gap-6">
          <div class="flex-shrink-0">
            <div
              class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-100 dark:bg-purple-900/20">
              <svg
                class="h-6 w-6 text-purple-600 dark:text-purple-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
            </div>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Extensible Tool System
            </h3>
            <p class="mt-2 text-base text-gray-600 dark:text-gray-400">
              Equip your agents with powerful tools like code interpreters, web search, file
              readers, and more. Mix and match tools for any use case.
            </p>
          </div>
        </div>

        <!-- Clean Architecture -->
        <div class="flex gap-6">
          <div class="flex-shrink-0">
            <div
              class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-100 dark:bg-green-900/20">
              <svg
                class="h-6 w-6 text-green-600 dark:text-green-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
              </svg>
            </div>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Clean Architecture
            </h3>
            <p class="mt-2 text-base text-gray-600 dark:text-gray-400">
              Built with maintainability in mind. Clear separation between web and API
              controllers, shared business logic, and no unnecessary complexity.
            </p>
          </div>
        </div>

        <!-- OpenAI Integration -->
        <div class="flex gap-6">
          <div class="flex-shrink-0">
            <div
              class="flex h-12 w-12 items-center justify-center rounded-xl bg-orange-100 dark:bg-orange-900/20">
              <svg
                class="h-6 w-6 text-orange-600 dark:text-orange-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M13 10V3L4 14h7v7l9-11h-7z"></path>
              </svg>
            </div>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">OpenAI Powered</h3>
            <p class="mt-2 text-base text-gray-600 dark:text-gray-400">
              Leverages OpenAI's latest models with proper thread management, conversation
              history, and future support for assistants and tool calling.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Demo Section -->
  <section class="py-24 bg-gray-50 dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-16">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Try it Now</h2>
        <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">
          Experience the power of AI conversations with our demo account
        </p>
      </div>

      <div
        class="max-w-md mx-auto bg-white dark:bg-gray-900 rounded-2xl shadow-theme-lg p-8 border border-gray-200 dark:border-gray-700">
        <div class="text-center">
          <div
            class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-100 dark:bg-brand-900/20 mb-6">
            <svg
              class="h-8 w-8 text-brand-600 dark:text-brand-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
          </div>

          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Demo Account</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
            Test all features with our pre-configured demo account
          </p>

          <div class="space-y-3 text-sm">
            <div
              class="flex justify-between items-center py-2 px-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
              <span class="text-gray-600 dark:text-gray-400">Username:</span>
              <span class="font-mono font-medium text-gray-900 dark:text-white">demo</span>
            </div>
            <div
              class="flex justify-between items-center py-2 px-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
              <span class="text-gray-600 dark:text-gray-400">Password:</span>
              <span class="font-mono font-medium text-gray-900 dark:text-white">password</span>
            </div>
          </div>

          <a
            href="/login"
            class="mt-6 w-full inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-theme-sm hover:bg-brand-500 transition-colors">
            Try Demo Account
            <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
            </svg>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="py-24 bg-brand-600 dark:bg-brand-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <h2 class="text-3xl font-bold text-white sm:text-4xl">Ready to start chatting?</h2>
      <p class="mt-4 text-lg text-brand-100">
        Create your account and experience the future of AI conversations.
      </p>
      <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
        <a
          href="/register"
          class="inline-flex items-center justify-center rounded-lg bg-white px-6 py-3 text-base font-semibold text-brand-600 shadow-theme-sm hover:bg-gray-50 transition-colors">
          Get Started Free
          <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
          </svg>
        </a>
        <a
          href="/login"
          class="inline-flex items-center justify-center rounded-lg border-2 border-white px-6 py-3 text-base font-semibold text-white hover:bg-white hover:text-brand-600 transition-colors">
          Sign In
        </a>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="flex flex-col items-center justify-center text-center">
        <!-- Logo -->
        <div class="flex items-center gap-3 mb-4">
          <div class="w-8 h-8 rounded-lg bg-brand-500 flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
          </div>
          <span class="text-xl font-bold text-gray-900 dark:text-white">OpenAI Webchat</span>
        </div>

        <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md">
          AI-powered conversations made simple. Built with clarity over complexity.
        </p>

        <!-- Tech Stack -->
        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-8">
          <span>Powered by</span>
          <div class="flex items-center gap-2">
            <span class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded text-xs font-medium">PHP 8.4</span>
            <span class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded text-xs font-medium">OpenAI</span>
            <span class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded text-xs font-medium">MySQL</span>
            <span class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded text-xs font-medium">Docker</span>
          </div>
        </div>

        <!-- Copyright -->
        <div class="text-sm text-gray-500 dark:text-gray-400">
          <p>&copy; 2025 OpenAI Webchat. Built for learning and demonstration.</p>
        </div>
      </div>
    </div>
  </footer>
</body>

</html>

<?php
$content = ob_get_clean();
echo $content;
?>