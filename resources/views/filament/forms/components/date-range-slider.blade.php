<div class="space-y-4">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        Time Period: <span id="slider-value-{{ $statePath }}">{{ $labels[$value] ?? $value . ' Months' }}</span>
    </label>
    
    <div class="relative">
        <input 
            type="range" 
            id="slider-{{ $statePath }}"
            name="{{ $statePath }}"
            min="{{ $min }}" 
            max="{{ $max }}" 
            step="{{ $step }}"
            value="{{ $value }}"
            wire:model.live="dateRangeMonths"
            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700 slider"
            style="background: linear-gradient(to right, #3b82f6 0%, #3b82f6 {{ ($value / $max) * 100 }}%, #d1d5db {{ ($value / $max) * 100 }}%, #d1d5db 100%);"
        >
        
        <!-- Slider tick marks -->
        <div class="flex justify-between text-xs text-gray-500 mt-2">
            @foreach($labels as $months => $label)
                <span 
                    class="cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 {{ $value == $months ? 'font-semibold text-blue-600' : '' }}"
                    onclick="updateSlider('{{ $statePath }}', {{ $months }})"
                >
                    {{ $label }}
                </span>
            @endforeach
        </div>
    </div>
</div>

<style>
    .slider::-webkit-slider-thumb {
        appearance: none;
        height: 20px;
        width: 20px;
        border-radius: 50%;
        background: #3b82f6;
        cursor: pointer;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .slider::-moz-range-thumb {
        height: 20px;
        width: 20px;
        border-radius: 50%;
        background: #3b82f6;
        cursor: pointer;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
</style>

<script>
    function updateSlider(statePath, value) {
        const slider = document.getElementById('slider-' + statePath);
        const label = document.getElementById('slider-value-' + statePath);
        
        slider.value = value;
        
        // Trigger Livewire update
        @this.set('dateRangeMonths', value);
        
        // Update visual feedback
        updateSliderBackground(slider);
        updateSliderLabel(statePath, value);
    }
    
    function updateSliderBackground(slider) {
        const value = slider.value;
        const max = slider.max;
        const percentage = (value / max) * 100;
        slider.style.background = `linear-gradient(to right, #3b82f6 0%, #3b82f6 ${percentage}%, #d1d5db ${percentage}%, #d1d5db 100%)`;
    }
    
    function updateSliderLabel(statePath, value) {
        const labels = @json($labels);
        const labelElement = document.getElementById('slider-value-' + statePath);
        labelElement.textContent = labels[value] || value + ' Months';
        
        // Update tick mark highlighting
        document.querySelectorAll('[onclick*="' + statePath + '"]').forEach(tick => {
            tick.classList.remove('font-semibold', 'text-blue-600');
            if (tick.onclick.toString().includes(value)) {
                tick.classList.add('font-semibold', 'text-blue-600');
            }
        });
    }
    
    // Initialize slider on page load
    document.addEventListener('DOMContentLoaded', function() {
        const slider = document.getElementById('slider-{{ $statePath }}');
        if (slider) {
            updateSliderBackground(slider);
            
            slider.addEventListener('input', function() {
                updateSliderBackground(this);
                const labels = @json($labels);
                const labelElement = document.getElementById('slider-value-{{ $statePath }}');
                labelElement.textContent = labels[this.value] || this.value + ' Months';
            });
        }
    });
</script>