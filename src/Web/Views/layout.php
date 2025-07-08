<?php
// src/Web/Views/layout.php - Sidebar-focused layout with header controls moved to sidebar
?>
<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'OpenAI Webchat') ?></title>
  <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">

  <!-- Critical CSS for preventing flash -->
  <style>
    /* Prevent flash of unstyled content */
    .theme-transition-disable * {
      transition: none !important;
    }
    
    /* Hide content until Alpine.js loads to prevent layout shift */
    [x-cloak] { 
      display: none !important; 
    }
  </style>

  <!-- Theme Manager - Load FIRST to prevent flickering -->
  <script src="/assets/js/theme-manager.js"></script>

  <!-- Tailwind CSS with JIT -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      content: ["./src/**/*.{html,js,php}"],
      theme: {
        extend: {
          colors: {
            brand: {
              50: "#eff6ff",
              100: "#dbeafe",
              200: "#bfdbfe",
              300: "#93c5fd",
              400: "#60a5fa",
              500: "#3b82f6",
              600: "#2563eb",
              700: "#1d4ed8",
              800: "#1e40af",
              900: "#1e3a8a",
            },
            success: {
              50: "#ecfdf5",
              100: "#d1fae5",
              200: "#a7f3d0",
              300: "#6ee7b7",
              400: "#34d399",
              500: "#10b981",
              600: "#059669",
              700: "#047857",
              800: "#065f46",
              900: "#064e3b",
            },
            warning: {
              50: "#fffbeb",
              100: "#fef3c7",
              200: "#fde68a",
              300: "#fcd34d",
              400: "#fbbf24",
              500: "#f59e0b",
              600: "#d97706",
              700: "#b45309",
              800: "#92400e",
              900: "#78350f",
            },
            error: {
              50: "#fef2f2",
              100: "#fee2e2",
              200: "#fecaca",
              300: "#fca5a5",
              400: "#f87171",
              500: "#ef4444",
              600: "#dc2626",
              700: "#b91c1c",
              800: "#991b1b",
              900: "#7f1d1d",
            },
          },
          fontSize: {
            "theme-xs": ["0.75rem", "1rem"],
            "theme-sm": ["0.875rem", "1.25rem"],
            "theme-base": ["1rem", "1.5rem"],
            "theme-lg": ["1.125rem", "1.75rem"],
            "theme-xl": ["1.25rem", "1.75rem"],
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
          animation: {
            "fade-in": "fadeIn 0.5s ease-in-out",
            "slide-in": "slideIn 0.3s ease-out",
            "bounce-slow": "bounce 2s infinite",
          },
          keyframes: {
            fadeIn: {
              "0%": { opacity: "0" },
              "100%": { opacity: "1" },
            },
            slideIn: {
              "0%": { transform: "translateX(-100%)" },
              "100%": { transform: "translateX(0)" },
            },
          },
        },
      },
      darkMode: "class",
    };
  </script>

  <!-- Alpine.js -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <!-- Custom CSS -->
  <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body
  x-data="{ 
        sidebarToggle: false, 
        loaded: true
    }"
  x-init="
        // Remove transition disable after Alpine loads
        document.body.classList.remove('theme-transition-disable');
    "
  class="bg-gray-50 dark:bg-gray-900 min-h-screen theme-transition-disable"
  x-cloak>

  <!-- Page Wrapper -->
  <div class="flex h-screen overflow-hidden">
    
    <!-- Sidebar -->
    <?php if (isset($_SESSION['user_id'])): ?>
      <aside
        :class="sidebarToggle ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        class="fixed left-0 top-0 z-50 flex h-screen w-[290px] flex-col overflow-y-hidden border-r border-gray-200 bg-white duration-300 ease-linear dark:border-gray-800 dark:bg-black lg:static lg:translate-x-0"
        @click.outside="sidebarToggle = false">
        
        <!-- Sidebar Header with Theme Toggle -->
        <div class="flex items-center justify-between gap-2 px-5 pb-7 pt-8">
          <a href="/dashboard" class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-brand-500 flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
              </svg>
            </div>
            <span class="text-xl font-bold text-gray-900 dark:text-white">OpenAI Chat</span>
          </a>
          
          <!-- Theme Toggle (moved from header) - Always visible -->
          <button
            @click="$store.theme.toggle()"
            class="rounded-lg bg-gray-100 dark:bg-gray-700 p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
            title="Toggle dark mode">
            <svg x-show="!$store.theme.darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
            <svg x-show="$store.theme.darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
          </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-5 py-4 overflow-y-auto">
          <div class="space-y-2">
            <!-- Menu Group -->
            <div class="mb-6">
              <h3 class="mb-3 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Menu
              </h3>
              <ul class="space-y-1">
                <li>
                  <a
                    href="/dashboard"
                    class="group flex items-center rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white">
                    <svg
                      class="mr-3 h-5 w-5 flex-shrink-0"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Dashboard
                  </a>
                </li>

                <li>
                  <a
                    href="/chat"
                    class="group flex items-center rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white">
                    <svg
                      class="mr-3 h-5 w-5 flex-shrink-0"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    Chat
                  </a>
                </li>

                <li>
                  <a
                    href="/agents"
                    class="group flex items-center rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white">
                    <svg
                      class="mr-3 h-5 w-5 flex-shrink-0"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                    Agents
                  </a>
                </li>
              </ul>
            </div>

            <!-- Theme Toggle for Desktop -->
            <!-- Removed duplicate theme toggle - now only in header corner -->

            <!-- User Profile Section (moved from header) -->
            <div class="border-t border-gray-200 dark:border-gray-800 pt-6">
              <h3 class="mb-3 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Account
              </h3>
              <ul class="space-y-1">
                <li>
                  <!-- User Menu with Dropdown -->
                  <div class="relative" x-data="{ open: false }">
                    <button
                      @click="open = !open"
                      class="group flex items-center w-full rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white">
                      <div class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 dark:bg-brand-900/20">
                        <span class="text-sm font-medium text-brand-600 dark:text-brand-400">
                          <?= strtoupper(substr($_SESSION['username'] ?? 'User', 0, 1)) ?>
                        </span>
                      </div>
                      <div class="ml-3 min-w-0 flex-1 text-left">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                          <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Online</p>
                      </div>
                      <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                      </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div
                      x-show="open"
                      @click.outside="open = false"
                      x-transition:enter="transition ease-out duration-100"
                      x-transition:enter-start="transform opacity-0 scale-95"
                      x-transition:enter-end="transform opacity-100 scale-100"
                      x-transition:leave="transition ease-in duration-75"
                      x-transition:leave-start="transform opacity-100 scale-100"
                      x-transition:leave-end="transform opacity-0 scale-95"
                      class="absolute bottom-full left-0 mb-2 w-full rounded-lg bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                      <div class="py-1">
                        <button class="w-full text-left block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                          Profile Settings
                        </button>
                        <a href="/logout" class="block px-4 py-2 text-sm text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                          Sign out
                        </a>
                      </div>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </nav>
      </aside>
    <?php endif; ?>

    <!-- Main Content Area -->
    <div class="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden">
      
      <!-- Mobile Sidebar Toggle -->
      <?php if (isset($_SESSION['user_id'])): ?>
        <button
          @click="sidebarToggle = !sidebarToggle"
          class="fixed top-4 left-4 z-40 lg:hidden rounded-lg bg-white p-2 shadow-theme-sm dark:bg-gray-800">
          <svg
            class="w-6 h-6 text-gray-600 dark:text-gray-300"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </button>
      <?php endif; ?>

      <!-- Page Content -->
      <main class="flex-1 overflow-y-auto">
        <?= $content ?>
      </main>
    </div>
  </div>

  <!-- Success/Error Messages -->
  <?php if (isset($_SESSION['success'])): ?>
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" 
         class="fixed top-4 right-4 z-50 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-lg">
      <div class="flex items-center justify-between">
        <span><?= htmlspecialchars($_SESSION['success']) ?></span>
        <button @click="show = false" class="ml-2 text-green-500 hover:text-green-700">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    </div>
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" 
         class="fixed top-4 right-4 z-50 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded shadow-lg">
      <div class="flex items-center justify-between">
        <span><?= htmlspecialchars($_SESSION['error']) ?></span>
        <button @click="show = false" class="ml-2 text-red-500 hover:text-red-700">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    </div>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

