<div class="border text-center" style="max-width: 400px; margin-left: auto; margin-right: auto;">
    <h3><?= $site_info['store_name'] ?></h3>
    <p><?= $site_info['store_address'] ?></p>
    <p>Contact: <?= $site_info['contact'] ?></p>

    <table class="table table-borderless">
        <?php
            $date = date('F d, Y', strtotime($orders['date_ordered']));
            $time = date('H:i A', strtotime($orders['date_ordered']));
        ?>
        <tr>
            <th class="text-start">Invoice No: <?= $args['order'] ?></th>
            <th class="text-end"><?= $date ?></th>
        </tr>
        <tr>
            <th class="text-start">Cashier: <?= $cashier ?></th>
            <th class="text-end"><?= $time ?></th>
        </tr>
    </table>
    <table class="table table-borderless">
        <tr>
            <td>Quantity</td>
            <td>Description</td>
            <td>Price</td>
            <td>Subtotal</td>
        </tr>
        <?php foreach($cart as $item): ?>
            <tr>
                <td><?= $item['quantity'] ?></td>
                <td><?= $item['product'] ?></td>
                <td><?= $item['price_sell'] ?></td>
                <td><?= number_format((float)$item['quantity']*$item['price_sell'], 2, '.', ''); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="border m-2">
        <table class="table table-borderless">
            <tr>
                <th class="text-start">Vat: </th>
                <th class="text-end">0.00 </th>
            </tr>
            <tr>
                <th class="text-start">Total: </th>
                <th class="text-end"><?= $orders['total'] ?></th>
            </tr>
            <tr>
                <th class="text-start">Cash: </th>
                <th class="text-end"><?= $orders['cash'] ?></th>
            </tr>
            <tr>
                <th class="text-start">Change: </th>
                <th class="text-end"><?= $orders['changed'] ?></th>
            </tr>
        </table>
    </div>

    <p class="text-center">
        THIS IS YOUR OFFICIAL RECEIPT<br>
        <strong>Thank you! Come Again!</strong>
    </p>
</div>

<script>
    window.print();
    window.onfocus=function(){ window.close();}
</script>