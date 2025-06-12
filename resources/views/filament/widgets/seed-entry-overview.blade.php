<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="fi-section-content-ctn">
        <div class="fi-section-content p-4">
            <div class="flex items-center gap-x-3">
                <div class="flex-1">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Seed Variety Details
                    </h3>
                </div>
                @if($seedEntry->catalog_number || $seedEntry->days_to_maturity)
                <div class="flex items-center gap-x-4 text-sm">
                    @if($seedEntry->catalog_number)
                        <span class="text-gray-600 dark:text-gray-300">
                            Catalog #{{ $seedEntry->catalog_number }}
                        </span>
                    @endif
                    @if($seedEntry->days_to_maturity)
                        <span class="text-gray-600 dark:text-gray-300">
                            {{ $seedEntry->days_to_maturity }} days to maturity
                        </span>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>