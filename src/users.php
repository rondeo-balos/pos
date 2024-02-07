
<script>
    const addCallback = function() {
        $('form input').val('');
        $('form #ID').val('-1');
        $('form #passwordhelp').css('display', 'none');
        $('form #password').attr('required','true');
    }

    const editCallback = function(data, ids) {
        ids.forEach(function(val, index) {
            $('form #' + val).val(data[val]);
            $('form #passwordhelp').css('display', 'block');
            $('form #password').removeAttr('required');
        });
    }
</script>

<button class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#modal" onclick="addCallback()">Add New User</button>
<div class="clearfix"></div><br>

<table class="table table-bordered">
    <colgroup>
        <col width="1%">
        <col>
        <col>
        <col>
        <col>
        <col width="10%">
    </colgroup>
    <thead>
        <tr>
            <th>#</th>
            <th>Username</th>
            <th>Name</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody class="table-group-divider align-middle">
        <?php
            $index = 1;
            if(!empty($users)) {
                foreach($users as $user) {
                    if($user['username'] == 'root') continue;
                    ?>
                        <tr>
                            <td><?= $index ?></td>
                            <td><?= $user['username'] ?></td>
                            <td><?= $user['name'] ?></td>
                            <td><?= $user['phone'] ?></td>
                            <td><?= $user['role'] ?></td>
                            <td class="text-center">
                                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal" onclick='editCallback(<?= json_encode($user) ?>, ["ID", "username", "name", "phone", "role"])'><i class="fa fa-pen"></i> Edit</a>
                                <form method="POST" action="./delete/users" style="display: inline;">
                                    <input type="hidden" name="ID" value="<?= $user['ID'] ?>">
                                    <input type="hidden" name="route" value="/users">
                                    <button type="submit" class="btn btn-danger"><i class="fa fa-trash"></i></button>
                                </form>
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
                    <h2 class="modal-title">User Information</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="ID" id="ID" value="-1">    

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="text" class="form-control" id="password" name="password" aria-describedby="passwordhelp" required>
                        <div id="passwordhelp" class="form-text">If you don't want to update the password, leave empty.</div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="cashier">Cashier</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
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

<style>
    a[href*="/users"] .nav-link {
        background: #ffffff26;
    }
</style>