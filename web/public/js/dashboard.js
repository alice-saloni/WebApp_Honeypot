// Initialize Charts
let timelineChart, attackTypesChart, severityChart;
let lastUpdate = new Date();

// Chart Configuration
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 750,
        easing: 'easeInOutQuart'
    }
};

// Initialize WebSocket connection for real-time updates
const socket = io('http://localhost:3000');

document.addEventListener('DOMContentLoaded', () => {
    initializeCharts();
    setupWebSocket();
    loadInitialData();
});

function initializeCharts() {
    // Timeline Chart
    timelineChart = new Chart(document.getElementById('timelineChart'), {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Attacks per Minute',
                data: [],
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            ...chartOptions,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Attack Types Chart
    attackTypesChart = new Chart(document.getElementById('attackTypesChart'), {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF'
                ]
            }]
        },
        options: chartOptions
    });

    // Severity Chart
    severityChart = new Chart(document.getElementById('severityChart'), {
        type: 'bar',
        data: {
            labels: ['Critical', 'High', 'Medium', 'Low'],
            datasets: [{
                label: 'Attacks by Severity',
                data: [0, 0, 0, 0],
                backgroundColor: [
                    '#DC2626',
                    '#F59E0B',
                    '#10B981',
                    '#60A5FA'
                ]
            }]
        },
        options: chartOptions
    });
}

function setupWebSocket() {
    socket.on('newAttack', (data) => {
        updateDashboard(data);
        addAttackToFeed(data);
    });
}

function loadInitialData() {
    fetch('/api.php?action=dashboard_stats')
        .then(response => response.json())
        .then(data => {
            updateStats(data.stats);
            updateCharts(data.charts);
        });
}

function updateDashboard(attackData) {
    // Update timeline
    const now = new Date();
    timelineChart.data.labels.push(now.toLocaleTimeString());
    timelineChart.data.datasets[0].data.push(1);
    if (timelineChart.data.labels.length > 30) {
        timelineChart.data.labels.shift();
        timelineChart.data.datasets[0].data.shift();
    }
    timelineChart.update();

    // Update attack types
    updateAttackTypeChart(attackData.type);

    // Update severity
    updateSeverityChart(attackData.severity);

    // Update stats
    document.getElementById('activeAttacks').textContent = 
        parseInt(document.getElementById('activeAttacks').textContent) + 1;
}

function addAttackToFeed(attack) {
    const feed = document.getElementById('attackFeed');
    const card = createAttackCard(attack);
    feed.insertBefore(card, feed.firstChild);

    // Remove old entries if more than 10
    if (feed.children.length > 10) {
        feed.removeChild(feed.lastChild);
    }
}

function createAttackCard(attack) {
    const card = document.createElement('div');
    card.className = 'attack-card bg-white rounded-lg shadow p-4 border-l-4 ' + 
        getSeverityColor(attack.severity);
    
    card.innerHTML = `
        <div class="flex justify-between items-start">
            <div>
                <h3 class="font-bold text-lg">${attack.type}</h3>
                <p class="text-gray-600">${attack.description}</p>
            </div>
            <span class="px-2 py-1 rounded text-sm ${getSeverityBadgeClass(attack.severity)}">
                ${attack.severity}
            </span>
        </div>
        <div class="mt-4">
            <p class="text-sm text-gray-500">
                <strong>IP:</strong> ${attack.ip} |
                <strong>Path:</strong> ${attack.path}
            </p>
            <p class="text-sm text-gray-500 mt-1">
                <strong>Technique:</strong> ${attack.technique}
            </p>
        </div>
        ${attack.steps ? `
        <div class="mt-2">
            <p class="text-sm font-semibold">Attack Steps:</p>
            <ol class="list-decimal list-inside text-sm text-gray-600">
                ${attack.steps.map(step => `<li>${step}</li>`).join('')}
            </ol>
        </div>
        ` : ''}
    `;

    return card;
}

function getSeverityColor(severity) {
    const colors = {
        'Critical': 'border-red-600',
        'High': 'border-orange-500',
        'Medium': 'border-yellow-500',
        'Low': 'border-blue-500'
    };
    return colors[severity] || 'border-gray-500';
}

function getSeverityBadgeClass(severity) {
    const classes = {
        'Critical': 'bg-red-100 text-red-800',
        'High': 'bg-orange-100 text-orange-800',
        'Medium': 'bg-yellow-100 text-yellow-800',
        'Low': 'bg-blue-100 text-blue-800'
    };
    return classes[severity] || 'bg-gray-100 text-gray-800';
}

// Helper function to update attack type chart
function updateAttackTypeChart(type) {
    const index = attackTypesChart.data.labels.indexOf(type);
    if (index === -1) {
        attackTypesChart.data.labels.push(type);
        attackTypesChart.data.datasets[0].data.push(1);
    } else {
        attackTypesChart.data.datasets[0].data[index]++;
    }
    attackTypesChart.update();
}

// Helper function to update severity chart
function updateSeverityChart(severity) {
    const severityIndex = {
        'Critical': 0,
        'High': 1,
        'Medium': 2,
        'Low': 3
    };
    severityChart.data.datasets[0].data[severityIndex[severity]]++;
    severityChart.update();
}
