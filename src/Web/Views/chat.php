<?php
// src/Web/Views/chat.php - Debloated and optimized

$pageTitle = 'Chat - OpenAI Webchat';
$page = 'chat';
ob_start();

$messages = !empty($currentThread) ? Thread::getMessages($currentThread['id']) : [];
?>

<div x-data="chatApp()" class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3">
                    <div>
                        <h1 class="text-title-md font-bold text-gray-800 dark:text-white/90">
                            Chat
                        </h1>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            <span x-text="messageCount"></span> messages • AI-powered conversation
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button @click="toggleSidebar()" class="lg:hidden inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>

                <button @click="createNewThread()" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    New Chat
                </button>

                <?php if (!empty($availableAgents)): ?>
                    <select x-model="currentAgentId" @change="updateAgentStatus()" class="appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 pr-8 text-sm shadow-theme-xs focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        <option value="">Default</option>
                        <?php foreach ($availableAgents as $agent): ?>
                            <option value="<?= $agent->getId() ?>" <?= $selectedAgentId == $agent->getId() ? 'selected' : '' ?>>
                                <?= htmlspecialchars($agent->getName()) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
        <!-- Sidebar -->
        <div x-show="showSidebar" x-transition class="lg:col-span-1 space-y-6">
            <!-- Conversations -->
            <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Conversations</h3>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    <?php if (empty($threads)): ?>
                        <div class="px-6 py-8 text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">No conversations yet</p>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-gray-100 dark:divide-gray-800">
                            <?php foreach ($threads as $thread): ?>
                                <div @click="switchToThread(<?= $thread['id'] ?>)" class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer <?= $thread['id'] == ($currentThread['id'] ?? 0) ? 'bg-brand-50 dark:bg-brand-900/20' : '' ?>">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white/90 truncate">
                                        <?= htmlspecialchars($thread['title']) ?>
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <?= $thread['message_count'] ?? 0 ?> messages
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Test -->
            <?php if (!empty($availableAgents)): ?>
                <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-800">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Quick Test</h3>
                    </div>
                    <div class="px-6 py-4 space-y-3">
                        <?php foreach (array_slice($availableAgents, 0, 3) as $agent): ?>
                            <button @click="testAgent(<?= $agent->getId() ?>)" class="flex w-full items-center gap-3 rounded-lg border border-gray-200 bg-white p-3 text-left text-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700">
                                <div class="w-8 h-8 rounded-full bg-brand-100 dark:bg-brand-900/20 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-white truncate">
                                        <?= htmlspecialchars($agent->getName()) ?>
                                    </p>
                                </div>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Chat Area -->
        <div class="lg:col-span-3">
            <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] h-[calc(100vh-200px)] flex flex-col">
                <!-- Chat Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-brand-100 dark:bg-brand-900/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-white/90" x-text="currentThreadTitle">
                                <?= htmlspecialchars($currentThread['title'] ?? 'New Chat') ?>
                            </h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="agentStatus">
                                Default assistant
                            </p>
                        </div>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/20 dark:text-green-400">
                        <span class="mr-1.5 h-2 w-2 rounded-full bg-green-400"></span>
                        Online
                    </span>
                </div>

                <!-- Messages -->
                <div class="flex-1 overflow-y-auto p-6 space-y-6" id="messages-container">
                    <?php if (empty($messages)): ?>
                        <div class="text-center py-12">
                            <div class="w-16 h-16 rounded-2xl bg-brand-100 dark:bg-brand-900/20 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white/90 mb-2">Start a conversation</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Send a message to begin chatting</p>
                            <div class="flex flex-wrap justify-center gap-2">
                                <button @click="setQuickMessage('Hello! How can you help me today?')" class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg">
                                    👋 Say hello
                                </button>
                                <button @click="setQuickMessage('What can you do?')" class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg">
                                    🤔 Ask capabilities
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <?php if ($message['role'] === 'system') continue; ?>
                            <div class="flex items-start gap-4 <?= $message['role'] === 'user' ? 'flex-row-reverse' : '' ?>">
                                <div class="w-10 h-10 rounded-full <?= $message['role'] === 'user' ? 'bg-brand-500' : 'bg-gray-500' ?> flex items-center justify-center shadow-theme-xs">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $message['role'] === 'user' ? 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' : 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' ?>"/>
                                    </svg>
                                </div>
                                <div class="flex-1 max-w-3xl">
                                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-800 <?= $message['role'] === 'user' ? 'bg-brand-50 border-brand-200 dark:bg-brand-900/20 dark:border-brand-800' : '' ?>">
                                        <p class="text-gray-900 dark:text-gray-100 leading-relaxed m-0">
                                            <?= nl2br(htmlspecialchars($message['content'])) ?>
                                        </p>
                                    </div>
                                    <div class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 <?= $message['role'] === 'user' ? 'justify-end' : '' ?>">
                                        <span><?= $message['role'] === 'user' ? 'You' : 'Assistant' ?></span>
                                        <?php if (isset($message['timestamp'])): ?>
                                            <span>•</span>
                                            <span><?= date('g:i A', strtotime($message['timestamp'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Input -->
                <div class="border-t border-gray-100 dark:border-gray-800 p-4">
                    <form @submit.prevent="sendMessage()" class="flex items-end gap-2">
                        <!-- Attachment -->
                        <div x-data="{ showMenu: false }" class="relative">
                            <button @click="showMenu = !showMenu" @click.outside="showMenu = false" type="button" class="w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </button>
                            <div x-show="showMenu" x-transition class="absolute bottom-12 left-0 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-2 z-50 min-w-48">
                                <button @click="showMenu = false" type="button" class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
                                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Document
                                </button>
                                <button @click="showMenu = false" type="button" class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Image
                                </button>
                                <button @click="showMenu = false" type="button" class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
                                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    Camera
                                </button>
                            </div>
                        </div>

                        <!-- Message Input -->
                        <div class="flex-1">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm focus-within:border-brand-500 focus-within:shadow-md">
                                <textarea x-ref="messageInput" x-model="message" @input="autoResize()" @keydown="if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); sendMessage(); }" :disabled="isLoading" rows="1" class="block w-full resize-none bg-transparent px-4 py-3 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 border-0 focus:outline-none focus:ring-0 rounded-2xl" placeholder="Type a message..." style="min-height: 44px; max-height: 120px;"></textarea>
                            </div>
                        </div>

                        <!-- Send Button -->
                        <button :disabled="isLoading || !message.trim()" type="submit" class="w-10 h-10 rounded-full bg-brand-500 hover:bg-brand-600 disabled:bg-gray-400 flex items-center justify-center text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function chatApp() {
    return {
        currentThreadId: <?= $currentThread['id'] ?? 'null' ?>,
        currentAgentId: <?= $selectedAgentId ? $selectedAgentId : 'null' ?>,
        message: '',
        isLoading: false,
        messageCount: <?= count($messages) ?>,
        showSidebar: true,
        currentThreadTitle: '<?= htmlspecialchars($currentThread['title'] ?? 'New Chat') ?>',
        agentStatus: 'Default assistant',

        async sendMessage() {
            if (!this.message.trim() || this.isLoading) return;
            
            const messageText = this.message.trim();
            this.isLoading = true;
            
            this.addMessageToUI('user', messageText);
            this.message = '';
            this.autoResize();
            this.showTypingIndicator();
            
            try {
                const url = this.currentAgentId 
                    ? `/api/agents/${this.currentAgentId}/run`
                    : `/api/threads/${this.currentThreadId}/messages`;
                
                const body = this.currentAgentId 
                    ? { message: messageText, threadId: this.currentThreadId }
                    : { message: messageText };
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });
                
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                
                const result = await response.json();
                this.hideTypingIndicator();
                
                if (result.response) {
                    this.addMessageToUI('assistant', result.response);
                    this.messageCount += 2;
                }
            } catch (error) {
                this.hideTypingIndicator();
                this.addMessageToUI('assistant', 'Sorry, I encountered an error. Please try again.');
                console.error(error);
            } finally {
                this.isLoading = false;
                this.$refs.messageInput?.focus();
            }
        },

        addMessageToUI(role, content) {
            const container = document.getElementById('messages-container');
            if (!container) return;
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `flex items-start gap-4 ${role === 'user' ? 'flex-row-reverse' : ''}`;
            
            const time = new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            const avatarClass = role === 'user' ? 'bg-brand-500' : 'bg-gray-500';
            const messageClass = role === 'user' ? 'bg-brand-50 border-brand-200 dark:bg-brand-900/20 dark:border-brand-800' : '';
            const timeClass = role === 'user' ? 'justify-end' : '';
            const icon = role === 'user' 
                ? 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'
                : 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z';
            
            messageDiv.innerHTML = `
                <div class="w-10 h-10 rounded-full ${avatarClass} flex items-center justify-center shadow-theme-xs">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${icon}"/>
                    </svg>
                </div>
                <div class="flex-1 max-w-3xl">
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-800 ${messageClass}">
                        <p class="text-gray-900 dark:text-gray-100 leading-relaxed m-0">${this.escapeHtml(content).replace(/\n/g, '<br>')}</p>
                    </div>
                    <div class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 ${timeClass}">
                        <span>${role === 'user' ? 'You' : 'Assistant'}</span>
                        <span>•</span>
                        <span>${time}</span>
                    </div>
                </div>
            `;
            
            container.appendChild(messageDiv);
            this.scrollToBottom();
        },

        showTypingIndicator() {
            const container = document.getElementById('messages-container');
            if (!container) return;
            
            const typingDiv = document.createElement('div');
            typingDiv.id = 'typing-indicator';
            typingDiv.className = 'flex items-start gap-4';
            typingDiv.innerHTML = `
                <div class="w-10 h-10 rounded-full bg-gray-500 flex items-center justify-center shadow-theme-xs">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="flex-1 max-w-3xl">
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-800">
                        <div class="flex items-center space-x-1">
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                        </div>
                    </div>
                </div>
            `;
            
            container.appendChild(typingDiv);
            this.scrollToBottom();
        },

        hideTypingIndicator() {
            document.getElementById('typing-indicator')?.remove();
        },

        autoResize() {
            const input = this.$refs.messageInput;
            if (!input) return;
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 120) + 'px';
        },

        scrollToBottom() {
            setTimeout(() => {
                const container = document.getElementById('messages-container');
                if (container) container.scrollTop = container.scrollHeight;
            }, 100);
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        toggleSidebar() {
            this.showSidebar = !this.showSidebar;
        },

        updateAgentStatus() {
            if (this.currentAgentId) {
                const select = this.$el.querySelector('select');
                const option = select?.querySelector(`option[value="${this.currentAgentId}"]`);
                this.agentStatus = option ? `Using: ${option.textContent}` : 'Default assistant';
            } else {
                this.agentStatus = 'Default assistant';
            }
        },

        setQuickMessage(msg) {
            this.message = msg;
            this.$refs.messageInput?.focus();
            this.autoResize();
        },

        async createNewThread() {
            try {
                const response = await fetch('/api/threads', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ title: 'New Chat' })
                });
                
                if (!response.ok) throw new Error('Failed to create thread');
                
                const thread = await response.json();
                window.location.href = `/chat?thread=${thread.id}`;
            } catch (error) {
                console.error(error);
            }
        },

        switchToThread(threadId) {
            window.location.href = `/chat?thread=${threadId}`;
        },

        testAgent(agentId) {
            this.currentAgentId = agentId;
            this.updateAgentStatus();
            
            const messages = [
                "Hello! Test your capabilities.",
                "What tools do you have?",
                "Help me with a task.",
                "Tell me your specialties."
            ];
            
            this.setQuickMessage(messages[Math.floor(Math.random() * messages.length)]);
        }
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?>