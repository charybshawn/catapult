{{-- Customer Profile Slideover Template - Matches reference design --}}
@props([
    'record' => null,
    'action' => 'view'
])

@php
    $user = $record;
    $isView = $action === 'view';
    $isEdit = $action === 'edit';
    $isCreate = $action === 'create';
@endphp

<div class="flex flex-col h-full bg-white dark:bg-gray-900">
    {{-- Header Section --}}
    <div class="flex-shrink-0 px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
        <div class="flex items-center justify-between">
            {{-- Customer Name/Title --}}
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    @if($user)
                        {{ $user->name }}
                    @else
                        {{ $isCreate ? 'New Customer' : 'Customer' }}
                    @endif
                </h1>
            </div>
            
            {{-- Action Buttons (if in view mode) --}}
            @if($isView && $user)
                <div class="flex items-center space-x-2">
                    <button type="button" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 bg-blue-50 rounded-md hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/30">
                        <x-filament::icon icon="heroicon-o-link" class="w-4 h-4 mr-1" />
                        Im
                    </button>
                    
                    <button type="button" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        <x-filament::icon icon="heroicon-o-ellipsis-horizontal" class="w-4 h-4" />
                    </button>
                    
                    <button type="button" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="w-4 h-4" />
                    </button>
                    
                    <button 
                        type="button" 
                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
                        x-on:click="
                            console.log('Edit button clicked for user {{ $user->id }}');
                            $wire.call('editUserModal', {{ $user->id }}).then(() => {
                                console.log('editUserModal call completed');
                            }).catch((error) => {
                                console.error('editUserModal call failed:', error);
                            });
                        "
                    >
                        Edit
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Content Section --}}
    <div class="flex-1 overflow-y-auto">
        @if($isView && $user)
            {{-- Stats Section --}}
            <div class="px-6 py-6 border-b border-gray-200 dark:border-gray-700">
                <div class="stats-grid">
                    {{-- Orders/Visits Count --}}
                    <div class="stat-item">
                        <div class="stat-label">
                            @if($user->orders()->count() > 0)
                                Orders
                            @else
                                Visits
                            @endif
                        </div>
                        <div class="stat-value">
                            {{ $user->orders()->count() ?? '0' }}
                        </div>
                    </div>
                    
                    {{-- Last Order --}}
                    <div class="stat-item">
                        <div class="stat-label">Last order</div>
                        <div class="stat-value">
                            @if($user->orders()->latest()->first())
                                {{ $user->orders()->latest()->first()->created_at->format('M j') }}
                            @else
                                Never
                            @endif
                        </div>
                    </div>
                    
                    {{-- First Order --}}
                    <div class="stat-item">
                        <div class="stat-label">First order</div>
                        <div class="stat-value">
                            @if($user->orders()->oldest()->first())
                                {{ $user->orders()->oldest()->first()->created_at->format('M Y') }}
                            @else
                                {{ $user->created_at->format('M Y') }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Contact Information Section --}}
            <div class="px-6 py-6 space-y-6">
                {{-- Phone --}}
                @if($user->phone)
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Phone</span>
                        <a href="tel:{{ $user->phone }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                            {{ $user->phone }}
                        </a>
                    </div>
                @endif

                {{-- Email --}}
                @if($user->email)
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Email</span>
                        <a href="mailto:{{ $user->email }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                            {{ $user->email }}
                        </a>
                    </div>
                @endif

                {{-- Customer Type --}}
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Customer Type</span>
                    <span class="text-sm text-gray-600 dark:text-gray-300">
                        {{ ucfirst($user->customer_type ?? 'retail') }}
                        @if($user->customer_type === 'wholesale' && $user->wholesale_discount_percentage)
                            ({{ $user->wholesale_discount_percentage }}% discount)
                        @endif
                    </span>
                </div>

                {{-- Marketing Status --}}
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Marketing</span>
                    <span class="text-sm text-gray-600 dark:text-gray-300">
                        @if($user->email_verified_at)
                            Subscribed
                        @else
                            Not subscribed
                        @endif
                    </span>
                </div>

                {{-- Address --}}
                @if($user->address || $user->city || $user->state)
                    <div class="flex justify-between items-start">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Address</span>
                        <div class="text-sm text-gray-600 dark:text-gray-300 text-right max-w-xs">
                            @if($user->address)
                                {{ $user->address }}<br>
                            @endif
                            @if($user->city || $user->state || $user->zip)
                                {{ $user->city }}@if($user->city && ($user->state || $user->zip)),@endif 
                                {{ $user->state }} {{ $user->zip }}
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Company Name (for wholesale) --}}
                @if($user->company_name)
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Company</span>
                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ $user->company_name }}</span>
                    </div>
                @endif
            </div>

            {{-- Show More Button --}}
            <div class="px-6 py-4">
                <button type="button" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium">
                    Show more
                </button>
            </div>

            {{-- Action Sections --}}
            <div class="px-6 py-6 space-y-6 border-t border-gray-200 dark:border-gray-700">
                {{-- Payment on file --}}
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Payment on file</span>
                    <button type="button" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium">
                        Add
                    </button>
                </div>

                {{-- Notes and files --}}
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Notes and files</span>
                    <button type="button" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium">
                        Add
                    </button>
                </div>
            </div>
        @else
            {{-- Edit/Create Form Content --}}
            <div class="px-6 py-6">
                @if($isEdit)
                    {{-- Custom edit form layout --}}
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Name Field --}}
                            <div class="md:col-span-2">
                                {{ $this->form->getComponent('name') }}
                            </div>
                            
                            {{-- Email and Phone --}}
                            <div>
                                {{ $this->form->getComponent('email') }}
                            </div>
                            <div>
                                {{ $this->form->getComponent('phone') }}
                            </div>
                            
                            {{-- Customer Type --}}
                            <div>
                                {{ $this->form->getComponent('customer_type') }}
                            </div>
                            
                            {{-- Company Name (conditional) --}}
                            <div>
                                {{ $this->form->getComponent('company_name') }}
                            </div>
                            
                            {{-- Wholesale Discount --}}
                            <div class="md:col-span-2">
                                {{ $this->form->getComponent('wholesale_discount_percentage') }}
                            </div>
                        </div>
                        
                        {{-- Address Section --}}
                        <div class="pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Address Information</h3>
                            <div class="space-y-4">
                                <div>
                                    {{ $this->form->getComponent('address') }}
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        {{ $this->form->getComponent('city') }}
                                    </div>
                                    <div>
                                        {{ $this->form->getComponent('state') }}
                                    </div>
                                    <div>
                                        {{ $this->form->getComponent('zip') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Standard form rendering for create --}}
                    {{ $slot ?? 'Form content will appear here' }}
                @endif
            </div>
        @endif
    </div>
