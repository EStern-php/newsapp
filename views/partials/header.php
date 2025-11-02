
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Latest headlines curated by MyNewsApp.">
  <title><?= htmlspecialchars($pageTitle ?? 'NewsApp', ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="/css/style.css?v=1">
  <link rel="icon" href="/assets/favicon.ico" type="image/x-icon">
</head>
<body>
  <header class="site-header">
    <div class="container">
      <h1 class="site-title"><a href="/" class="logo-link">News App</a></h1>
      <nav class="site-nav" aria-label="Main navigation">
        <ul>
          <li><a href="/articles">Articles</a></li>
        </ul>
      </nav>
    
    <form class="site-search" action="/search" method="get" role="search" aria-label="Search articles">
        <input type="text" name="q" placeholder="Search news…" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">

        <select name="source" aria-label="Source">
            <option value="">All sources</option>
            <?php foreach (($this->sourceList ?? []) as $s): ?>
            <option value="<?= htmlspecialchars($s['source_id']) ?>"
                <?= (($_GET['source'] ?? '') === $s['source_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <select name="country" aria-label="Country">
            <option value="">All countries</option>
            <?php foreach (($this->countriesList ?? []) as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>"
                <?= (($_GET['country'] ?? '') === $c) ? 'selected' : '' ?>>
                <?= htmlspecialchars(strtoupper($c)) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Search</button>
        </form>
    </div>

  </header>

  <main class="site-main container">