<table class="table table-bordered table-striped table-hover table-sm">
    <colgroup>
        <col width="1%">
        <col>
        <col>
        <col>
        <col>
    </colgroup>
    <thead>
        <tr>
            <th>User</th>
            <th>Timestamp</th>
            <th>File</th>
            <th>Title</th>
            <th>Log</th>
            <th>Args</th>
        </tr>
    </thead>
    <tbody class="table-group-divider align-middle">
        <?php
            if(!empty($logs)) {
                foreach($logs as $log) {
                    ?>
                        <tr>
                            <td><?= $log['user'] ?></td>
                            <td><?= date('F d, Y H:iA', strtotime($log['date_logged'])) ?></td>
                            <td><?= $log['file'] ?></td>
                            <td><?= $log['title'] ?></td>
                            <td><?= $log['log'] ?></td>
                            <td><?= $log['args'] ?></td>
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
    a[href*="/logs"] .nav-link {
        background: #ffffff26;
    }
</style>