<script>
// Global Toast Notification System
window.showToast = function(type, message, duration = 5000) {
    // Remove any existing toasts
    const existingToast = document.getElementById('dynamic-toast');
    if (existingToast) {
        existingToast.remove();
    }

    // Create toast element
    const toast = document.createElement('div');
    toast.id = 'dynamic-toast';
    toast.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;
    
    // Set colors based on type
    if (type === 'success') {
        toast.className += ' bg-green-100 border border-green-400 text-green-700 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400';
    } else if (type === 'error') {
        toast.className += ' bg-red-100 border border-red-400 text-red-700 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400';
    } else if (type === 'warning') {
        toast.className += ' bg-yellow-100 border border-yellow-400 text-yellow-700 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-400';
    } else {
        toast.className += ' bg-blue-100 border border-blue-400 text-blue-700 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-400';
    }

    // Create toast content
    toast.innerHTML = `
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium">${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-current hover:opacity-70">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;

    // Add to document
    document.body.appendChild(toast);

    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);

    // Auto-remove after duration
    setTimeout(() => {
        if (toast.parentElement) {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 300);
        }
    }, duration);
};

// Global Confirmation Modal System
window.showConfirmDelete = function(itemName, description = '') {
    return new Promise((resolve) => {
        // Remove any existing confirmation modal
        const existingModal = document.getElementById('confirmation-modal');
        if (existingModal) {
            existingModal.remove();
        }

        // Create modal HTML
        const modalHtml = `
            <div id="confirmation-modal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border-0 w-11/12 md:w-1/3 max-w-md shadow-xl rounded-xl bg-white dark:bg-gray-900">
                    <div class="text-center">
                        <!-- Warning Icon -->
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/20 mb-4">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        
                        <!-- Title -->
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            Delete "${itemName}"?
                        </h3>
                        
                        <!-- Description -->
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                            ${description || 'This action cannot be undone.'}
                        </p>
                        
                        <!-- Buttons -->
                        <div class="flex space-x-3 justify-center">
                            <button id="cancel-delete" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                                Cancel
                            </button>
                            <button id="confirm-delete" class="inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add modal to document
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        const modal = document.getElementById('confirmation-modal');
        const cancelBtn = document.getElementById('cancel-delete');
        const confirmBtn = document.getElementById('confirm-delete');

        // Handle cancel
        const handleCancel = () => {
            modal.remove();
            resolve(false);
        };

        // Handle confirm
        const handleConfirm = () => {
            modal.remove();
            resolve(true);
        };

        // Add event listeners
        cancelBtn.addEventListener('click', handleCancel);
        confirmBtn.addEventListener('click', handleConfirm);

        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                handleCancel();
            }
        });

        // Close on Escape key
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                handleCancel();
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);

        // Focus the confirm button
        setTimeout(() => {
            confirmBtn.focus();
        }, 100);
    });
};
</script>

</body>
</html>