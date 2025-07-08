<?php
$pageTitle = 'Agents - OpenAI Webchat';
ob_start();
?>

<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-title-md font-bold text-gray-800 dark:text-white/90">
                    My Agents
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Create and manage your AI agents with custom tools and capabilities
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button
                    onclick="window.location.href='/chat'"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    Test in Chat
                </button>
                <button
                    id="create-agent-btn"
                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Create Agent
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3 mb-8">
        <!-- Total Agents -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-brand-100 dark:bg-brand-900/20">
                        <svg class="h-6 w-6 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                            Total Agents
                        </dt>
                        <dd class="text-2xl font-bold text-gray-900 dark:text-white/90">
                            <?= count($agents) ?>
                        </dd>
                    </dl>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex items-center text-sm">
                    <span class="text-gray-600 dark:text-gray-400">
                        <?= count(array_filter($agents, fn($a) => $a->isActive())) ?> active
                    </span>
                </div>
            </div>
        </div>

        <!-- Tools Available -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/20">
                        <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                            Tools Available
                        </dt>
                        <dd class="text-2xl font-bold text-gray-900 dark:text-white/90">
                            4
                        </dd>
                    </dl>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex items-center text-sm">
                    <span class="text-gray-600 dark:text-gray-400">
                        Math, Search, Weather, PDF
                    </span>
                </div>
            </div>
        </div>

        <!-- Agents with Tools -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/20">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                            Tool-Enabled
                        </dt>
                        <dd class="text-2xl font-bold text-gray-900 dark:text-white/90">
                            <?= count(array_filter($agents, fn($a) => !empty($a->getTools()))) ?>
                        </dd>
                    </dl>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex items-center text-sm">
                    <span class="text-green-600 dark:text-green-400 font-medium">
                        Enhanced capabilities
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Agents List -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100 dark:border-gray-800">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                    Your Agents
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage your custom AI agents and their capabilities
                </p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        <?= count($agents) ?> total
                    </span>
                </div>
            </div>
        </div>

        <?php if (empty($agents)): ?>
            <div class="text-center py-16 px-6">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                    <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="mt-6 text-lg font-medium text-gray-900 dark:text-white/90">
                    No agents created yet
                </h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                    Get started by creating your first AI agent with custom tools and capabilities.
                </p>
                <div class="mt-8">
                    <button
                        id="create-first-agent-btn"
                        class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-6 py-3 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create Your First Agent
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php foreach ($agents as $agent): ?>
                    <div class="group p-6 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4 min-w-0 flex-1">
                                <!-- Agent Avatar -->
                                <div class="flex-shrink-0">
                                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center shadow-theme-sm">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Agent Info -->
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h4 class="text-base font-semibold text-gray-900 dark:text-white/90 truncate">
                                            <?= htmlspecialchars($agent->getName()) ?>
                                        </h4>
                                        <?php if (!$agent->isActive()): ?>
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/20 dark:text-red-400">
                                                Inactive
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                                Active
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                                        <?= htmlspecialchars(substr($agent->getInstructions(), 0, 120)) ?><?= strlen($agent->getInstructions()) > 120 ? '...' : '' ?>
                                    </p>

                                    <!-- Agent Details -->
                                    <div class="flex items-center gap-6 text-xs text-gray-500 dark:text-gray-400">
                                        <div class="flex items-center gap-1">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span>Model: <?= htmlspecialchars($agent->getModel()) ?></span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            <span><?= count($agent->getTools()) ?> tools</span>
                                        </div>
                                        <?php if (!empty($agent->getTools())): ?>
                                            <div class="flex items-center gap-1">
                                                <span class="inline-flex items-center gap-1">
                                                    <?php foreach (array_slice($agent->getTools(), 0, 3) as $tool): ?>
                                                        <span class="inline-flex items-center rounded-md bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700 dark:bg-brand-900/20 dark:text-brand-400">
                                                            <?= htmlspecialchars($tool) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                    <?php if (count($agent->getTools()) > 3): ?>
                                                        <span class="text-gray-400">+<?= count($agent->getTools()) - 3 ?></span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <button
                                    onclick="testAgent(<?= $agent->getId() ?>)"
                                    class="inline-flex items-center justify-center rounded-lg bg-green-100 px-3 py-2 text-sm font-medium text-green-700 hover:bg-green-200 dark:bg-green-900/20 dark:text-green-400 dark:hover:bg-green-900/40 transition-colors"
                                    title="Test Agent">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M18 10h.01M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2z"></path>
                                    </svg>
                                    <span class="ml-1.5 hidden sm:block">Test</span>
                                </button>

                                <button
                                    onclick="editAgent(<?= $agent->getId() ?>)"
                                    class="inline-flex items-center justify-center rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors"
                                    title="Edit Agent">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    <span class="ml-1.5 hidden sm:block">Edit</span>
                                </button>

                                <button
                                    onclick="deleteAgent(<?= $agent->getId() ?>, '<?= htmlspecialchars($agent->getName()) ?>')"
                                    class="inline-flex items-center justify-center rounded-lg bg-red-100 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-200 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40 transition-colors"
                                    title="Delete Agent">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    <span class="ml-1.5 hidden sm:block">Delete</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Agent Modal -->
