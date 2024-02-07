<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.css">

<div class="row">
    <div class="col-md-4">

        <div class="card p-4">
            <div class="card-body">
                <h5 class="card-title">Today's Sale</h5>
                <h1>₱ <?= number_format($today_sales, 2, '.', ',') ?></h1>
            </div>
        </div>

    </div>
    <div class="col-md-4">

        <div class="card p-4">
            <div class="card-body">
                <h5 class="card-title">This Month's Sale</h5>
                <h1>₱ <?= number_format($this_month_sales, 2, '.', ',') ?></h1>
            </div>
        </div>

    </div>
</div>

<br>

<div class="row">
    <div class="col-md-8">
        <div class="card p-4">
            <canvas id="chBar"></canvas>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.js"></script>
<script>
var colors = ['#007bff', '#28a745', '#333333', '#c3e6cb', '#dc3545', '#6c757d'];
/* bar chart */
var chBar = document.getElementById("chBar");
if (chBar) {
    new Chart(chBar, {
        type: 'bar',
        data: {
            labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
            datasets: [{
                data: <?= json_encode(array_column($monthly_sales, 'monthly_sales')); ?>,
                backgroundColor: colors[0]
            }]
        },
        options: {
            legend: {
                display: false
            },
            scales: {
                xAxes: [{
                    barPercentage: 1,
                    categoryPercentage: 0.7
                }],
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });
}
</script>

<style>
a[href*="/analytics"] .nav-link {
    background: #ffffff26;
}
</style>