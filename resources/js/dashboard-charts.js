import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const charts = {};

function replaceChart(key, canvas, config) {
    charts[key]?.destroy();
    charts[key] = new Chart(canvas, config);
}

const chartTextColor = () => document.documentElement.classList.contains('light') ? '#475569' : '#cbd5e1';
const chartGridColor = () => document.documentElement.classList.contains('light') ? 'rgba(15,23,42,0.12)' : 'rgba(148,163,184,0.16)';

window.initializeDashboardCharts = function ({ roleLabels, roleValues, signupLabels, signupValues }) {
    const roleCanvas = document.getElementById('dashboard-role-chart');
    const signupCanvas = document.getElementById('dashboard-signup-chart');

    if (roleCanvas && roleLabels.length) {
        replaceChart('dashboard-role', roleCanvas, {
            type: 'doughnut',
            data: {
                labels: roleLabels,
                datasets: [
                    {
                        data: roleValues,
                        backgroundColor: ['#2563eb', '#14b8a6', '#f97316', '#eab308', '#8b5cf6', '#f43f5e'],
                        borderWidth: 0,
                    },
                ],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: chartTextColor(),
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.label}: ${context.parsed}`,
                        },
                    },
                },
            },
        });
    }

    if (signupCanvas && signupLabels.length) {
        replaceChart('dashboard-signup', signupCanvas, {
            type: 'bar',
            data: {
                labels: signupLabels,
                datasets: [
                    {
                        label: 'New users',
                        data: signupValues,
                        backgroundColor: '#2563ebcc',
                        borderRadius: 8,
                        borderSkipped: false,
                    },
                ],
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: chartTextColor(),
                        },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: chartTextColor(),
                        },
                        grid: {
                            color: chartGridColor(),
                        },
                    },
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                },
            },
        });
    }
};

window.initializeReportsCharts = function ({ growthLabels, growthValues, permissionLabels, permissionValues, presenceLabels, presenceValues, loginLabels, loginValues }) {
    const growthCanvas = document.getElementById('reports-user-growth-chart');
    const permissionCanvas = document.getElementById('reports-permission-chart');
    const presenceCanvas = document.getElementById('reports-presence-chart');
    const loginCanvas = document.getElementById('reports-login-chart');

    if (growthCanvas && growthLabels.length) {
        replaceChart('reports-growth', growthCanvas, {
            type: 'line',
            data: {
                labels: growthLabels,
                datasets: [
                    {
                        label: 'New users',
                        data: growthValues,
                        borderColor: '#14b8a6',
                        backgroundColor: 'rgba(20,184,166,0.16)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 2,
                    },
                ],
            },
            options: lineChartOptions(),
        });
    }

    if (permissionCanvas && permissionLabels.length) {
        replaceChart('reports-permissions', permissionCanvas, {
            type: 'bar',
            data: {
                labels: permissionLabels,
                datasets: [
                    {
                        label: 'Permissions',
                        data: permissionValues,
                        backgroundColor: '#3b6df4cc',
                        borderRadius: 8,
                        borderSkipped: false,
                    },
                ],
            },
            options: barChartOptions(),
        });
    }

    if (presenceCanvas && presenceLabels.length) {
        replaceChart('reports-presence', presenceCanvas, {
            type: 'doughnut',
            data: {
                labels: presenceLabels,
                datasets: [
                    {
                        data: presenceValues,
                        backgroundColor: ['#34d399', '#fbbf24', '#f43f5e'],
                        borderWidth: 0,
                    },
                ],
            },
            options: doughnutChartOptions(),
        });
    }

    if (loginCanvas && loginLabels.length) {
        replaceChart('reports-logins', loginCanvas, {
            type: 'line',
            data: {
                labels: loginLabels,
                datasets: [
                    {
                        label: 'Logins',
                        data: loginValues,
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249,115,22,0.14)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 2,
                    },
                ],
            },
            options: lineChartOptions(),
        });
    }
};

function lineChartOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: chartTextColor(), maxTicksLimit: 8 },
            },
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, color: chartTextColor() },
                grid: { color: chartGridColor() },
            },
        },
        plugins: {
            legend: { display: false },
        },
    };
}

function doughnutChartOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '64%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: chartTextColor(),
                    boxWidth: 10,
                    boxHeight: 10,
                },
            },
            tooltip: {
                callbacks: {
                    label: (context) => `${context.label}: ${context.parsed}`,
                },
            },
        },
    };
}

function barChartOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        scales: {
            x: {
                beginAtZero: true,
                ticks: { stepSize: 1, color: chartTextColor() },
                grid: { color: chartGridColor() },
            },
            y: {
                grid: { display: false },
                ticks: { color: chartTextColor() },
            },
        },
        plugins: {
            legend: { display: false },
        },
    };
}
