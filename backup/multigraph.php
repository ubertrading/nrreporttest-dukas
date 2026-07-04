<?php // content="text/plain; charset=utf-8"
date_default_timezone_set('Europe/Warsaw');

$news_title = $_GET['news'] ?? "Missing news title";
$forecast_avg = $_GET['forecast_avg'] ?? '';
$forecast = $_GET['forecast'] ?? '';
$actual = $_GET['actual'] ?? '';
$deviation = $_GET['deviation'] ?? '';
$instruments_str = $_GET['instruments'] ?? '';
$instruments = array_filter(explode(',', $instruments_str));

// Build base params for each iframe (everything except symbol)
$base_params = array(
  'id' => $_GET['id'] ?? '',
  'datetime' => $_GET['datetime'] ?? '',
  'scheduled' => $_GET['scheduled'] ?? '',
  'news' => $news_title,
  'forecast_avg' => $forecast_avg,
  'forecast' => $forecast,
  'actual' => $actual,
  'deviation' => $deviation,
  'embed' => '1'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Multi Chart - <?php echo htmlspecialchars($news_title); ?></title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: #1a1a2e;
      color: #eee;
      padding: 15px;
    }
    .info-bar {
      background: #16213e;
      border: 1px solid #0f3460;
      border-radius: 8px;
      padding: 12px 20px;
      margin-bottom: 15px;
    }
    .info-bar h2 {
      font-size: 20px;
      margin-bottom: 6px;
      color: #ffffff;
    }
    .info-bar .data-row {
      display: flex;
      gap: 30px;
      font-size: 14px;
      color: #ccc;
    }
    .info-bar .data-row strong {
      color: #fff;
    }
    .dev-positive { color: #4caf50 !important; font-weight: bold; }
    .dev-negative { color: #ef5350 !important; font-weight: bold; }
    .charts-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }
    .chart-panel {
      background: #16213e;
      border: 1px solid #0f3460;
      border-radius: 8px;
      overflow: hidden;
    }
    .chart-panel .chart-title {
      padding: 10px 15px;
      font-size: 18px;
      font-weight: bold;
      color: #fff;
      border-bottom: 1px solid #0f3460;
      background: #1a1a3e;
      letter-spacing: 0.5px;
    }
    .chart-panel iframe {
      width: 100%;
      height: 450px;
      border: none;
      background: #fff;
    }
  </style>
</head>
<body>
  <div class="info-bar">
    <h2><?php echo htmlspecialchars($news_title); ?></h2>
    <div class="data-row">
      <?php if ($forecast_avg !== ''): ?>
        <span><strong>Avg. F'cast:</strong> <?= htmlspecialchars($forecast_avg) ?></span>
      <?php endif; ?>
      <?php if ($forecast !== ''): ?>
        <span><strong>Forecast:</strong> <?= htmlspecialchars($forecast) ?></span>
      <?php endif; ?>
      <?php if ($actual !== ''): ?>
        <span><strong>Actual:</strong> <?= htmlspecialchars($actual) ?></span>
      <?php endif; ?>
      <?php if ($deviation !== ''): ?>
        <span class="<?= floatval($deviation) >= 0 ? 'dev-positive' : 'dev-negative' ?>">
          <strong>Deviation:</strong> <?= htmlspecialchars($deviation) ?>
        </span>
      <?php endif; ?>
    </div>
  </div>

  <div class="charts-grid">
    <?php foreach ($instruments as $symbol): ?>
      <?php
        $params = $base_params;
        $params['symbol'] = trim($symbol);
        $iframe_url = 'graph.php?' . http_build_query($params);
      ?>
      <div class="chart-panel">
        <div class="chart-title"><?= htmlspecialchars(trim($symbol)) ?></div>
        <iframe src="<?= htmlspecialchars($iframe_url) ?>" loading="lazy"></iframe>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>
