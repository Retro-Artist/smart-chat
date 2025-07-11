<?php
$pageTitle = 'Dashboard - OpenAI Webchat';
$page = 'dashboard'; // For sidebar active state
ob_start();
?>

<!-- Enhanced Dashboard with Charts -->
<div
  x-data="{ 
        chartPeriod: '7days',
        selectedMetric: 'conversations'
    }"
  class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">

  <!-- Page Header -->
  <div class="mb-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-title-md font-bold text-gray-800 dark:text-white/90">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Monitor your AI conversations, agent performance, and usage analytics.
        </p>
      </div>
      <div class="flex items-center gap-3">
        <!-- Time Period Selector -->
        <div class="flex items-center gap-2">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Period:</label>
          <select
            x-model="chartPeriod"
            @change="window.refreshCharts && window.refreshCharts(chartPeriod)"
            class="appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 pr-8 text-sm shadow-theme-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 ">
            <option value="24hours">Last 24 Hours</option>
            <option value="7days">Last 7 Days</option>
            <option value="30days">Last 30 Days</option>
            <option value="90days">Last 90 Days</option>
          </select>
        </div>

        <button
          onclick="window.location.href='/chat'"
          class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 ">
          <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
          </svg>
          New Chat
        </button>

        <button
          onclick="window.location.href='/agents'"
          class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 ">
          <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Create Agent
        </button>
      </div>
    </div>
  </div>

  <!-- Enhanced Stats Grid -->
  <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
    <!-- Total Conversations -->
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-brand-100 dark:bg-brand-900/20">
            <svg class="h-6 w-6 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
          </div>
        </div>
        <div class="ml-4 w-0 flex-1">
          <dl>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
              Total Conversations
            </dt>
            <dd class="text-2xl font-bold text-gray-900 dark:text-white/90">
              <?= $threadStats['total'] ?>
            </dd>
          </dl>
        </div>
      </div>
      <div class="mt-4">
        <div class="flex items-center text-sm">
          <span class="text-green-600 dark:text-green-400 font-medium">
            +<?= $threadStats['recent'] ?>
          </span>
          <span class="ml-2 text-gray-500 dark:text-gray-400">this week</span>
        </div>
      </div>
    </div>

    <!-- Active Agents -->
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/20">
            <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
          </div>
        </div>
        <div class="ml-4 w-0 flex-1">
          <dl>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
              Active Agents
            </dt>
            <dd class="text-2xl font-bold text-gray-900 dark:text-white/90">
              <?= $agentStats['active'] ?>
            </dd>
          </dl>
        </div>
      </div>
      <div class="mt-4">
        <div class="flex items-center text-sm">
          <span class="text-gray-600 dark:text-gray-400">
            <?= $agentStats['total'] ?> total created
          </span>
        </div>
      </div>
    </div>

    <!-- Total Messages -->
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/20">
            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
            </svg>
          </div>
        </div>
        <div class="ml-4 w-0 flex-1">
          <dl>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
              Messages Exchanged
            </dt>
            <dd class="text-2xl font-bold text-gray-900 dark:text-white/90">
              <?= array_sum(array_column($recentThreads, 'message_count')) ?>
            </dd>
          </dl>
        </div>
      </div>
      <div class="mt-4">
        <div class="flex items-center text-sm">
          <span class="text-green-600 dark:text-green-400 font-medium">
            <?= round(array_sum(array_column($recentThreads, 'message_count')) / max(count($recentThreads), 1), 1) ?>
          </span>
          <span class="ml-1 text-gray-500 dark:text-gray-400">avg per conversation</span>
        </div>
      </div>
    </div>

    <!-- Success Rate -->
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/20">
            <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
          </div>
        </div>
        <div class="ml-4 w-0 flex-1">
          <dl>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
              Agent Success Rate
            </dt>
            <dd class="text-2xl font-bold text-gray-900 dark:text-white/90">
              <?php
              $successRate = $runStats['total_runs'] > 0 ? round(($runStats['completed_runs'] / $runStats['total_runs']) * 100, 1) : 0;
              echo $successRate;
              ?>%
            </dd>
          </dl>
        </div>
      </div>
      <div class="mt-4">
        <div class="flex items-center text-sm">
          <?php if ($runStats['failed_runs'] > 0): ?>
            <span class="text-red-600 dark:text-red-400">
              <?= $runStats['failed_runs'] ?> failed runs
            </span>
          <?php else: ?>
            <span class="text-green-600 dark:text-green-400">All runs successful</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Charts Section -->
  <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-8">
    <!-- Conversations Over Time Chart -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex items-center justify-between px-6 py-5">
        <div>
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
            Conversation Activity
          </h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            Daily conversation volume over time
          </p>
        </div>
        <div class="flex items-center gap-2">
          <div class="flex items-center gap-1">
            <div class="w-3 h-3 rounded-full bg-brand-500"></div>
            <span class="text-xs text-gray-600 dark:text-gray-400">Conversations</span>
          </div>
        </div>
      </div>
      <div class="border-t border-gray-100 dark:border-gray-800 p-6">
        <canvas id="conversationsChart" width="400" height="200"></canvas>
      </div>
    </div>

    <!-- Agent Performance Chart -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex items-center justify-between px-6 py-5">
        <div>
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Agent Performance</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">Success rate by agent type</p>
        </div>
      </div>
      <div class="border-t border-gray-100 dark:border-gray-800 p-6">
        <canvas id="agentPerformanceChart" width="400" height="200"></canvas>
      </div>
    </div>
  </div>

  <!-- Usage Analytics and Tools -->
  <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <!-- Tool Usage Analytics -->
    <div class="lg:col-span-1">
      <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-6 py-5">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Tool Usage</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">Most popular agent tools</p>
        </div>
        <div class="border-t border-gray-100 dark:border-gray-800">
          <?php
          // Calculate tool usage from agents
          $toolUsage = [];
          foreach ($agents as $agent) {
            foreach ($agent->getTools() as $tool) {
              $toolUsage[$tool] = ($toolUsage[$tool] ?? 0) + 1;
            }
          }
          arsort($toolUsage);
          ?>

          <?php if (empty($toolUsage)): ?>
            <div class="px-6 py-8 text-center">
              <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
              <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No tools in use</p>
              <p class="text-xs text-gray-400 dark:text-gray-500">Create agents with tools to see analytics</p>
            </div>
          <?php else: ?>
            <div class="p-6">
              <div class="space-y-4">
                <?php
                $maxUsage = max($toolUsage);
                foreach (array_slice($toolUsage, 0, 5, true) as $tool => $usage):
                  $percentage = ($usage / $maxUsage) * 100;
                  $toolIcons = [
                    'Math' => 'M9 7h6m0 10v-3m-3+3.5h3m-6-6h.01M9 11h.01M11 11h.01M15 11h.01',
                    'Search' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
                    'Weather' => 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z',
                    'ReadPDF' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
                  ];
                  $iconPath = $toolIcons[$tool] ?? 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z';
                ?>
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                      <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-lg bg-brand-100 dark:bg-brand-900/20 flex items-center justify-center">
                          <svg class="w-4 h-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="<?= $iconPath ?>"></path>
                          </svg>
                        </div>
                      </div>
                      <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                          <?= htmlspecialchars($tool) ?>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                          <?= $usage ?> agent<?= $usage > 1 ? 's' : '' ?>
                        </p>
                      </div>
                    </div>
                    <div class="flex items-center gap-2">
                      <div class="w-16 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div
                          class="h-full bg-brand-500 rounded-full"
                          style="width: <?= $percentage ?>%"></div>
                      </div>
                      <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                        <?= round($percentage) ?>%
                      </span>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Recent Conversations (Enhanced) -->
    <div class="lg:col-span-2">
      <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center justify-between px-6 py-5">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Recent Activity</h3>
          <a href="/chat" class="text-sm font-medium text-brand-600 hover:text-brand-500 dark:text-brand-400">
            View all conversations
          </a>
        </div>
        <div class="border-t border-gray-100 dark:border-gray-800">
          <?php if (empty($recentThreads)): ?>
            <div class="px-6 py-12 text-center">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white/90">No conversations</h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Get started by creating your first conversation.
              </p>
              <div class="mt-6">
                <button
                  onclick="window.location.href='/chat'"
                  class="inline-flex items-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 ">
                  <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                  </svg>
                  Start Chatting
                </button>
              </div>
            </div>
          <?php else: ?>
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
              <?php foreach ($recentThreads as $thread): ?>
                <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                      <div class="flex-shrink-0">
                        <div class="h-10 w-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                          <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                          </svg>
                        </div>
                      </div>
                      <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white/90 truncate">
                          <?= htmlspecialchars($thread['title']) ?>
                        </p>
                        <div class="flex items-center gap-4 mt-1">
                          <p class="text-xs text-gray-500 dark:text-gray-400">
                            <?= $thread['message_count'] ?> messages
                          </p>
                          <p class="text-xs text-gray-500 dark:text-gray-400">
                            <?= date('M j, Y g:i A', strtotime($thread['created_at'])) ?>
                          </p>
                        </div>
                      </div>
                    </div>
                    <div class="flex items-center space-x-2">
                      <?php if ($thread['message_count'] > 5): ?>
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/20 dark:text-green-400">
                          Active
                        </span>
                      <?php endif; ?>
                      <a
                        href="/chat?thread=<?= $thread['id'] ?>"
                        class="text-brand-600 hover:text-brand-500 dark:text-brand-400 ">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"></path>
                        </svg>
                      </a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Insights Card -->
  <div class="mt-8">
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="px-6 py-5">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Quick Insights</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Key metrics and recommendations for your AI assistant usage
        </p>
      </div>
      <div class="border-t border-gray-100 dark:border-gray-800 p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Usage Trend -->
          <div class="text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/20 mb-3">
              <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
              </svg>
            </div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white/90">Usage Trend</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              <?php
              $trendPercent = $threadStats['recent'] > 0 ? '+' . round(($threadStats['recent'] / max($threadStats['total'] - $threadStats['recent'], 1)) * 100) : '0';
              echo $trendPercent;
              ?>% increase this week
            </p>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
              Your engagement is <?= $threadStats['recent'] > 3 ? 'growing steadily' : 'just getting started' ?>
            </p>
          </div>

          <!-- Agent Utilization -->
          <div class="text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900/20 mb-3">
              <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
            </div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white/90">Agent Efficiency</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              <?= $agentStats['with_tools'] ?> of <?= $agentStats['total'] ?> agents have tools
            </p>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
              <?php if ($agentStats['total'] == 0): ?>
                Create your first agent to get started
              <?php elseif ($agentStats['with_tools'] / $agentStats['total'] > 0.5): ?>
                Great tool utilization!
              <?php else: ?>
                Consider adding tools to boost capabilities
              <?php endif; ?>
            </p>
          </div>

          <!-- Conversation Quality -->
          <div class="text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/20 mb-3">
              <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white/90">Conversation Quality</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              Avg <?= round(array_sum(array_column($recentThreads, 'message_count')) / max(count($recentThreads), 1), 1) ?> messages per chat
            </p>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
              <?php
              $avgMessages = round(array_sum(array_column($recentThreads, 'message_count')) / max(count($recentThreads), 1), 1);
              if ($avgMessages > 10) {
                echo "Deep, engaging conversations";
              } elseif ($avgMessages > 5) {
                echo "Good conversation depth";
              } else {
                echo "Room for longer interactions";
              }
              ?>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Chart.js Integration with Enhanced Theme Support -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
  /* Prevent canvas elements from showing transitions during theme changes */
  canvas {
    transition: none !important;
  }
  canvas.theme-transition-disable {
    opacity: 1 !important;
  }
