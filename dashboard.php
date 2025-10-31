<?php
// dashboard.php
// Sales Dashboard with iCloud-inspired design
$DB_HOST = 'localhost';
$DB_USER = 's67160157';
$DB_PASS = 'TJUH8MHy';
$DB_NAME = 's67160157';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
  http_response_code(500);
  die('Database connection failed: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

function fetch_all($mysqli, $sql) {
  $res = $mysqli->query($sql);
  if (!$res) { return []; }
  $rows = [];
  while ($row = $res->fetch_assoc()) { $rows[] = $row; }
  $res->free();
  return $rows;
}

$monthly = fetch_all($mysqli, "SELECT ym, net_sales FROM v_monthly_sales");
$category = fetch_all($mysqli, "SELECT category, net_sales FROM v_sales_by_category");
$region = fetch_all($mysqli, "SELECT region, net_sales FROM v_sales_by_region");
$topProducts = fetch_all($mysqli, "SELECT product_name, qty_sold, net_sales FROM v_top_products");
$payment = fetch_all($mysqli, "SELECT payment_method, net_sales FROM v_payment_share");
$hourly = fetch_all($mysqli, "SELECT hour_of_day, net_sales FROM v_hourly_sales");
$newReturning = fetch_all($mysqli, "SELECT date_key, new_customer_sales, returning_sales FROM v_new_vs_returning ORDER BY date_key");
$kpis = fetch_all($mysqli, "
  SELECT
    (SELECT SUM(net_amount) FROM fact_sales WHERE date_key >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)) AS sales_30d,
    (SELECT SUM(quantity)   FROM fact_sales WHERE date_key >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)) AS qty_30d,
    (SELECT COUNT(DISTINCT customer_id) FROM fact_sales WHERE date_key >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)) AS buyers_30d
");
$kpi = $kpis ? $kpis[0] : ['sales_30d'=>0,'qty_30d'=>0,'buyers_30d'=>0];

function nf($n) { return number_format((float)$n, 2); }
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Retail Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { 
      background: #f5f5f7; 
      color: #1d1d1f; 
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    /* iCloud-style top nav */
    .top-nav {
      background: rgba(255,255,255,0.8);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(0,0,0,0.08);
      padding: 12px 24px;
      position: sticky;
      top: 0;
      z-index: 1000;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .top-nav h1 {
      font-size: 1.3rem;
      font-weight: 600;
      color: #1d1d1f;
      margin: 0;
    }
    .logout-btn {
      background: #007aff;
      color: white;
      border: none;
      padding: 8px 20px;
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
    }
    .logout-btn:hover {
      background: #0051d5;
      transform: scale(1.02);
    }
    
    .container-fluid { 
      max-width: 1400px; 
      margin: 0 auto;
      padding: 24px;
    }
    
    .card { 
      background: white;
      border: none;
      border-radius: 18px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.06);
      padding: 24px;
      transition: all 0.3s;
    }
    .card:hover {
      box-shadow: 0 4px 16px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.08);
      transform: translateY(-2px);
    }
    .card h5 { 
      color: #1d1d1f; 
      font-weight: 600;
      font-size: 1rem;
      margin-bottom: 16px;
    }
    .kpi { 
      font-size: 2rem; 
      font-weight: 700; 
      color: #007aff;
      margin-top: 8px;
    }
    .kpi-label {
      color: #86868b;
      font-size: 0.85rem;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .grid { 
      display: grid; 
      gap: 20px; 
      grid-template-columns: repeat(12, 1fr); 
    }
    .col-12 { grid-column: span 12; }
    .col-6 { grid-column: span 6; }
    .col-4 { grid-column: span 4; }
    .col-8 { grid-column: span 8; }
    
    @media (max-width: 991px) {
      .col-6, .col-4, .col-8 { grid-column: span 12; }
      .top-nav h1 { font-size: 1.1rem; }
    }
    
    canvas { max-height: 340px; }
  </style>
</head>
<body>
  <!-- iCloud-style Navigation -->
  <nav class="top-nav">
    <h1>📊 Retail Dashboard</h1>
    <button class="logout-btn" onclick="if(confirm('ต้องการออกจากระบบหรือไม่?')) window.location.href='logout.php'">
      Logout
    </button>
  </nav>

  <div class="container-fluid">
    <!-- KPI Cards -->
    <div class="grid mb-4">
      <div class="card col-4">
        <div class="kpi-label">ยอดขาย 30 วัน</div>
        <div class="kpi">฿<?= nf($kpi['sales_30d']) ?></div>
      </div>
      <div class="card col-4">
        <div class="kpi-label">จำนวนชิ้นขาย 30 วัน</div>
        <div class="kpi"><?= number_format((int)$kpi['qty_30d']) ?></div>
      </div>
      <div class="card col-4">
        <div class="kpi-label">จำนวนผู้ซื้อ 30 วัน</div>
        <div class="kpi"><?= number_format((int)$kpi['buyers_30d']) ?></div>
      </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid">
      <div class="card col-8">
        <h5>ยอดขายรายเดือน</h5>
        <canvas id="chartMonthly"></canvas>
      </div>

      <div class="card col-4">
        <h5>สัดส่วนตามหมวด</h5>
        <canvas id="chartCategory"></canvas>
      </div>

      <div class="card col-6">
        <h5>Top 10 สินค้าขายดี</h5>
        <canvas id="chartTopProducts"></canvas>
      </div>

      <div class="card col-6">
        <h5>ยอดขายตามภูมิภาค</h5>
        <canvas id="chartRegion"></canvas>
      </div>

      <div class="card col-6">
        <h5>วิธีการชำระเงิน</h5>
        <canvas id="chartPayment"></canvas>
      </div>

      <div class="card col-6">
        <h5>ยอดขายรายชั่วโมง</h5>
        <canvas id="chartHourly"></canvas>
      </div>

      <div class="card col-12">
        <h5>ลูกค้าใหม่ vs ลูกค้าเดิม</h5>
        <canvas id="chartNewReturning"></canvas>
      </div>
    </div>
  </div>

<script>
const monthly = <?= json_encode($monthly, JSON_UNESCAPED_UNICODE) ?>;
const category = <?= json_encode($category, JSON_UNESCAPED_UNICODE) ?>;
const region = <?= json_encode($region, JSON_UNESCAPED_UNICODE) ?>;
const topProducts = <?= json_encode($topProducts, JSON_UNESCAPED_UNICODE) ?>;
const payment = <?= json_encode($payment, JSON_UNESCAPED_UNICODE) ?>;
const hourly = <?= json_encode($hourly, JSON_UNESCAPED_UNICODE) ?>;
const newReturning = <?= json_encode($newReturning, JSON_UNESCAPED_UNICODE) ?>;

const toXY = (arr, x, y) => ({ labels: arr.map(o => o[x]), values: arr.map(o => parseFloat(o[y])) });

// iCloud color palette
const blue = '#007aff';
const colors = ['#007aff', '#5ac8fa', '#4cd964', '#ff9500', '#ff3b30', '#af52de', '#ffcc00', '#ff2d55'];

// Monthly Sales
(() => {
  const {labels, values} = toXY(monthly, 'ym', 'net_sales');
  new Chart(document.getElementById('chartMonthly'), {
    type: 'line',
    data: { labels, datasets: [{ 
      label: 'ยอดขาย (฿)', 
      data: values, 
      borderColor: blue,
      backgroundColor: blue + '20',
      tension: 0.4, 
      fill: true,
      borderWidth: 2
    }] },
    options: { 
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: 'rgba(0,0,0,0.05)' } },
        y: { grid: { color: 'rgba(0,0,0,0.05)' } }
      }
    }
  });
})();

