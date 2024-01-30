<nav aria-label="...">
    <ul class="pagination">
        <li class="page-item <?= $pagination['prev_class'] ?>">
            <a href='<?= $pagination['prev_link'] ?>' class="page-link">Previous</a>
        </li>

        <?php foreach($pagination['pages'] as $page): ?>
            <li class="page-item <?= $page['class'] ?>">
                <a class="page-link" href="<?= $page['link'] ?>"><?= $page['index'] ?></a>
            </li>
        <?php endforeach; ?>

        <li class="page-item <?= $pagination['next_class'] ?>">
            <a class="page-link" href="<?= $pagination['next_link'] ?>">Next</a>
        </li>
    </ul>
</nav>