</style>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    let conversationsChart = null;
    let agentPerformanceChart = null;

    // Real data from PHP (no more Math.random!)
    const realConversationData = <?= json_encode($conversationChartData) ?>;
    const realAgentPerformanceData = <?= json_encode($agentPerformanceData) ?>;

    // Dynamic chart color system that responds to theme changes
    function getChartColors() {
      const isDarkMode = document.documentElement.classList.contains('dark');

      return {
        primary: '#0ea5e9',
        secondary: '#3b82f6',
        success: '#22c55e',
        warning: '#f59e0b',
        error: '#ef4444',
        purple: '#8b5cf6',
        orange: '#f97316',

        // Dynamic colors based on theme
        gray: isDarkMode ? '#6b7280' : '#9ca3af',
        lightGray: isDarkMode ? '#4b5563' : '#d1d5db',
        background: isDarkMode ? '#1f2937' : '#ffffff',
        cardBackground: isDarkMode ? '#111827' : '#ffffff',
        text: isDarkMode ? '#f3f4f6' : '#374151',
        mutedText: isDarkMode ? '#9ca3af' : '#6b7280',

        // Grid and border colors
        gridColor: isDarkMode ? '#374151' : '#f3f4f6',
        borderColor: isDarkMode ? '#4b5563' : '#e5e7eb',

        // Tooltip colors
        tooltipBg: isDarkMode ? '#374151' : '#ffffff',
        tooltipBorder: isDarkMode ? '#4b5563' : '#d1d5db',
      };
    }

    // Chart configuration generator with REAL data
    function createConversationsChart() {
      const colors = getChartColors();
      const ctx = document.getElementById('conversationsChart').getContext('2d');

      return new Chart(ctx, {
        type: 'line',
        data: {
          labels: realConversationData.labels, // Real labels from PHP
          datasets: [{
            label: 'Conversations',
            data: realConversationData.data, // Real data from PHP - NO MORE Math.random()!
            fill: true,
            tension: 0.4,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              backgroundColor: colors.tooltipBg,
              titleColor: colors.text,
              bodyColor: colors.text,
              borderColor: colors.tooltipBorder,
              borderWidth: 1,
              cornerRadius: 8,
              displayColors: false,
              padding: 12,
              titleFont: {
                size: 14,
                weight: 'bold'
              },
              bodyFont: {
                size: 13
              }
            }
          },
          scales: {
            x: {
              grid: {
                display: false
              },
              ticks: {
                color: colors.mutedText,
                font: {
                  size: 12,
                  weight: '500'
                },
                padding: 8
              },
              border: {
                color: colors.borderColor
              }
            },
            y: {
              beginAtZero: true,
              suggestedMax: 60,
              grid: {
                color: colors.gridColor,
                borderDash: [3, 3],
                drawBorder: false
              },
              ticks: {
                color: colors.mutedText,
                font: {
                  size: 12,
                  weight: '500'
                },
                stepSize: 1,
                padding: 8
              },
              border: {
                display: false
              }
            }
          },
          interaction: {
            intersect: false,
            mode: 'index'
          },
          elements: {
            point: {
              hoverRadius: 8
            }
          }
        }
      });
    }

    function createAgentPerformanceChart() {
      const colors = getChartColors();
      const ctx = document.getElementById('agentPerformanceChart').getContext('2d');

      // Use real data instead of PHP-generated random data
      const completedRuns = realAgentPerformanceData.completed;
      const failedRuns = realAgentPerformanceData.failed;
      const pendingRuns = realAgentPerformanceData.pending;

      return new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: ['Successful Runs', 'Failed Runs', 'Pending Runs'],
          datasets: [{
            data: [completedRuns, failedRuns, pendingRuns], // Real data!
            backgroundColor: [
              colors.success,
              colors.error,
              colors.warning
            ],
            borderColor: colors.background,
            borderWidth: 2,
            hoverOffset: 8
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                color: colors.text,
                font: {
                  size: 12
                },
                padding: 20,
                usePointStyle: true,
                pointStyle: 'circle'
              }
            },
            tooltip: {
              backgroundColor: colors.tooltipBg,
              titleColor: colors.text,
              bodyColor: colors.text,
              borderColor: colors.tooltipBorder,
              borderWidth: 1,
              cornerRadius: 8,
              callbacks: {
                label: function(context) {
                  const total = context.dataset.data.reduce((a, b) => a + b, 0);
                  const percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                  return `${context.label}: ${context.parsed} (${percentage}%)`;
                }
              }
            }
          },
          cutout: '60%',
          radius: '80%'
        }
      });
    }

    // Initialize charts
    function initCharts() {
      // Destroy existing charts if they exist
      if (conversationsChart) {
        conversationsChart.destroy();
      }
      if (agentPerformanceChart) {
        agentPerformanceChart.destroy();
      }

      // Create new charts with current theme colors
      conversationsChart = createConversationsChart();
      agentPerformanceChart = createAgentPerformanceChart();
    }

    // Update chart colors on theme change without recreating charts
    function handleThemeChange() {
      // Update immediately without delay to prevent visual glitches
      updateChartColors();
    }

    // Update chart colors without reinitializing (prevents animation restart)
    function updateChartColors() {
      const colors = getChartColors();
      
      // Add class to disable CSS transitions during update
      const chartContainers = document.querySelectorAll('#conversationsChart, #agentPerformanceChart');
      chartContainers.forEach(canvas => {
        canvas.classList.add('theme-transition-disable');
      });
      
      if (conversationsChart) {
        // Update conversation chart colors
        const dataset = conversationsChart.data.datasets[0];
        dataset.borderColor = colors.primary;
        dataset.backgroundColor = colors.primary + '20';
        dataset.pointBackgroundColor = colors.primary;
        dataset.pointBorderColor = colors.background;
        dataset.pointHoverBackgroundColor = colors.primary;
        dataset.pointHoverBorderColor = colors.background;
        
        // Update scales colors
        conversationsChart.options.scales.x.ticks.color = colors.mutedText;
        conversationsChart.options.scales.x.border.color = colors.borderColor;
        conversationsChart.options.scales.y.ticks.color = colors.mutedText;
        conversationsChart.options.scales.y.grid.color = colors.gridColor;
        
        // Update tooltip colors
        conversationsChart.options.plugins.tooltip.backgroundColor = colors.tooltipBg;
        conversationsChart.options.plugins.tooltip.titleColor = colors.text;
        conversationsChart.options.plugins.tooltip.bodyColor = colors.text;
        conversationsChart.options.plugins.tooltip.borderColor = colors.tooltipBorder;
        
        conversationsChart.update('none'); // Update without animation
      }
      
      if (agentPerformanceChart) {
        // Update agent performance chart colors
        const dataset = agentPerformanceChart.data.datasets[0];
        dataset.borderColor = colors.background;
        
        // Update legend colors
        agentPerformanceChart.options.plugins.legend.labels.color = colors.text;
        
        // Update tooltip colors
        agentPerformanceChart.options.plugins.tooltip.backgroundColor = colors.tooltipBg;
        agentPerformanceChart.options.plugins.tooltip.titleColor = colors.text;
        agentPerformanceChart.options.plugins.tooltip.bodyColor = colors.text;
        agentPerformanceChart.options.plugins.tooltip.borderColor = colors.tooltipBorder;
        
        agentPerformanceChart.update('none'); // Update without animation
      }
      
      // Re-enable transitions after a brief moment
      requestAnimationFrame(() => {
        chartContainers.forEach(canvas => {
          canvas.classList.remove('theme-transition-disable');
        });
      });
    }

    // Initialize charts on page load
    initCharts();

    // Listen for theme changes
    window.addEventListener('theme-changed', handleThemeChange);

    // Also listen for manual theme toggle (fallback)
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type === 'attributes' &&
          mutation.attributeName === 'class' &&
          mutation.target === document.documentElement) {
          handleThemeChange();
        }
      });
    });

    observer.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ['class']
    });

    // Utility function for period changes
    window.refreshCharts = function(period) {
      // TODO: Implement AJAX call to fetch new data for different periods
      // For now, just recreate with current data
      initCharts();
    };

    // Expose chart instances globally for debugging
    window.dashboardCharts = {
      conversations: () => conversationsChart,
      performance: () => agentPerformanceChart,
      refresh: initCharts
    };
  });

  // Watch for Alpine.js period changes and refresh charts
  document.addEventListener('alpine:updated', function() {
    // This is triggered when Alpine.js data changes
    // You could use this to refresh charts when chartPeriod changes
  });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?>