// Category
(() => {
  const {labels, values} = toXY(category, 'category', 'net_sales');
  new Chart(document.getElementById('chartCategory'), {
    type: 'doughnut',
    data: { labels, datasets: [{ data: values, backgroundColor: colors }] },
    options: { plugins: { legend: { position: 'bottom' } } }
  });
})();

// Top Products
(() => {
  const labels = topProducts.map(o => o.product_name);
  const qty = topProducts.map(o => parseInt(o.qty_sold));
  new Chart(document.getElementById('chartTopProducts'), {
    type: 'bar',
    data: { labels, datasets: [{ label: 'ชิ้นที่ขาย', data: qty, backgroundColor: blue }] },
    options: {
      indexAxis: 'y',
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: 'rgba(0,0,0,0.05)' } },
        y: { grid: { display: false } }
      }
    }
  });
})();

// Region
(() => {
  const {labels, values} = toXY(region, 'region', 'net_sales');
  new Chart(document.getElementById('chartRegion'), {
    type: 'bar',
    data: { labels, datasets: [{ label: 'ยอดขาย (฿)', data: values, backgroundColor: colors }] },
    options: { 
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false } },
        y: { grid: { color: 'rgba(0,0,0,0.05)' } }
      }
    }
  });
})();

// Payment
(() => {
  const {labels, values} = toXY(payment, 'payment_method', 'net_sales');
  new Chart(document.getElementById('chartPayment'), {
    type: 'pie',
    data: { labels, datasets: [{ data: values, backgroundColor: colors }] },
    options: { plugins: { legend: { position: 'bottom' } } }
  });
})();

// Hourly
(() => {
  const {labels, values} = toXY(hourly, 'hour_of_day', 'net_sales');
  new Chart(document.getElementById('chartHourly'), {
    type: 'bar',
    data: { labels, datasets: [{ label: 'ยอดขาย (฿)', data: values, backgroundColor: blue }] },
    options: { 
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false } },
        y: { grid: { color: 'rgba(0,0,0,0.05)' } }
      }
    }
  });
})();

// New vs Returning
(() => {
  const labels = newReturning.map(o => o.date_key);
  const newC = newReturning.map(o => parseFloat(o.new_customer_sales));
  const retC = newReturning.map(o => parseFloat(o.returning_sales));
  new Chart(document.getElementById('chartNewReturning'), {
    type: 'line',
    data: { labels,
      datasets: [
        { label: 'ลูกค้าใหม่ (฿)', data: newC, borderColor: '#5ac8fa', backgroundColor: '#5ac8fa20', tension: 0.4, fill: true, borderWidth: 2 },
        { label: 'ลูกค้าเดิม (฿)', data: retC, borderColor: '#4cd964', backgroundColor: '#4cd96420', tension: 0.4, fill: true, borderWidth: 2 }
      ]
    },
    options: { 
      plugins: { legend: { position: 'top' } },
      scales: {
        x: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { maxTicksLimit: 12 } },
        y: { grid: { color: 'rgba(0,0,0,0.05)' } }
      }
    }
  });
})();
</script>
</body>
</html>