import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

window.initializeDashboardCharts = function ({ roleLabels, roleValues, signupLabels, signupValues }) {
    const roleCanvas = document.getElementById('dashboard-role-chart');
    const signupCanvas = document.getElementById('dashboard-signup-chart');

    if (roleCanvas && roleLabels.length) {
        new Chart(roleCanvas, {
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
                            color: '#cbd5e1',
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
        new Chart(signupCanvas, {
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
                            color: '#cbd5e1',
                        },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: '#cbd5e1',
                        },
                        grid: {
                            color: 'rgba(148,163,184,0.16)',
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
