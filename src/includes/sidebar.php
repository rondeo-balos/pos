<div class="side-navbar active-nav d-flex justify-content-between flex-wrap flex-column" id="sidebar">
    <ul class="nav flex-column text-white w-100">
        <div class="container-fluid py-3 border-bottom text-center mb-2 bg-dark">
            <strong class="h5 brand-title"><?= $site_info['app_name'] ?></strong>
        </div>
        <?php foreach( $menu as $item ): ?>
            <a href="<?= $site_info['url'].$item['route'] ?>">
                <li class="nav-link">
                    <span class="mx-2"><i class="<?= $item['icon'] ?>"></i> <?= $item['title'] ?></span> <i class="<?= $item['icon'] ?>"></i>
                </li>
            </a>
        <?php endforeach; ?>
        
        <a href="<?= $site_info['url'] ?>/logout">
            <li class="nav-link">
                <span class="mx-2"><i class="fas fa-sign-out-alt"></i> Logout</span> <i class="fas fa-sign-out-alt"></i>
            </li>
        </a>
        <li class="nav-link" id="menu-btn">
            <span class="mx-2"><i class="fas fa-angle-left"></i> Collapse</span> <i class="fas fa-angle-right"></i>
        </li>
    </ul>
</div>

<style>
    .side-navbar {
        width: 180px;
        height: 100%;
        position: fixed;
        margin-left: -130px;
        transition: 0.2s;
        background: #353a48;
    }

    .nav-link {
        cursor: pointer;
    }

    .nav a, .nav-link * {
        color: #fff;
        text-decoration: none !important;
    }

    .nav-link.active,
    .nav-link:active,
    .nav-link:focus,
    .nav-link:hover {
        background-color: #ffffff26;
    }

    .my-container {
        transition: 0.4s;
    }

    .active-nav {
        margin-left: 0;
    }

    .side-navbar .brand-title {
        visibility: hidden;
    }
    .side-navbar hr {
        display: none;
    }

    .active-nav .brand-title {
        visibility: visible;
    }
    .active-nav hr {
        display: block;
    }

    .side-navbar .nav-link > svg {
        display: block;
        margin-left: auto;
        width: 20px;
    }

    .side-navbar .nav-link > span {
        display: none;
    }

    .side-navbar.active-nav .nav-link > svg {
        display: none;
    }

    .side-navbar.active-nav .nav-link > span {
        display: block;
    }

    .my-container {
        margin-left: 50px;
        transition: 0.2s;
    }

    /* for main section */
    .active-cont {
        margin-left: 180px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function(){
        var menu_btn = document.querySelector("#menu-btn");
        var sidebar = document.querySelector("#sidebar");
        var container = document.querySelector(".my-container");
        menu_btn.addEventListener("click", () => {
            sidebar.classList.toggle("active-nav");
            container.classList.toggle("active-cont");
        });
    });
</script>