<?php
// src/Web/Views/wa_chat.php
require_once __DIR__ . '/../../Core/Helpers.php';

$instance = $data['instance'] ?? null;
$contacts = $data['contacts'] ?? [];
$recentContacts = $data['recent_contacts'] ?? [];
$selectedContact = $data['selected_contact'] ?? null;
$contactInfo = $data['contact_info'] ?? null;
$messages = $data['messages'] ?? [];
$userPhone = $data['user_phone'] ?? '';
?>

<div class="h-screen bg-gray-100 dark:bg-gray-900 flex" x-data="whatsappChat()" x-init="init()">
    
    <!-- Left Column - Chat List -->
    <div class="w-1/3 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col">
        
        <!-- Header -->
        <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967c-.273-.099-.471-.148-.67.15c-.197.297-.767.966-.94 1.164c-.173.199-.347.223-.644.075c-.297-.15-1.255-.463-2.39-1.475c-.883-.788-1.48-1.761-1.653-2.059c-.173-.297-.018-.458.13-.606c.134-.133.298-.347.446-.52c.149-.174.198-.298.298-.497c.099-.198.05-.371-.025-.52c-.075-.149-.669-1.612-.916-2.207c-.242-.579-.487-.5-.669-.51c-.173-.008-.371-.01-.57-.01c-.198 0-.52.074-.792.372c-.272.297-1.04 1.016-1.04 2.479c0 1.462 1.065 2.875 1.213 3.074c.149.198 2.096 3.2 5.077 4.487c.709.306 1.262.489 1.694.625c.712.227 1.36.195 1.871.118c.571-.085 1.758-.719 2.006-1.413c.248-.694.248-1.289.173-1.413c-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214l-3.741.982l.998-3.648l-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884c2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.315"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h1 class="text-lg font-semibold text-gray-900 dark:text-white">WhatsApp</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">+<?= htmlspecialchars($userPhone) ?></p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-2">
                    <button 
                        @click="syncData()"
                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600"
                        title="Sync Data"
                    >
                        <svg class="w-5 h-5" :class="{'animate-spin': syncing}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                    
                    <button 
                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600"
                        title="Settings"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Search -->
        <div class="p-3 border-b border-gray-200 dark:border-gray-600">
            <div class="relative mb-3">
                <input 
                    type="text" 
                    x-model="searchQuery"
                    @input="searchContacts()"
                    placeholder="Search contacts..." 
                    class="w-full pl-10 pr-4 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                />
                <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            
            <!-- Filter Buttons -->
            <div class="flex flex-wrap gap-2">
                <button 
                    @click="setFilter('all')"
                    :class="currentFilter === 'all' ? 'bg-green-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-3 py-1 text-xs font-medium rounded-full transition-colors"
                >
                    All
                </button>
                <button 
                    @click="setFilter('unread')"
                    :class="currentFilter === 'unread' ? 'bg-green-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-3 py-1 text-xs font-medium rounded-full transition-colors"
                >
                    Unread
                </button>
                <button 
                    @click="setFilter('favorites')"
                    :class="currentFilter === 'favorites' ? 'bg-green-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-3 py-1 text-xs font-medium rounded-full transition-colors"
                >
                    Favorites
                </button>
                <button 
                    @click="setFilter('groups')"
                    :class="currentFilter === 'groups' ? 'bg-green-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-3 py-1 text-xs font-medium rounded-full transition-colors"
                >
                    Groups
                </button>
            </div>
        </div>
        
        <!-- Contact List -->
        <div class="flex-1 overflow-y-auto">
            <template x-for="contact in filteredContacts" :key="contact.phone_number">
                <div 
                    @click="selectContact(contact.phone_number)"
                    :class="selectedContactPhone === contact.phone_number ? 'bg-green-50 dark:bg-green-900/20 border-r-4 border-green-500' : 'hover:bg-gray-50 dark:hover:bg-gray-700'"
                    class="p-3 border-b border-gray-100 dark:border-gray-700 cursor-pointer transition-colors"
                >
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-gray-600 dark:text-gray-300 font-medium text-lg" x-text="getInitials(contact.name || contact.phone_number)"></span>
                        </div>
                        
                        <div class="ml-3 flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="contact.name || '+' + contact.phone_number"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="formatTime(contact.last_message_time)"></p>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <p class="text-sm text-gray-600 dark:text-gray-300 truncate" x-text="contact.last_message || 'No messages yet'"></p>
                                <div x-show="contact.unread_count > 0" class="bg-green-500 text-white text-xs rounded-full px-2 py-1 min-w-[20px] text-center" x-text="contact.unread_count"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            
            <!-- No contacts message -->
            <div x-show="filteredContacts.length === 0" class="text-center py-8">
                <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="text-gray-500 dark:text-gray-400">No contacts found</p>
            </div>
        </div>
    </div>
    
    <!-- Middle Column - Chat Window -->
    <div class="flex-1 flex flex-col" :class="showContactInfo ? 'w-2/3' : 'w-full'">
        
        <!-- Chat Header -->
        <div x-show="selectedContactPhone" class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                        <span class="text-gray-600 dark:text-gray-300 font-medium" x-text="getInitials(selectedContactName)"></span>
                    </div>
                    <div class="ml-3">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="selectedContactName"></h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">WhatsApp Contact</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-2">
                    <button class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600" title="Search">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                    <button 
                        @click="toggleContactInfo()"
                        :class="showContactInfo ? 'bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'"
                        class="p-2 hover:text-gray-700 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                        title="Contact Info"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </button>
                    <button class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600" title="More options">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Welcome Screen -->
        <div x-show="!selectedContactPhone" class="flex-1 flex items-center justify-center bg-gray-50 dark:bg-gray-800">
            <div class="text-center">
                <div class="w-24 h-24 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-12 h-12 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967c-.273-.099-.471-.148-.67.15c-.197.297-.767.966-.94 1.164c-.173.199-.347.223-.644.075c-.297-.15-1.255-.463-2.39-1.475c-.883-.788-1.48-1.761-1.653-2.059c-.173-.297-.018-.458.13-.606c.134-.133.298-.347.446-.52c.149-.174.198-.298.298-.497c.099-.198.05-.371-.025-.52c-.075-.149-.669-1.612-.916-2.207c-.242-.579-.487-.5-.669-.51c-.173-.008-.371-.01-.57-.01c-.198 0-.52.074-.792.372c-.272.297-1.04 1.016-1.04 2.479c0 1.462 1.065 2.875 1.213 3.074c.149.198 2.096 3.2 5.077 4.487c.709.306 1.262.489 1.694.625c.712.227 1.36.195 1.871.118c.571-.085 1.758-.719 2.006-1.413c.248-.694.248-1.289.173-1.413c-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214l-3.741.982l.998-3.648l-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884c2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.315"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">WhatsApp Web</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Select a contact to start messaging</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Your personal messages are end-to-end encrypted</p>
            </div>
        </div>
        
        <!-- Messages Container -->
        <div x-show="selectedContactPhone" class="flex-1 overflow-y-auto p-4 bg-gray-50 dark:bg-gray-800" x-ref="messagesContainer">
            <template x-for="message in messages" :key="message.id">
                <div :class="message.is_from_me ? 'flex justify-end mb-4' : 'flex justify-start mb-4'">
                    <div :class="message.is_from_me ? 'bg-green-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white'" class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg shadow">
                        <p class="text-sm" x-text="message.content"></p>
                        <div class="flex items-center justify-end mt-1 space-x-1">
                            <span class="text-xs opacity-75" x-text="formatTime(message.timestamp)"></span>
                            <template x-if="message.is_from_me">
                                <div class="flex items-center">
                                    <svg x-show="message.status === 'sent'" class="w-4 h-4 opacity-75" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <svg x-show="message.status === 'delivered'" class="w-4 h-4 opacity-75" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L8 8.586l3.293-3.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <svg x-show="message.status === 'read'" class="w-4 h-4 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L8 8.586l3.293-3.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        
        <!-- Message Input -->
        <div x-show="selectedContactPhone" class="p-4 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
            <form @submit.prevent="sendMessage()" class="flex items-end space-x-2">
                <div class="flex-1">
                    <textarea 
                        x-model="messageText"
                        @keydown.enter.prevent="sendMessage()"
                        @keydown.shift.enter.prevent="messageText += '\n'"
                        placeholder="Type a message..." 
                        rows="1"
                        class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-full text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none"
                        style="min-height: 40px; max-height: 120px;"
                    ></textarea>
                </div>
                
                <button 
                    type="submit"
                    :disabled="!messageText.trim() || sending"
                    class="p-2 bg-green-500 hover:bg-green-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-full transition-colors"
                >
                    <svg x-show="!sending" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    <svg x-show="sending" class="animate-spin w-6 h-6" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>
    
    <!-- Right Column - Contact Info Sidebar -->
    <div 
        x-show="showContactInfo && selectedContactPhone" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-x-full"
        x-transition:enter-end="opacity-100 transform translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-x-0"
        x-transition:leave-end="opacity-0 transform translate-x-full"
        class="w-1/3 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col"
    >
        <!-- Contact Info Header -->
        <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Contact Info</h3>
                <button 
                    @click="toggleContactInfo()"
                    class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Contact Info Content -->
        <div class="flex-1 overflow-y-auto p-4">
            <!-- Profile Section -->
            <div class="text-center mb-6">
                <div class="w-24 h-24 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-gray-600 dark:text-gray-300 font-medium text-2xl" x-text="getInitials(selectedContactName)"></span>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-1" x-text="selectedContactName"></h2>
                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="'+' + selectedContactPhone"></p>
            </div>
            
            <!-- About Section -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">About</h4>
                    <button class="text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                    Available
                </p>
            </div>
            
            <!-- Notes Section -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Notes</h4>
                    <button class="text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </button>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 p-3 rounded-lg min-h-[60px] flex items-center justify-center">
                    Click to add notes for this contact
                </div>
            </div>
            
            <!-- Media Preview -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Media, Links and Docs</h4>
                    <span class="text-xs text-gray-500 dark:text-gray-400">0</span>
                </div>
                <div class="grid grid-cols-3 gap-2 min-h-[80px]">
                    <div class="aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Settings Sections -->
            <div class="space-y-4">
                <!-- Starred Messages -->
                <button class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-yellow-500 mr-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Starred Messages</span>
                    </div>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                
                <!-- Mute Notifications -->
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" clip-rule="evenodd"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Mute Notifications</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                    </label>
                </div>
                
                <!-- Disappearing Messages -->
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Disappearing Messages</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Off</p>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function whatsappChat() {
    return {
        selectedContactPhone: '<?= htmlspecialchars($selectedContact ?? '') ?>',
        selectedContactName: '<?= htmlspecialchars($contactInfo['name'] ?? $selectedContact ?? '') ?>',
        messageText: '',
        sending: false,
        syncing: false,
        searchQuery: '',
        currentFilter: 'all',
        showContactInfo: false,
        contacts: <?= json_encode($recentContacts) ?>,
        messages: <?= json_encode(array_reverse($messages)) ?>,
        filteredContacts: [],
        messageCheckInterval: null,
        
        init() {
            this.filteredContacts = this.contacts;
            this.startMessagePolling();
            this.$nextTick(() => {
                this.scrollToBottom();
            });
        },
        
        selectContact(phoneNumber) {
            window.location.href = `/whatsapp/chat?contact=${phoneNumber}`;
        },
        
        async sendMessage() {
            if (!this.messageText.trim() || this.sending) return;
            
            this.sending = true;
            const message = this.messageText.trim();
            this.messageText = '';
            
            try {
                const response = await fetch('/api/whatsapp/send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        phone_number: this.selectedContactPhone,
                        message: message
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Add message to UI immediately
                    this.messages.push({
                        id: data.message_id || Date.now(),
                        content: message,
                        is_from_me: true,
                        timestamp: new Date().toISOString(),
                        status: 'sent'
                    });
                    
                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });
                } else {
                    alert('Failed to send message: ' + data.error);
                }
            } catch (error) {
                console.error('Send message error:', error);
                alert('Failed to send message. Please try again.');
            }
            
            this.sending = false;
        },
        
        async loadMessages() {
            if (!this.selectedContactPhone) return;
            
            try {
                const response = await fetch(`/api/whatsapp/messages?phone_number=${this.selectedContactPhone}&limit=50`);
                const data = await response.json();
                
                if (data.success) {
                    this.messages = data.messages;
                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });
                }
            } catch (error) {
                console.error('Load messages error:', error);
            }
        },
        
        async refreshConversations() {
            try {
                const response = await fetch('/api/whatsapp/conversations');
                const data = await response.json();
                
                if (data.success) {
                    this.contacts = data.conversations;
                    this.filterContacts();
                }
            } catch (error) {
                console.error('Refresh conversations error:', error);
            }
        },
        
        async syncData() {
            this.syncing = true;
            
            try {
                const response = await fetch('/api/whatsapp/sync', {
                    method: 'POST'
                });
                const data = await response.json();
                
                if (data.success) {
                    // Refresh conversations after sync
                    setTimeout(() => {
                        this.refreshConversations();
                    }, 2000);
                } else {
                    alert('Sync failed: ' + data.error);
                }
            } catch (error) {
                console.error('Sync error:', error);
                alert('Sync failed. Please try again.');
            }
            
            this.syncing = false;
        },
        
        searchContacts() {
            this.filterContacts();
        },
        
        filterContacts() {
            let filtered = this.contacts;
            
            // Apply search filter
            if (this.searchQuery.trim()) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(contact => 
                    (contact.name && contact.name.toLowerCase().includes(query)) ||
                    contact.contact_phone.includes(query)
                );
            }
            
            // Apply category filter
            switch (this.currentFilter) {
                case 'unread':
                    filtered = filtered.filter(contact => contact.unread_count > 0);
                    break;
                case 'favorites':
                    filtered = filtered.filter(contact => contact.is_favorite);
                    break;
                case 'groups':
                    filtered = filtered.filter(contact => contact.is_group);
                    break;
                case 'all':
                default:
                    // Already filtered by search, no additional filter needed
                    break;
            }
            
            this.filteredContacts = filtered;
        },
        
        setFilter(filter) {
            this.currentFilter = filter;
            this.filterContacts();
        },
        
        toggleContactInfo() {
            this.showContactInfo = !this.showContactInfo;
        },
        
        startMessagePolling() {
            // Check for new messages every 3 seconds
            this.messageCheckInterval = setInterval(() => {
                if (this.selectedContactPhone) {
                    this.loadMessages();
                }
                this.refreshConversations();
            }, 3000);
        },
        
        scrollToBottom() {
            if (this.$refs.messagesContainer) {
                this.$refs.messagesContainer.scrollTop = this.$refs.messagesContainer.scrollHeight;
            }
        },
        
        getInitials(name) {
            if (!name) return '?';
            
            if (name.startsWith('+')) {
                return name.slice(-2);
            }
            
            return name.split(' ')
                .map(word => word[0])
                .join('')
                .toUpperCase()
                .slice(0, 2);
        },
        
        formatTime(timestamp) {
            if (!timestamp) return '';
            
            const date = new Date(timestamp);
            const now = new Date();
            const diffTime = Math.abs(now - date);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays === 1) {
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            } else if (diffDays <= 7) {
                return date.toLocaleDateString([], { weekday: 'short' });
            } else {
                return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
            }
        },
        
        destroy() {
            if (this.messageCheckInterval) {
                clearInterval(this.messageCheckInterval);
            }
        }
    }
}
</script>