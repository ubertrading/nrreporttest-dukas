<?php
include 'config.php';

$newsId = isset($_GET['newsId']) ? intval($_GET['newsId']) : 0;
$news_title = isset($_GET['news']) ? $_GET['news'] : 'Unknown Event';
$site = isset($_GET['site']) ? $_GET['site'] : 'NY';

$tbl_prefix = strtolower($site) . "_";
$servername = $db_servername;
$username = $db_username;
$password = $db_password;
$database = $db_database;
$table = $tbl_prefix . $db_table;

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch historical data for this newsId
// Only select rows where value is not null and either forecast or forecast_avg is not null
$sql = "SELECT event_time, MIN(`timestamp`) as timestamp, value, forecast, forecast_avg 
        FROM `{$table}` 
        WHERE news_id = {$newsId} 
        AND value IS NOT NULL AND value != ''
        GROUP BY event_time, value, forecast, forecast_avg 
        ORDER BY event_time ASC, MIN(`timestamp`) = 0 ASC, MIN(`timestamp`) ASC";

$res = $conn->query($sql);
$data = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $val = floatval($row['value']);
        
        $dev = null;
        if (isset($row['forecast']) && $row['forecast'] !== '') {
            $dev = $val - floatval($row['forecast']);
        } else if (isset($row['forecast_avg']) && $row['forecast_avg'] !== '') {
            $dev = $val - floatval($row['forecast_avg']);
        }

        if ($dev !== null) {
            // Group by event_time in PHP to pick just the primary (earliest) record for that time
            if (!isset($data[$row['event_time']])) {
                $data[$row['event_time']] = [
                    'time' => (int)$row['event_time'],
                    'dev' => round($dev, 5)
                ];
            }
        }
    }
}

$conn->close();

// Prepare arrays for Chart.js
$labels = [];
$deviations = [];
$colors = [];

foreach ($data as $event_time => $row) {
    $labels[] = date('Y-m-d', $event_time / 1000);
    $deviations[] = $row['dev'];
    $colors[] = $row['dev'] >= 0 ? 'rgba(46, 125, 50, 0.8)' : 'rgba(198, 40, 40, 0.8)';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Deviation History: <?php echo htmlspecialchars($news_title); ?></title>
  
  <script src="node_modules/jquery/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>

  <style>
    body {
      font-family: sans-serif;
      margin: 0;
      padding: 20px;
      transition: background 0.3s, color 0.3s;
    }
    .dark-theme {
      background: #1a1a2e;
      color: #eee;
    }
    .chart-container {
      position: relative;
      height: 80vh;
      width: 100%;
      max-width: 1200px;
      margin: 0 auto;
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <script>
    var isDark = localStorage.getItem('darkTheme') !== 'false';
    if (isDark) document.body.classList.add('dark-theme');
    var chartFontColor = isDark ? '#ccc' : '#666';
    var chartGridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';
  </script>

  <h2><?php echo htmlspecialchars($news_title); ?> - Historical Deviations</h2>
  
  <div class="chart-container">
    <canvas id="deviationChart"></canvas>
  </div>

  <script>
    var ctx = document.getElementById('deviationChart').getContext('2d');
    var labels = <?php echo json_encode(array_values($labels)); ?>;
    var data = <?php echo json_encode(array_values($deviations)); ?>;
    var colors = <?php echo json_encode(array_values($colors)); ?>;

    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Deviation',
          data: data,
          backgroundColor: colors,
          borderColor: colors.map(c => c.replace('0.8', '1')),
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            ticks: { color: chartFontColor, maxRotation: 45 },
            grid: { color: chartGridColor }
          },
          y: {
            ticks: { color: chartFontColor },
            grid: { color: chartGridColor },
            title: {
              display: true,
              text: 'Deviation Value',
              color: chartFontColor
            }
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                return 'Deviation: ' + context.raw;
              }
            }
          }
        }
      }
    });
  </script>
</body>
</html>
