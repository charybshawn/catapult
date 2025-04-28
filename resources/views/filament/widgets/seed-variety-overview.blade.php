@if($seedVariety)
<div class="flex flex-col gap-6 mb-6">
    <div class="grid gap-6 md:grid-cols-3">
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-y-1">
                <p class="text-sm text-gray-500 dark:text-gray-400">Variety Information</p>
                <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                    {{ $seedVariety->name }}
                </div>
                <div class="flex items-center gap-x-1 text-sm font-medium text-gray-500 dark:text-gray-400">
                    <span>Crop Type: {{ $seedVariety->crop_type }}</span>
                    <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-y-1">
                <p class="text-sm text-gray-500 dark:text-gray-400">Germination Rate</p>
                <div class="text-3xl font-semibold tracking-tight text-green-600 dark:text-green-400">
                    {{ $seedVariety->germination_rate ? "{$seedVariety->germination_rate}%" : 'Not specified' }}
                </div>
                <div class="flex items-center gap-x-1 text-sm font-medium text-gray-500 dark:text-gray-400">
                    <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.577 4.878a.75.75 0 01.919-.53l4.78 1.281a.75.75 0 01.531.919l-1.281 4.78a.75.75 0 01-1.449-.387l.81-3.022a19.407 19.407 0 00-5.594 5.203.75.75 0 01-1.139.093L7 10.06l-4.72 4.72a.75.75 0 01-1.06-1.061l5.25-5.25a.75.75 0 011.06 0l3.074 3.073a20.923 20.923 0 015.545-4.931l-3.042-.815a.75.75 0 01-.53-.919z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-y-1">
                <p class="text-sm text-gray-500 dark:text-gray-400">Days to Maturity</p>
                <div class="text-3xl font-semibold tracking-tight text-blue-600 dark:text-blue-400">
                    {{ $seedVariety->days_to_maturity ?? 'Not specified' }}
                </div>
                <div class="flex items-center gap-x-1 text-sm font-medium text-gray-500 dark:text-gray-400">
                    <svg class="h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>
@endif 