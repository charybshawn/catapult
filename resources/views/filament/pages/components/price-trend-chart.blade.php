<div>
    <canvas id="priceTrendChart" style="width:100%; height:300px;"></canvas>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    console.log('🚀 Chart initialization started');
    
    const canvas = document.getElementById('priceTrendChart');
    if (!canvas) {
        console.error('❌ Chart canvas not found!');
        return;
    }
    
    const ctx = canvas.getContext('2d');
    const chartData = {!! json_encode($chartData) !!};
    
    // Detailed debugging
    console.log('📊 Chart Data Structure:', {
        labels: chartData.labels,
        datasets: chartData.datasets,
        hasLabels: chartData.labels && chartData.labels.length > 0,
        hasDatasets: chartData.datasets && chartData.datasets.length > 0,
        datasetCount: chartData.datasets ? chartData.datasets.length : 0
    });
    
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('❌ Chart.js library not loaded!');
        return;
    }
    
    // Validate data
    if (!chartData.labels || chartData.labels.length === 0) {
        console.warn('⚠️ No labels provided for chart');
    }
    
    if (!chartData.datasets || chartData.datasets.length === 0) {
        console.warn('⚠️ No datasets provided for chart');
        return;
    }
    
    // Add colors to datasets
    const colors = [
        'rgb(255, 99, 132)',
        'rgb(54, 162, 235)', 
        'rgb(255, 205, 86)',
        'rgb(75, 192, 192)',
        'rgb(153, 102, 255)',
        'rgb(255, 159, 64)'
    ];
    
    chartData.datasets.forEach((dataset, index) => {
        const color = colors[index % colors.length];
        dataset.borderColor = color;
        dataset.backgroundColor = color + '20'; // Add transparency
        
        console.log(`📈 Dataset ${index}:`, {
            label: dataset.label,
            dataPoints: dataset.data,
            nonNullPoints: dataset.data.filter(p => p !== null).length
        });
    });
    
    try {
        // Create the chart
        const chart = new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false,
                        title: {
                            display: true,
                            text: 'Price per kg (USD)'
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
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('en-US', {
                                        style: 'currency',
                                        currency: 'USD'
                                    }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
        
        console.log('✅ Chart created successfully!', chart);
        
    } catch (error) {
        console.error('❌ Error creating chart:', error);
    }
});

// Global error handler for Chart.js
window.addEventListener('error', function(e) {
    if (e.message.toLowerCase().includes('chart')) {
        console.error('🔥 Chart-related error:', e);
    }
});
</script> 