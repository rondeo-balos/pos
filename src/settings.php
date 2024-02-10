<p class="p-2 bg-info bg-opacity-50 text-dark border border-primary border-5 border-top-0 border-bottom-0 border-end-0"><i class="fas fa-info-circle"></i> Please be mindful with updating the settings. It might break the system.</p>

<div class="row">

    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header">
                <strong>Site Settings</strong>
            </div>
            <div class="card-body">
                <form method="POST">
                <?php foreach($options as $option): ?>
                    <?php extract($option) ?>
                    <div class="mb-3">
                        <label for="<?= $name ?>" class="form-label"><?= $label ?></label>
                        <input type="text" class="form-control" id="<?= $name ?>" name="<?= $name ?>" value="<?= $value ?>" required>
                    </div>
                <?php endforeach; ?>

                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>
    </div>

</div>

<style>
    a[href*="/settings"] .nav-link {
        background: #ffffff26;
    }
</style>