<div class="d-flex align-items-center py-4 bg-body-tertiary content">
    <main class="form-signin w-100 m-auto">
        <form method="POST" autocomplete="off">
            <h1 class="h3 mb-3 fw-normal"><?= $site_info['app_name'] ?></h1>

            <?php if(isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong><?= $error ?></strong>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="form-floating">
                <input class="form-control" type="text" name="username" id="username" placeholder="Username" required>
                <label for="username">Username</label>
            </div>
            <div class="form-floating">
                <input class="form-control" type="password" name="password" placeholder="Password" required>
                <label>Password</label>
            </div>
            <!--<div class="form-check text-start my-3">
                <input class="form-check-input" type="checkbox" value="remember-me" id="remember-me">
                <label class="form-check-label" for="remember-me">
                    Remember me
                </label>
            </div>-->
            <button class="btn btn-primary w-100 py-2" type="submit">Log in</button>
        </form>
    </main>
</div>

<style>
    .content {
        min-height: 100vh;
    }
    .form-signin {
        max-width: 330px;
        padding: 1rem;
    }

    .form-signin .form-floating:focus-within {
        z-index: 2;
    }

    .form-signin input[type="text"] {
        margin-bottom: -1px;
        border-bottom-right-radius: 0;
        border-bottom-left-radius: 0;
    }

    .form-signin input[type="password"] {
        margin-bottom: 10px;
        border-top-left-radius: 0;
        border-top-right-radius: 0;
    }
</style>