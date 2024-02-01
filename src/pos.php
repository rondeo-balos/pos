<p class="p-2 bg-info bg-opacity-50 text-dark border border-primary border-5 border-top-0 border-bottom-0 border-end-0"><i class="fas fa-info-circle"></i> Use the barcode scanner to add the product to cart.</p>

<div class="row">

    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header">
                <strong>Cart</strong>
            </div>
            <div class="card-body">

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Barcode</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="table-group-divider align-middle" id="cart_data"></tbody>
                </table>

            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">TOTAL</h5>
                <h6 class="h1" id="total">0</h6>
            </div>
        </div><br>

        <div class="card">
            <div class="card-header">
                <strong>Customer Details</strong>
            </div>
            <div class="card-body">

                <div class="mb-3">
                    <label for="customer_name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                </div>

                <div class="mb-3">
                    <label for="customer_number" class="form-label">Mobile No.</label>
                    <input type="text" class="form-control" id="customer_number" name="customer_number" aria-describedby="numberHelp" >
                    <div id="numberHelp" class="form-text">Not required, you can leave blank if necessary</div>
                </div>

                <button type="submit" class="btn btn-primary">Checkout <strong>[ENTER]</strong></button>
            </div>
        </div>
    </div>

</div>

<script>
    setInterval(function() {
        $.ajax('<?= $site_info['url'] ?>/fetchCart', {
            method: 'POST',
            data: [],
            success: function(response) {
                //console.log(response);
                //var data = JSON.parse(response);
                $('#cart_data').html('<tr><td colspan="100">Waiting for the scanner...</td></tr>');
                if(Object.keys(response).length > 0 ) {
                    $('#cart_data').html('');
                    var total = 0;
                    response.forEach( function(value, index) {
                        var subtotal = value['price_sell']*value['quantity'];
                        total += subtotal;
                        $('#cart_data').append(`<tr>
                            <td>${value['barcode']}</td>
                            <td>${value['product']}</td>
                            <td>${value['price_sell']}</td>
                            <td>${value['quantity']}</td>
                            <td>${parseFloat(subtotal).toFixed(2)}</td>
                        </tr>`);
                    } );

                    $('#total').html(parseFloat(total).toFixed(2));
                }
            }
        });
    }, 500);
</script>

<style>
    a[href*="/"]:nth-of-type(1) .nav-link {
        background: #ffffff26;
    }
</style>