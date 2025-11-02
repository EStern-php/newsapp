<section aria-label="Article">
<?php

if(!empty($this->newsData)){
 
    ?>
    <article class="article-card">
        <header>
            <h2 class="article-title">
                <a href="/article/<?= $this->newsData['id'] ?>"><?= htmlspecialchars($this->newsData['title']) ?></a>
            </h2>
        </header>
        <div class="article-body">
            <p><?=  $this->newsData['content'] ?></p>
        </div>
    <footer class="article-footer">
      <div class="article-links">
        <a href="/articles/" class="read-link back-link">
          <span aria-hidden="true">←</span> Back
        </a>
        <a href="<?= htmlspecialchars($this->newsData['url']) ?>" class="read-link source-link">
          View original article <span aria-hidden="true">→</span>
        </a>
      </div>
    </footer>
    </article>
    <?php
    }
    ?>
</section>

<section id="comments" aria-label="Comments" class="comments-section">
  <h3 class="comments-title">Comments</h3>

  <?php if (!empty($this->comments)){ ?>
    <ul class="comment-list">
      <?php foreach ($this->comments as $comment) { ?>
        <li class="comment-item">
          <div class="comment-meta">
            <span class="comment-author"><?= htmlspecialchars($comment['author'] ?: 'Anonymous') ?></span>
            <time datetime="<?= htmlspecialchars($comment['created_at']) ?>"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($comment['created_at']))) ?></time>
          </div>
          <p class="comment-body"><?= nl2br(htmlspecialchars($comment['body_raw'])) ?></p>
        </li>
      <?php } ?>
    </ul>
  <?php } ?>

  <h4 class="comments-form-title">Add comment</h4>
  <form class="comment-form" method="post" action="/article/<?= $this->newsData['id'] ?>">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
    <!-- Honeypot -->
    <div class="hp" aria-hidden="true">
      <label>Homepage (leave empty)</label>
      <input type="text" name="website" tabindex="-1" autocomplete="off">
    </div>

    <div class="field-row">
      <label for="author">Namn (optional)</label>
      <input id="author" name="author" type="text" maxlength="80" placeholder="Your name">
    </div>

    <div class="field-row">
      <label for="comment">Comment</label>
      <textarea id="comment" name="comment" rows="5" maxlength="2000" required placeholder="Write comment"></textarea>
      <small class="muted">Max 2000 letters.</small>
    </div>

    <button type="submit" class="btn-primary">Send</button>
  </form>
</section>
        