<?php

declare(strict_types=1);

use FrostyMedia\WpTally\Stats\Table;

$data ??= [];

?>
<h2>WP Tally Stats</h2>
<?php
$table = new Table();
$table->prepare_items();
$table->display();
?>
<h3>User Activity</h3>
<table id="activityTable" class="widefat striped fixed">
    <thead>
    <tr>
        <th>User</th>
        <th>View Type</th>
        <th>IP Address</th>
        <th>Count</th>
        <th>Clear</th>
    </tr>
    </thead>
    <tbody></tbody>
</table>

<h2>Chart: Views per User (Grouped by View Type)</h2>
<canvas id="userChart"></canvas>

<h2>Chart: Views per IP Address (Grouped by View Type)</h2>
<canvas id="ipChart"></canvas>

<script>
  /**
   * @typedef {Object} ViewType
   * @property {Object.<string, number>} api
   * @property {Object.<string, number>} shortcode
   */

  /**
   * @typedef {Object} User
   * @property {number} total_count
   * @property {ViewType} view
   */

  /**
   * @typedef {Object} Data
   * @property {number} total_count
   * @property {Object.<string, User>} users
   * @property {string} db_version
   */

  /** @type {Data} */
  const data = <?php echo json_encode($data, JSON_THROW_ON_ERROR) ?>

  const tbody = document.querySelector('#activityTable tbody')
  const url = window.location.href
  const nonce = '<?php echo wp_create_nonce('_wp_tally_nonce'); ?>'

  const usernames = []
  const viewTypes = new Set()
  const userViewTypeMap = {}
  const ipViewMap = {}  // { ip: { viewType: count } }

  // Populate table + aggregate data
  for (const [username, userData] of Object.entries(data.users)) {
    usernames.push(username)
    userViewTypeMap[username] = {}

    for (const [viewType, ipData] of Object.entries(userData.view)) {
      viewTypes.add(viewType)

      let viewTotal = 0

      for (const [ip, count] of Object.entries(ipData)) {
        viewTotal += count

        // Table row
        const row = `<tr>
        <td>${username}</td>
        <td>${viewType}</td>
        <td>${ip}</td>
        <td>${count}</td>
        <td><a href="${url}&_wpnonce=${nonce}&_wp_tally_clear_user=1&username=${username}&ip=${ip}&view=${viewType}">Clear ${ip} stats</a></td>
      </tr>`
        tbody.insertAdjacentHTML('beforeend', row)

        // IP-based aggregation
        if (!ipViewMap[ip]) ipViewMap[ip] = {}
        ipViewMap[ip][viewType] = (ipViewMap[ip][viewType] || 0) + count
      }

      userViewTypeMap[username][viewType] = viewTotal
    }
  }

  // Prepare user view type chart
  const userChartDatasets = Array.from(viewTypes).map(viewType => {
    return {
      label: viewType,
      backgroundColor: viewType === 'api' ? '#36a2eb' : '#4bc0c0',
      data: usernames.map(username => userViewTypeMap[username][viewType] || 0)
    }
  })

  new Chart(document.getElementById('userChart'), {
    type: 'bar',
    data: {
      labels: usernames,
      datasets: userChartDatasets
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'top' } },
      scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }
    }
  })

  // Prepare IP-based chart
  const ipAddresses = Object.keys(ipViewMap)
  const ipChartDatasets = Array.from(viewTypes).map(viewType => {
    return {
      label: viewType,
      backgroundColor: viewType === 'api' ? '#ff6384' : '#ffcd56',
      data: ipAddresses.map(ip => ipViewMap[ip][viewType] || 0)
    }
  })

  new Chart(document.getElementById('ipChart'), {
    type: 'bar',
    data: {
      labels: ipAddresses,
      datasets: ipChartDatasets
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'top' } },
      scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }
    }
  })
</script>