</div>


<style>
    /* Stats Grid Layout - Force 3 columns */
    .stats-grid {
        display: grid !important;
        grid-template-columns: 1fr 1fr 1fr !important;
        gap: 2rem !important;
        width: 100%;
    }
    
    .stat-item {
        display: flex !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        text-align: left !important;
    }
    
    .stat-label {
        font-size: 0.875rem !important;
        color: rgb(107 114 128) !important;
        margin-bottom: 0.5rem !important;
        font-weight: 400 !important;
    }
    
    .stat-value {
        font-size: 1.75rem !important;
        font-weight: 700 !important;
        color: rgb(17 24 39) !important;
        line-height: 1 !important;
    }
    
    /* Dark mode support */
    .dark .stat-label {
        color: rgb(156 163 175) !important;
    }
    
    .dark .stat-value {
        color: rgb(243 244 246) !important;
    }
    
    /* Custom scrollbar styling */
    .overflow-y-auto {
        scrollbar-width: thin;
        scrollbar-color: rgb(156 163 175) transparent;
    }
    
    .overflow-y-auto::-webkit-scrollbar {
        width: 6px;
    }
    
    .overflow-y-auto::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .overflow-y-auto::-webkit-scrollbar-thumb {
        background-color: rgb(156 163 175);
        border-radius: 3px;
    }
    
    .overflow-y-auto::-webkit-scrollbar-thumb:hover {
        background-color: rgb(107 114 128);
    }
</style>