<?php
/*
 * Idempotent demo seed for the test stand: theme + layout, root page, start
 * page with an article holding the "Website check" content element.
 *
 *   php seed.php <dsn> <user> <password> <language> <admin e-mail>
 */
if ('--count' === ($argv[1] ?? '')) {
    // php seed.php --count <dsn> <user> <password> → number of stored checks (entrypoint helper)
    $pdo = new PDO($argv[2], $argv[3], $argv[4], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo (int) $pdo->query('SELECT COUNT(*) FROM tl_neo_websitecheck')->fetchColumn();

    return;
}

[, $dsn, $user, $password, $language, $adminEmail] = $argv + [null, null, null, null, 'de', 'admin@example.com'];

$pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$now = time();

if ((int) $pdo->query("SELECT COUNT(*) FROM tl_page WHERE type = 'root'")->fetchColumn() > 0) {
    echo "Pages exist, nothing to seed.\n";

    return;
}

$pdo->exec("INSERT INTO tl_theme (tstamp, name, author) VALUES ($now, 'Test', 'Admin')");
$theme = (int) $pdo->lastInsertId();

$modules = serialize([['mod' => 0, 'col' => 'main', 'enable' => 1]]);
$pdo->prepare('INSERT INTO tl_layout (pid, tstamp, name, `rows`, `cols`, `modules`, template, viewport, titleTag, framework, defaultImageDensities, lightboxSize) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
    ->execute([$theme, $now, 'Standard', '1rw', '1cl', $modules, 'fe_page', 'width=device-width, initial-scale=1.0', '', serialize(['layout.css', 'responsive.css', 'grid.css', 'reset.css', 'form.css', 'tinymce.css']), '', '']);
$layout = (int) $pdo->lastInsertId();

$page = $pdo->prepare('INSERT INTO tl_page (pid, sorting, tstamp, title, alias, type, language, fallback, dns, adminEmail, includeLayout, layout, published, robots, sitemap, includeChmod, chmod, cuser, cgroup, dateFormat, timeFormat, datimFormat, useSSL) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)');
$chmod = serialize(['u1', 'u2', 'u3', 'u4', 'u5', 'u6', 'g1', 'g2', 'g3', 'g4', 'g5', 'g6']);
$page->execute([0, 128, $now, 'Test website', 'test-website', 'root', $language, 1, '', $adminEmail, 1, $layout, 1, 'index,follow', 'map_default', 1, $chmod, 1, 1, 'd.m.Y', 'H:i', 'd.m.Y H:i']);
$root = (int) $pdo->lastInsertId();

$page->execute([$root, 128, $now, 'de' === $language ? 'Website-Check' : 'Website check', 'index', 'regular', '', 0, '', '', 0, 0, 1, 'index,follow', 'map_default', 1, $chmod, 1, 1, '', '', '']);
$home = (int) $pdo->lastInsertId();

$page->execute([$root, 256, $now, 'de' === $language ? 'Kontakt' : 'Contact', 'kontakt', 'regular', '', 0, '', '', 0, 0, 1, 'index,follow', 'map_default', 1, $chmod, 1, 1, '', '', '']);
$contact = (int) $pdo->lastInsertId();

$page->execute([$root, 384, $now, 'de' === $language ? 'Datenschutz' : 'Privacy', 'datenschutz', 'regular', '', 0, '', '', 0, 0, 1, 'noindex,nofollow', 'map_never', 1, $chmod, 1, 1, '', '', '']);

$article = $pdo->prepare('INSERT INTO tl_article (pid, sorting, tstamp, title, alias, author, inColumn, published) VALUES (?, ?, ?, ?, ?, 1, ?, 1)');
$article->execute([$home, 128, $now, 'de' === $language ? 'Website-Check' : 'Website check', 'website-check', 'main']);
$homeArticle = (int) $pdo->lastInsertId();
$article->execute([$contact, 128, $now, 'de' === $language ? 'Kontakt' : 'Contact', 'kontakt-artikel', 'main']);

$content = $pdo->prepare('INSERT INTO tl_content (pid, ptable, sorting, tstamp, type, headline, text, url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$content->execute([
    $homeArticle,
    'tl_article',
    128,
    $now,
    'neo_websitecheck',
    serialize(['unit' => 'h1', 'value' => 'de' === $language ? 'Wie gut ist Ihre Website?' : 'How good is your website?']),
    'de' === $language
        ? '<p>Adresse eingeben, zwei Minuten warten, Schulnote und PDF-Bericht erhalten.</p>'
        : '<p>Enter your address, wait two minutes, receive a school grade and a PDF report.</p>',
    '{{link_url::'.$contact.'}}',
]);

echo "Seeded: root #$root, start page #$home (article #$homeArticle), contact #$contact.\n";
