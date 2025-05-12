<x-filament::page>
    <x-filament::section>
        <div class="space-y-6">
            <h2 class="text-xl font-bold tracking-tight">Update Existing Grows with Recipe Changes</h2>
            
            <p class="text-gray-500 dark:text-gray-400">
                This tool allows you to selectively update grows that are already in progress with updated recipe parameters.
                Use this when you've refined a recipe and want to apply those changes to existing grows.
            </p>
            
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 p-4 rounded-lg shadow-sm">
                <h3 class="font-medium text-yellow-800 dark:text-yellow-300 flex items-center gap-2">
                    <span class="h-5 w-5 text-yellow-600 dark:text-yellow-400 inline-flex">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    Important Notes
                </h3>
                <ul class="mt-2 ml-6 list-disc space-y-1 text-sm text-yellow-700 dark:text-yellow-300">
                    <li>This will modify data for existing grows that match your selected criteria.</li>
                    <li>Changes cannot be undone automatically - consider backing up your database before proceeding.</li>
                    <li>Only active (non-harvested) grows will be affected by these changes.</li>
                    <li>Stage-specific updates only affect crops currently in that stage.</li>
                </ul>
            </div>
        </div>
    </x-filament::section>
    
    {{ $this->form }}
</x-filament::page> 