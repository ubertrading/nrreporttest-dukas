<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
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
$raw_rows = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $raw_rows[] = $row;
        $val = floatval($row['value']);
        
        $dev = null;
        if (isset($row['forecast']) && $row['forecast'] !== '') {
            $dev = $val - floatval($row['forecast']);
        } else if (isset($row['forecast_avg']) && $row['forecast_avg'] !== '') {
            $dev = $val - floatval($row['forecast_avg']);
        }

        if ($dev !== null) {
            $evTimeStr = date('Y-m-d H:i', $row['event_time'] / 1000);
            $ts = (int)$row['timestamp'];
            $evTimeMs = (int)$row['event_time'];
            
            // Completely ignore bogus ticks that arrive more than 1 hour BEFORE the scheduled event
            if ($ts != 0 && $ts < ($evTimeMs - 3600000)) {
                continue;
            }
            
            if (!isset($data[$evTimeStr])) {
                $data[$evTimeStr] = [
                    'time' => $evTimeMs,
                    'dev' => round($dev, 5),
                    'timestamp' => $ts
                ];
            } else {
                $existingTs = $data[$evTimeStr]['timestamp'];
                
                // Determine if this new row has an earlier valid timestamp
                $isNewEarlier = false;
                if ($existingTs == 0 && $ts != 0) {
                    $isNewEarlier = true;
                } else if ($ts != 0 && $ts < $existingTs) {
                    $isNewEarlier = true;
                }

                if ($isNewEarlier) {
                    $data[$evTimeStr] = [
                        'time' => $evTimeMs,
                        'dev' => round($dev, 5),
                        'timestamp' => $ts
                    ];
                }
            }
        }
    }
}

$conn->close();

if (isset($_GET['debug'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'raw_rows' => $raw_rows,
        'processed_data' => $data
    ]);
    exit;
}

// Prepare arrays for Chart.js
$labels = [];
$deviations = [];
$colors = [];
$times = [];

foreach ($data as $evTimeStr => $row) {
    $labels[] = date('Y-m-d', $row['time'] / 1000);
    $deviations[] = $row['dev'];
    $colors[] = $row['dev'] >= 0 ? 'rgba(46, 125, 50, 0.8)' : 'rgba(198, 40, 40, 0.8)';
    $times[] = $row['time'];
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
  
  <div style="text-align: center; margin-bottom: 20px;">
      <label for="fromDate" style="color: var(--font-color);">From Date:</label>
      <input type="date" id="fromDate" style="margin-right: 15px; padding: 4px; background: transparent; color: var(--font-color); border: 1px solid #555;">
      
      <label for="toDate" style="color: var(--font-color);">To Date:</label>
      <input type="date" id="toDate" style="margin-right: 25px; padding: 4px; background: transparent; color: var(--font-color); border: 1px solid #555;">

      <label for="minY" style="color: var(--font-color);">Min Y:</label>
      <input type="number" id="minY" style="width: 80px; margin-right: 15px; padding: 4px; background: transparent; color: var(--font-color); border: 1px solid #555;">
      
      <label for="maxY" style="color: var(--font-color);">Max Y:</label>
      <input type="number" id="maxY" style="width: 80px; margin-right: 15px; padding: 4px; background: transparent; color: var(--font-color); border: 1px solid #555;">
      
      <button onclick="updateChartScale()" style="padding: 4px 12px; cursor: pointer; background: #333; color: #fff; border: 1px solid #555;">Apply Filter</button>
      <button onclick="resetChartScale()" style="padding: 4px 12px; cursor: pointer; margin-left: 5px; background: #333; color: #fff; border: 1px solid #555;">Reset</button>
  </div>
  
  <div class="chart-container">
    <canvas id="deviationChart"></canvas>
  </div>

  <script>
    var isDark = localStorage.getItem('darkTheme') !== 'false';
    document.documentElement.style.setProperty('--font-color', isDark ? '#ccc' : '#666');
    
    var ctx = document.getElementById('deviationChart').getContext('2d');
    var origLabels = <?php echo json_encode(array_values($labels)); ?>;
    var origData = <?php echo json_encode(array_values($deviations)); ?>;
    var origColors = <?php echo json_encode(array_values($colors)); ?>;
    var origTimes = <?php echo json_encode(array_values($times)); ?>;

    var deviationChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: [...origLabels],
        times: [...origTimes],
        datasets: [{
          label: 'Deviation',
          data: [...origData],
          backgroundColor: [...origColors],
          borderColor: origColors.map(c => c.replace('0.8', '1')),
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: (e, elements, chart) => {
          if (elements.length > 0) {
            var index = elements[0].index;
            var timeMs = chart.data.times[index];
            var dateStr = chart.data.labels[index];
            
            var url = 'nrreport.html?datefrom=' + dateStr + '&dateto=' + dateStr + '&exactTime=' + timeMs;
            window.open(url, '_blank');
          }
        },
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
    
    function updateChartScale() {
        var minVal = document.getElementById('minY').value;
        var maxVal = document.getElementById('maxY').value;
        var fromDate = document.getElementById('fromDate').value;
        var toDate = document.getElementById('toDate').value;
        
        // 1. Update Y-Axis Scale Limits
        if (minVal !== '') {
            deviationChart.options.scales.y.min = parseFloat(minVal);
        } else {
            delete deviationChart.options.scales.y.min;
        }
        
        if (maxVal !== '') {
            deviationChart.options.scales.y.max = parseFloat(maxVal);
        } else {
            delete deviationChart.options.scales.y.max;
        }
        
        // 2. Filter X-Axis Data by Date
        var filteredLabels = [];
        var filteredData = [];
        var filteredColors = [];
        var filteredTimes = [];
        
        for (var i = 0; i < origLabels.length; i++) {
            var rowDate = origLabels[i]; // e.g. "2021-01-08"
            
            var keep = true;
            if (fromDate !== '' && rowDate < fromDate) keep = false;
            if (toDate !== '' && rowDate > toDate) keep = false;
            
            if (keep) {
                filteredLabels.push(origLabels[i]);
                filteredData.push(origData[i]);
                filteredColors.push(origColors[i]);
                filteredTimes.push(origTimes[i]);
            }
        }
        
        deviationChart.data.labels = filteredLabels;
        deviationChart.data.times = filteredTimes;
        deviationChart.data.datasets[0].data = filteredData;
        deviationChart.data.datasets[0].backgroundColor = filteredColors;
        deviationChart.data.datasets[0].borderColor = filteredColors.map(c => c.replace('0.8', '1'));
        
        deviationChart.update();
    }

    function resetChartScale() {
        // Reset inputs
        document.getElementById('minY').value = '';
        document.getElementById('maxY').value = '';
        document.getElementById('fromDate').value = '';
        document.getElementById('toDate').value = '';
        
        // Reset Y scale
        delete deviationChart.options.scales.y.min;
        delete deviationChart.options.scales.y.max;
        
        // Reset data
        deviationChart.data.labels = [...origLabels];
        deviationChart.data.times = [...origTimes];
        deviationChart.data.datasets[0].data = [...origData];
        deviationChart.data.datasets[0].backgroundColor = [...origColors];
        deviationChart.data.datasets[0].borderColor = origColors.map(c => c.replace('0.8', '1'));
        
        deviationChart.update();
    }
  </script>
</body>
</html>
