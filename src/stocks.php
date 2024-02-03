<script>
    const addCallback = function() {
        $('form input').val('');
        $('form #ID').val('-1');
    }

    const editCallback = function(data, ids) {
        ids.forEach(function(val, index) {
            $('form #' + val).val(data[val]);
        });
        $('form #quantity').val('1');
    }
</script>

<p class="p-2 bg-info bg-opacity-50 text-dark border border-primary border-5 border-top-0 border-bottom-0 border-end-0">To automatically add stocks, scan the product directly using a bar code scanner.</p>
<table class="table table-bordered">
    <colgroup>
        <col width="1%">
        <col width="5%">
        <col>
        <col>
        <col width="10%">
        <col width="15%">
        <col width="10%">
    </colgroup>
    <thead>
        <tr>
            <th>#</th>
            <th>Barcode</th>
            <th>Product</th>
            <th>Price</th>
            <th>Stocks</th>
            <th>Last Stocked</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody class="table-group-divider align-middle">
        <?php
            $index = 1;
            if(!empty($stocks)) {
                foreach($stocks as $stock) {
                    ?>
                        <tr>
                            <td><?= $index ?></td>
                            <td><img src="https://barcode.tec-it.com/barcode.ashx?data=<?= $stock['barcode'] ?>" class="img-fluid"></td>
                            <td><?= $stock['product'] ?></td>
                            <td>₱ <?= number_format($stock['price_sell'], 2, '.', ',') ?></td>
                            <td><?= $stock['count'] ?></td>
                            <td><?= date('F d, Y H:i A') ?></td>
                            <td class="text-center">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal" id="product<?= $stock['barcode'] ?>" onclick='editCallback(<?= json_encode($stock) ?>, ["ID", "barcode", "product", "price_buy", "price_sell", "count", "last_stock"])'><i class="fa fa-plus"></i> Re-stock</button>
                            </td>
                        </tr>
                    <?php
                    $index ++;
                }
            } else {
                ?><tr><td colspan="100">No Data</td></tr><?php
            }
        ?>
    </tbody>
</table>

<?= $this->fetch('includes/pagination.php', ['pagination' => $pagination]); ?>

<div class="modal fade" tabindex="-1" id="modal" aria-labelledby="modal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h2 class="modal-title">Stock Information</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="ID" id="ID" value="-1">    

                    <div class="mb-3">
                        <label for="barcode" class="form-label">Barcode</label>
                        <input type="number" class="form-control" id="barcode" name="barcode" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="product" class="form-label">Product</label>
                        <input type="text" class="form-control" id="product" name="product" disabled>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <label for="price_buy" class="form-label">Price Bought</label>
                            <input type="number" class="form-control" id="price_buy" name="price_buy" disabled>
                        </div>
                        <div class="col-sm-6">
                            <label for="price_sell" class="form-label">Resell Price</label>
                            <input type="number" class="form-control" id="price_sell" name="price_sell" disabled>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <label for="price_buy" class="form-label">Current Stock</label>
                            <input type="number" class="form-control" id="count" name="count" disabled>
                        </div>
                        <div class="col-sm-6">
                            <label for="price_sell" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" required>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    setInterval(function() {
        $.ajax('<?= $site_info['url'] ?>/getAlert', {
            method: 'POST',
            data: [],
            success: function(response) {
                console.log(response);
                if(response) {
                    if(response.route == '/stocks') {
                        $('#product'+response.barcode).click();
                    }
                }
            }
        });
    }, 1000);
</script>

<style>
    a[href*="/stocks"] .nav-link {
        background: #ffffff26;
    }
</style>