<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$stats = get_admin_stats();
$recent_visits = get_recent_visits(10);
$recent_downloads = get_recent_downloads(10);
$countries = get_country_stats();
$daily_stats = get_daily_stats(30);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameHub Launcher - Admin Panel</title>
    <link rel="stylesheet" href="../css/landing.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <span>GameHub</span>Admin
            </div>
            <nav class="admin-nav">
                <ul>
                    <li class="active"><a href="control.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="visits.php"><i class="fas fa-users"></i> Visits</a></li>
                    <li><a href="downloads.php"><i class="fas fa-download"></i> Downloads</a></li>
                    <li><a href="games.php"><i class="fas fa-gamepad"></i> Games</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="admin-content">
            <header class="admin-header">
                <h1>Dashboard</h1>
                <div class="admin-user">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>

            <section class="admin-stats">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-eye"></i>
        </div>
        <div class="stat-info">
            <h3>Total Visits</h3>
            <p><?php echo number_format($stats['total_visits']); ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3>Unique Visitors</h3>
            <p><?php echo number_format($stats['unique_visitors']); ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-download"></i>
        </div>
        <div class="stat-info">
            <h3>Total Downloads</h3>
            <p><?php echo number_format($stats['total_downloads']); ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-user-check"></i>
        </div>
        <div class="stat-info">
            <h3>Unique Downloads</h3>
            <p><?php echo number_format($stats['unique_downloads']); ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-calendar-day"></i>
        </div>
        <div class="stat-info">
            <h3>Today's Downloads</h3>
            <p><?php echo number_format($stats['today_downloads']); ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="stat-info">
            <h3>Today's Unique</h3>
            <p><?php echo number_format($stats['today_unique_downloads']); ?></p>
        </div>
    </div>
</section>

            <section class="admin-charts">
                <div class="chart-container">
                    <h2>Visits & Downloads (Last 30 Days)</h2>
                    <canvas id="trafficChart"></canvas>
                </div>
                <div class="chart-container">
                    <h2>Visitors by Country</h2>
                    <canvas id="countryChart"></canvas>
                </div>
            </section>

            <section class="admin-tables">
                <div class="table-container">
                    <h2>Recent Visits</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Country</th>
                                <th>Page</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_visits as $visit): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($visit['ip_address']); ?></td>
                                <td><?php echo htmlspecialchars($visit['country']); ?></td>
                                <td><?php echo htmlspecialchars($visit['page_visited']); ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($visit['visit_time'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="table-container">
                    <h2>Recent Downloads</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Country</th>
                                <th>Trial Code</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_downloads as $download): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($download['ip_address']); ?></td>
                                <td><?php echo htmlspecialchars($download['country']); ?></td>
                                <td><?php echo htmlspecialchars($download['trial_code']); ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($download['download_time'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Traffic Chart
        const trafficCtx = document.getElementById('trafficChart').getContext('2d');
        const trafficChart = new Chart(trafficCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($daily_stats, 'date')); ?>,
                datasets: [
                    {
                        label: 'Visits',
                        data: <?php echo json_encode(array_column($daily_stats, 'visits')); ?>,
                        borderColor: '#6c5ce7',
                        backgroundColor: 'rgba(108, 92, 231, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Downloads',
                        data: <?php echo json_encode(array_column($daily_stats, 'downloads')); ?>,
                        borderColor: '#00b894',
                        backgroundColor: 'rgba(0, 184, 148, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Country Chart
        const countryCtx = document.getElementById('countryChart').getContext('2d');
        const countryChart = new Chart(countryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($countries, 'country')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($countries, 'count')); ?>,
                    backgroundColor: [
                        '#6c5ce7',
                        '#a29bfe',
                        '#fd79a8',
                        '#00b894',
                        '#fdcb6e',
                        '#e17055',
                        '#0984e3',
                        '#00cec9'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
    </script>
</body>
</html>