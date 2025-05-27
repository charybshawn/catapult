<div class="w-full h-96">
    <canvas id="priceChart" class="w-full h-full"></canvas>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('priceChart').getContext('2d');
        
        // Generate random colors for each dataset
        const chartData = {!! json_encode($chartData) !!};
        const datasets = chartData.datasets.map((dataset, index) => {
            const hue = (index * 137) % 360; // Generate different hues
            const color = `hsl(${hue}, 70%, 60%)`;
            
            return {
                ...dataset,
                borderColor: color,
                backgroundColor: color + '33',
            };
        });
        
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Price per KG (USD)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Seed Price Trends Over Time'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.parsed.y;
                                return `${label}: $${value.toFixed(2)} per KG`;
                            }
                        }
                    }
                }
            }
        });
    });
</script> 