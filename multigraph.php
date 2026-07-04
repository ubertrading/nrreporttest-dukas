<?php // content="text/plain; charset=utf-8"
date_default_timezone_set('Europe/Warsaw');

$news_title = $_GET['news'] ?? "Missing news title";
$forecast_avg = $_GET['forecast_avg'] ?? '';
$forecast = $_GET['forecast'] ?? '';
$actual = $_GET['actual'] ?? '';
$deviation = $_GET['deviation'] ?? '';
$instruments_str = $_GET['instruments'] ?? '';
$event_datetime = $_GET['datetime'] ?? '';
$instruments = array_filter(explode(',', $instruments_str));

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
    .info-bar .data-row strong { color: #fff; }
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
      cursor: pointer;
      transition: border-color 0.2s;
    }
    .chart-panel:hover {
      border-color: #4fc3f7;
    }
    .chart-panel .chart-title {
      padding: 6px 15px;
      font-size: 18px;
      font-weight: bold;
      color: #fff;
      border-bottom: 1px solid #0f3460;
      background: #1a1a3e;
      letter-spacing: 0.5px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .chart-panel .chart-title .expand-hint {
      font-size: 11px;
      color: #666;
      font-weight: normal;
    }
    .chart-panel iframe {
      width: 100%;
      height: 720px;
      border: none;
      background: #fff;
      pointer-events: none;
    }

    /* Modal overlay for expanded chart */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.85);
      z-index: 9999;
      justify-content: center;
      align-items: center;
      padding: 30px;
    }
    .modal-overlay.active {
      display: flex;
    }
    .modal-content {
      background: #16213e;
      border: 2px solid #0f3460;
      border-radius: 12px;
      width: 95%;
      max-width: 1200px;
      max-height: 90vh;
      overflow: hidden;
      position: relative;
    }
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 20px;
      background: #1a1a3e;
      border-bottom: 1px solid #0f3460;
    }
    .modal-header h3 {
      font-size: 22px;
      color: #fff;
    }
    .modal-close {
      background: none;
      border: 2px solid #666;
      color: #ccc;
      font-size: 18px;
      padding: 4px 12px;
      border-radius: 6px;
      cursor: pointer;
    }
    .modal-close:hover {
      border-color: #ef5350;
      color: #ef5350;
    }
    .modal-content iframe {
      width: 100%;
      height: calc(90vh - 60px);
      border: none;
      background: #fff;
      overflow-y: auto;
    }
  </style>
</head>
<body>
  <div class="info-bar">
    <h2><?php echo htmlspecialchars($news_title); ?>
      <?php if ($event_datetime !== ''): ?>
        <span style="font-size: 14px; color: #aaa; margin-left: 15px;"><?= htmlspecialchars($event_datetime) ?></span>
      <?php endif; ?>
    </h2>
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
      <div class="chart-panel" onclick="expandChart('<?= htmlspecialchars(trim($symbol)) ?>', '<?= htmlspecialchars($iframe_url) ?>')">
        <div class="chart-title">
          <?= htmlspecialchars(trim($symbol)) ?>
          <span class="expand-hint">click to expand</span>
        </div>
        <iframe src="<?= htmlspecialchars($iframe_url) ?>" loading="lazy"></iframe>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Expanded chart modal -->
  <div class="modal-overlay" id="chartModal" onclick="closeModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
      <div class="modal-header">
        <h3 id="modalTitle"></h3>
        <button class="modal-close" onclick="closeModal()">&#x2715; Close</button>
      </div>
      <iframe id="modalIframe" src="about:blank"></iframe>
    </div>
  </div>

  <script>
    function expandChart(symbol, url) {
      // Remove embed=1 for popup so chart shows at full size
      url = url.replace('&embed=1', '').replace('embed=1&', '').replace('embed=1', '');
      document.getElementById('modalTitle').textContent = symbol;
      document.getElementById('modalIframe').src = url;
      document.getElementById('chartModal').classList.add('active');
    }
    function closeModal(e) {
      if (e && e.target !== document.getElementById('chartModal')) return;
      document.getElementById('chartModal').classList.remove('active');
      document.getElementById('modalIframe').src = 'about:blank';
    }
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeModal();
    });
  </script>
</body>
</html>
