<table class="table table-bordered">
    <colgroup>
        <col width="5%">
        <col>
        <col>
        <col>
        <col>
        <col>
        <col width="10%">
    </colgroup>
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Total</th>
            <th>Cash</th>
            <th>Change</th>
            <th>Date Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody class="table-group-divider align-middle">
        <?php
            if(!empty($orders)) {
                foreach($orders as $order) {
                    extract($order);
                    ?>
                        <tr>
                            <th><?= $ID ?></th>
                            <td><?= $customer_name ?></td>
                            <td>₱ <?= number_format($total, 2, '.', ',') ?></td>
                            <td>₱ <?= number_format($cash, 2, '.', ',') ?></td>
                            <td>₱ <?= number_format($changed, 2, '.', ',') ?></td>
                            <td><?= date('F d, Y H:i A', strtotime($date_ordered)) ?></td>
                            <td class="text-center">
                                <a href="<?= $site_info['url'] ?>/print/<?= $ID ?>" target="_blank" class="btn btn-primary"><i class="fa fa-eye"></i> View Receipt</button>
                            </td>
                        </tr>
                    <?php
                }
            } else {
                ?><tr><td colspan="100">No Data</td></tr><?php
            }
        ?>
    </tbody>
</table>

<?= $this->fetch('includes/pagination.php', ['pagination' => $pagination]); ?>

<style>
    a[href*="/orders"] .nav-link {
        background: #ffffff26;
    }
</style>