<div id="create-agent-modal" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border-0 w-11/12 md:w-3/4 lg:w-1/2 max-w-2xl shadow-theme-xl rounded-2xl bg-white dark:bg-gray-900">
        <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-800">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                    Create New Agent
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Design a custom AI agent with specific tools and capabilities
                </p>
            </div>
            <button onclick="closeCreateModal()" class="rounded-lg p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="create-agent-form" class="p-6 space-y-6">
            <!-- Agent Name -->
            <div>
                <label for="agent-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Agent Name
                </label>
                <input
                    type="text"
                    id="agent-name"
                    name="name"
                    required
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 shadow-theme-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-500"
                    placeholder="e.g., Code Assistant, Research Helper, Data Analyst">
            </div>

            <!-- Instructions -->
            <div>
                <label for="agent-instructions" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Instructions & Personality
                </label>
                <textarea
                    id="agent-instructions"
                    name="instructions"
                    rows="4"
                    required
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 shadow-theme-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-500"
                    placeholder="Describe what this agent should do, how it should behave, and its personality. Be specific about its role and capabilities..."></textarea>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Pro tip: Be specific about the agent's role, expertise, and communication style for best results.
                </p>
            </div>

            <!-- Model Selection -->
            <div>
                <label for="agent-model" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    AI Model
                </label>
                <select
                    id="agent-model"
                    name="model"
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-theme-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                    <option value="gpt-4o-mini">GPT-4O Mini - Fast & Cost-effective</option>
                    <option value="gpt-4o">GPT-4O - Most Capable</option>
                    <option value="gpt-4">GPT-4 - Legacy but Reliable</option>
                </select>
            </div>

            <!-- Tools Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    Available Tools
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="relative flex items-start p-3 border border-gray-200 rounded-lg hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/50 cursor-pointer transition-colors">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="tools" value="Math" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                        </div>
                        <div class="ml-3 min-w-0 flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Math Calculator</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Perform mathematical calculations safely</div>
                        </div>
                    </label>

                    <label class="relative flex items-start p-3 border border-gray-200 rounded-lg hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/50 cursor-pointer transition-colors">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="tools" value="Search" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                        </div>
                        <div class="ml-3 min-w-0 flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Web Search</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Search for current information on the web</div>
                        </div>
                    </label>

                    <label class="relative flex items-start p-3 border border-gray-200 rounded-lg hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/50 cursor-pointer transition-colors">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="tools" value="Weather" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                        </div>
                        <div class="ml-3 min-w-0 flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Weather Information</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Get weather information for any location</div>
                        </div>
                    </label>

                    <label class="relative flex items-start p-3 border border-gray-200 rounded-lg hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/50 cursor-pointer transition-colors">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="tools" value="ReadPDF" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                        </div>
                        <div class="ml-3 min-w-0 flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">PDF Reader</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Extract text and information from PDF files</div>
                        </div>
                    </label>
                </div>
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    Select the tools your agent should have access to. Tools enhance your agent's capabilities.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-100 dark:border-gray-800">
                <button
                    type="button"
                    onclick="closeCreateModal()"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                    Cancel
                </button>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Create Agent
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Agent Modal -->
<div id="edit-agent-modal" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border-0 w-11/12 md:w-3/4 lg:w-1/2 max-w-2xl shadow-theme-xl rounded-2xl bg-white dark:bg-gray-900">
        <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-800">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                    Edit Agent
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Modify your AI agent's settings and capabilities
                </p>
            </div>
            <button onclick="closeEditModal()" class="rounded-lg p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="edit-agent-form" class="p-6 space-y-6">
            <input type="hidden" id="edit-agent-id" name="id">

            <!-- Agent Name -->
            <div>
                <label for="edit-agent-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Agent Name
                </label>
                <input
                    type="text"
                    id="edit-agent-name"
                    name="name"
                    required
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 shadow-theme-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-500"
                    placeholder="e.g., Code Assistant, Research Helper, Data Analyst">
            </div>

            <!-- Instructions -->
            <div>
                <label for="edit-agent-instructions" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Instructions & Personality
                </label>
                <textarea
                    id="edit-agent-instructions"
                    name="instructions"
                    rows="4"
                    required
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 shadow-theme-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-500"
                    placeholder="Describe what this agent should do, how it should behave, and its personality..."></textarea>
            </div>

            <!-- Model Selection -->
            <div>
                <label for="edit-agent-model" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    AI Model
                </label>
                <select
                    id="edit-agent-model"
                    name="model"
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-theme-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                    <option value="gpt-4o-mini">GPT-4O Mini - Fast & Cost-effective</option>
                    <option value="gpt-4o">GPT-4O - Most Capable</option>
                    <option value="gpt-4">GPT-4 - Legacy but Reliable</option>
                </select>
            </div>

            <!-- Status Toggle -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    Agent Status
                </label>
                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="edit-agent-active" name="is_active" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-300 dark:peer-focus:ring-brand-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-brand-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">Active</span>
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Inactive agents won't appear in chat selection
                    </p>
                </div>
            </div>

            <!-- Tools Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    Available Tools
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="edit-tools-container">
                    <label class="relative flex items-start p-3 border border-gray-200 rounded-lg hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/50 cursor-pointer transition-colors">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="tools" value="Math" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                        </div>
                        <div class="ml-3 min-w-0 flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Math Calculator</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Perform mathematical calculations safely</div>
                        </div>
                    </label>

                    <label class="relative flex items-start p-3 border border-gray-200 rounded-lg hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/50 cursor-pointer transition-colors">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="tools" value="Search" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                        </div>
                        <div class="ml-3 min-w-0 flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Web Search</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Search for current information on the web</div>
                        </div>
                    </label>

                    <label class="relative flex items-start p-3 border border-gray-200 rounded-lg hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/50 cursor-pointer transition-colors">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="tools" value="Weather" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                        </div>
                        <div class="ml-3 min-w-0 flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Weather Information</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Get weather information for any location</div>
                        </div>
                    </label>

                    <label class="relative flex items-start p-3 border border-gray-200 rounded-lg hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/50 cursor-pointer transition-colors">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="tools" value="ReadPDF" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                        </div>
                        <div class="ml-3 min-w-0 flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">PDF Reader</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Extract text and information from PDF files</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-100 dark:border-gray-800">
                <button
                    type="button"
                    onclick="closeEditModal()"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                    Cancel
                </button>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const createAgentBtns = [
            document.getElementById('create-agent-btn'),
            document.getElementById('create-first-agent-btn')
        ].filter(Boolean);

        const createAgentModal = document.getElementById('create-agent-modal');
        const createAgentForm = document.getElementById('create-agent-form');
        const editAgentModal = document.getElementById('edit-agent-modal');
        const editAgentForm = document.getElementById('edit-agent-form');

        // Show create modal
        createAgentBtns.forEach(btn => {
            btn?.addEventListener('click', function() {
                createAgentModal.classList.remove('hidden');
                document.getElementById('agent-name').focus();
            });
        });

        // Handle create form submission
        createAgentForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(createAgentForm);
            const tools = Array.from(createAgentForm.querySelectorAll('input[name="tools"]:checked'))
                .map(checkbox => checkbox.value);

            const agentData = {
                name: formData.get('name'),
                instructions: formData.get('instructions'),
                model: formData.get('model'),
                tools: tools
            };

            const submitBtn = createAgentForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            try {
                submitBtn.disabled = true;
                submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Creating...
            `;

                const response = await fetch('/api/agents', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(agentData)
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.error || `Failed to create agent (${response.status})`);
                }

                showToast('success', `Agent "${agentData.name}" created successfully!`);
                setTimeout(() => window.location.reload(), 1000);

            } catch (error) {
                console.error('Error creating agent:', error);
                showToast('error', error.message || 'Failed to create agent. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });

        // Handle edit form submission
        editAgentForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(editAgentForm);
            const tools = Array.from(editAgentForm.querySelectorAll('input[name="tools"]:checked'))
                .map(checkbox => checkbox.value);

            const agentId = document.getElementById('edit-agent-id').value;
            const agentData = {
                name: formData.get('name'),
                instructions: formData.get('instructions'),
                model: formData.get('model'),
                is_active: document.getElementById('edit-agent-active').checked,
                tools: tools
            };

            const submitBtn = editAgentForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            try {
                submitBtn.disabled = true;
                submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Saving...
            `;

                const response = await fetch(`/api/agents/${agentId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(agentData)
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.error || `Failed to update agent (${response.status})`);
                }

                showToast('success', `Agent "${agentData.name}" updated successfully!`);
                setTimeout(() => window.location.reload(), 1000);

            } catch (error) {
                console.error('Error updating agent:', error);
                showToast('error', error.message || 'Failed to update agent. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    });

    function closeCreateModal() {
        const modal = document.getElementById('create-agent-modal');
        modal.classList.add('hidden');
        document.getElementById('create-agent-form').reset();
    }

    function closeEditModal() {
        const modal = document.getElementById('edit-agent-modal');
        modal.classList.add('hidden');
        document.getElementById('edit-agent-form').reset();
    }

    function testAgent(agentId) {
        window.location.href = `/chat?agent=${agentId}`;
    }

    async function editAgent(agentId) {
        try {
            // Fetch agent data
            const response = await fetch(`/api/agents/${agentId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to fetch agent data');
            }

            const agent = await response.json();

            // Populate form fields
            document.getElementById('edit-agent-id').value = agent.id;
            document.getElementById('edit-agent-name').value = agent.name;
            document.getElementById('edit-agent-instructions').value = agent.instructions;
            document.getElementById('edit-agent-model').value = agent.model;
            document.getElementById('edit-agent-active').checked = agent.is_active;

            // Clear all tool checkboxes first
            document.querySelectorAll('#edit-tools-container input[name="tools"]').forEach(checkbox => {
                checkbox.checked = false;
            });

            // Check the tools that this agent has
            if (agent.tools && Array.isArray(agent.tools)) {
                agent.tools.forEach(tool => {
                    const checkbox = document.querySelector(`#edit-tools-container input[value="${tool}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }

            // Show the modal
            document.getElementById('edit-agent-modal').classList.remove('hidden');
            document.getElementById('edit-agent-name').focus();

        } catch (error) {
            console.error('Error loading agent for editing:', error);
            showToast('error', 'Failed to load agent data for editing');
        }
    }

    async function deleteAgent(agentId, agentName) {
        const confirmed = await showConfirmDelete(
            agentName,
            'This action cannot be undone and will permanently remove the agent and all its configurations.'
        );

        if (!confirmed) return;

        try {
            const response = await fetch(`/api/agents/${agentId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || `Failed to delete agent (${response.status})`);
            }

            showToast('success', `Agent "${agentName}" deleted successfully.`);
            setTimeout(() => window.location.reload(), 1000);

        } catch (error) {
            console.error('Error deleting agent:', error);
            showToast('error', error.message || 'Failed to delete agent. Please try again.');
        }
    }

    // Close modals when clicking outside - REMOVED for agent modals
    // (We keep this behavior only for confirmation modal)

    // Enhanced keyboard support
    document.addEventListener('keydown', function(e) {
        const createModal = document.getElementById('create-agent-modal');
        const editModal = document.getElementById('edit-agent-modal');

        // Close modals on Escape key
        if (e.key === 'Escape') {
            if (!createModal.classList.contains('hidden')) {
                closeCreateModal();
            }
            if (!editModal.classList.contains('hidden')) {
                closeEditModal();
            }
        }

        // Submit forms on Ctrl/Cmd + Enter
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            if (!createModal.classList.contains('hidden')) {
                document.getElementById('create-agent-form').dispatchEvent(new Event('submit'));
            }
            if (!editModal.classList.contains('hidden')) {
                document.getElementById('edit-agent-form').dispatchEvent(new Event('submit'));
            }
        }
    });

    // Auto-resize textareas
    ['agent-instructions', 'edit-agent-instructions'].forEach(id => {
        const textarea = document.getElementById(id);
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 200) + 'px';
            });
        }
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?>