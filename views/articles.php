
<section aria-label="News list">
    <ul class="article-list">
<?php

if(!empty($this->newsData)){

    
    foreach($this->newsData as $i => $article) {
        ?>
        <li class="article-li">
            <article class="article-card">
                <header>
                    <?php if (isset($article['image_url'])): ?>
                
                        <a class="article-media" href="<?= htmlspecialchars($article['image_url']) ?>" aria-label="Read article: <?= htmlspecialchars($article['title']) ?>">
                            <img src="<?= htmlspecialchars($article['image_url']) ?>" alt="<?= htmlspecialchars($article['title']) ?>">
                        </a>
                    <?php endif; ?>
                    <h2 class="article-title">
                        <a href="/article/<?=$article['id'] ?>"><?= htmlspecialchars($article['title']) ?></a>
                    </h2>
                </header>
                <div class="article-body">
                    <p><?= htmlspecialchars(mb_strimwidth($article['description'] ?? '', 0, 160, '…')) ?></p>
                </div>
                <footer>
                <a href="/article/<?=$article['id'] ?>" class="read-link">Read more <span aria-hidden="true">→</span></a>
            </footer>
            </article>
        </li>
<?php   
    }
}
?>
   </ul>
</section>


