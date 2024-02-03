<p class="p-2 bg-info bg-opacity-50 text-dark border border-primary border-5 border-top-0 border-bottom-0 border-end-0"><i class="fas fa-info-circle"></i> Use the barcode scanner to add the product to cart.</p>

<div class="row">

    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header">
                <strong>Cart</strong>
            </div>
            <div class="card-body">

                <table class="table table-bordered table-lg">
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

                <form method="POST" id="saveOrder">
                    <div class="mb-3">
                        <label for="customer_name" class="form-label">Name</label>
                        <input type="text" class="form-control form-control-lg" id="customer_name" name="customer_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="customer_number" class="form-label">Mobile No.</label>
                        <input type="text" class="form-control form-control-lg" id="customer_number" name="customer_number" aria-describedby="numberHelp" >
                        <div id="numberHelp" class="form-text">Not required, you can leave blank if necessary</div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" id="checkout">Checkout <strong>[ENTER]</strong></button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>

<div class="modal fade" tabindex="-1" id="modal" aria-labelledby="modal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="verifyPurchase">
                <div class="modal-header">
                    <h2 class="modal-title">Please enter cash</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="customer_name" id="customer_name">
                    <input type="hidden" name="customer_number" id="customer_number">

                    <div class="mb-3">
                        <input type="number" step="any" class="form-control form-control-lg" id="cash" name="cash" value="0" required>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close <strong>[ESC]</strong></button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" tabindex="-1" id="verification" aria-labelledby="verification" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!--<div class="modal-header">
                <h2 class="modal-title text-center" id="message"></h2>
                <button type="button" class="btn-close" data-bs-dismiss="verification" aria-label="Close"></button>
            </div>-->
            <div class="modal-body">
                <h2 class="text-center" id="message"></h2>
                <h1 class="text-center" id="change"></h1>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="verification">Proceed <strong>[ESC]</strong></button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="verification" id="print">Print Receipt [P]</button>
            </div>
        </div>
    </div>
</div>

<script>
    var lastOrder = 0;
    var total = 0;
    var hash = '';
    setInterval(function() {
        $.ajax('<?= $site_info['url'] ?>/fetchCart', {
            method: 'POST',
            data: [],
            success: function(response) {
                //console.log(response);
                //var data = JSON.parse(response);
                $('#cart_data').html('<tr><td colspan="100">Waiting for the scanner...</td></tr>');
                $('#total').html('0');
                if(Object.keys(response).length > 0 ) {
                    $('#cart_data').html('');
                    total = 0;
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

                    hash = btoa($('#cart_data').html());
                }
            }
        });
    }, 500);

    $('#saveOrder').submit(function(e) {
        e.preventDefault();

        if(total <= 0) {
            alert('No items in the cart');
        } else if($('#customer_name').length > 0) {
            $('#verifyPurchase #customer_name').val($('#saveOrder #customer_name').val());
            $('#verifyPurchase #custommer_number').val($('#saveOrder #custommer_number').val());

            $('#modal').modal('show');
        }
    });

    $('#modal').on('shown', function(){
        alert();
        $('#cash').focus();
    });

    $('#verifyPurchase').submit(function(e) {
        e.preventDefault();

        $.ajax('<?= $site_info['url'] ?>/verifyPurchase', {
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                lastOrder = response.order;
                $('#message').html(response.message);
                $('#change').html(parseFloat(response.change).toFixed(2));
                $('#modal').modal('hide');
                $('#verification').modal('show');
                if(response.change > -1) {
                    $('#verifyPurchase input').val('');
                    $('#saveOrder input').val('');
                }
            }
        });
    });

    $(document).ready(function()  {
        $(document).keydown(function(e)  {
            var code = (e.keyCode ? e.keyCode : e.which);
            if(code==80) { // keycode P
                $('#verification').modal('hide');
                $('#print').click();
            } else if(code==13) {
                $('#checkout').click();
            }
        });
    });

    $('#print').click(function(e){
        window.open('<?= $site_info['url'] ?>/print/'+lastOrder);
    });
</script>

<style>
    a[href*="/"]:nth-of-type(1) .nav-link {
        background: #ffffff26;
    }
</style>