<script>
    const addCallback = function() {
        $('form input').val('');
        $('form #ID').val('-1');
    }

    const editCallback = function(data, ids) {
        ids.forEach(function(val, index) {
            $('form #' + val).val(data[val]);
        });
    }
</script>

<p class="p-2 bg-info bg-opacity-50 text-dark border border-primary border-5 border-top-0 border-bottom-0 border-end-0">You can also add or update products by using the scanner.</p>

<div class="row">
    <div class="col-md-8">
        <form method="GET" id="sort">
            <div class="row g-3 align-items-center">
                <div class="col-auto">
                    <label class="col-form-label">Sort By</label>
                </div>
                <div class="col-auto">
                    <select class="form-select" name="order">
                        <option value="na_a_z">Name (A-Z)</option>
                        <option value="na_z_a">Name (Z-A)</option>
                    </select>
                </div>
            </div>
        </form>
        <script>
            $('[name="order"]').val('<?= $_GET['order'] ?? 'na_a_z' ?>');
            $('[name="order"]').on('change', function(){
                $('#sort').submit();
            });
        </script>
    </div>
    <div class="col-md-4">
        <button class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#modal" id="add" onclick="addCallback()">Add New Product</button>
    </div>
</div>
<div class="clearfix"></div><br>

<table class="table table-bordered">
    <colgroup>
        <col width="1%">
        <col width="5%">
        <col>
        <col>
        <col>
        <col width="10%">
    </colgroup>
    <thead>
        <tr>
            <th>#</th>
            <th>Barcode</th>
            <th>Product</th>
            <th>Price Bought</th>
            <th>Resell Price</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody class="table-group-divider align-middle">
        <?php
            $index = 1;
            if(!empty($products)) {
                foreach($products as $product) {
                    ?>
                        <tr>
                            <td><?= $index ?></td>
                            <td><?= $generator->getBarcode($product['barcode'], $generator::TYPE_CODE_128, 1, 30); ?></td>
                            <td><?= $product['product'] ?></td>
                            <td>₱ <?= number_format($product['price_buy'], 2, '.', ',') ?></td>
                            <td>₱ <?= number_format($product['price_sell'], 2, '.', ',') ?></td>
                            <td class="text-center">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal" id="product<?= $product['barcode'] ?>" onclick='editCallback(<?= json_encode($product) ?>, ["ID", "barcode", "product", "price_buy", "price_sell"])'><i class="fa fa-pen"></i> Edit</button>
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
                    <h2 class="modal-title">Product Information</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="ID" id="ID" value="-1">    

                    <div class="mb-3">
                        <label for="barcode" class="form-label">Barcode</label>
                        <input type="number" class="form-control" id="barcode" name="barcode" required>
                    </div>

                    <div class="mb-3">
                        <label for="product" class="form-label">Product</label>
                        <input type="text" class="form-control" id="product" name="product" required>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <label for="price_buy" class="form-label">Price Bought</label>
                            <input type="number" step="any" class="form-control" id="price_buy" name="price_buy" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="price_sell" class="form-label">Resell Price</label>
                            <input type="number" step="any" class="form-control" id="price_sell" name="price_sell" required>
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
                if(response.route == '/products') {
                    $('#add').click();
                    $('#product'+response.barcode).click();
                    $('#barcode').val(response.barcode);
                }
            }
        });
    }, 1000);
</script>

<style>
    a[href*="/products"] .nav-link {
        background: #ffffff26;
    }
</style>