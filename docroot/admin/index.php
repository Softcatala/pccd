<?php

/**
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 * (c) Víctor Pàmies i Riudor <vpamies@gmail.com>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

require __DIR__ . '/../../src/common.php';

ini_set('memory_limit', '1024M');
set_time_limit(0);
session_start();

header('X-Robots-Tag: noindex');

if (isset($_POST['password']) && $_POST['password'] === getenv('WEB_ADMIN_PASSWORD')) {
    $_SESSION['auth'] = true;
    header('Location: /admin/');

    exit;
}

if (isset($_SESSION['auth'])) {
    if (isset($_GET['phpinfo'])) {
        phpinfo();

        exit;
    }

    if (isset($_GET['apc']) || isset($_GET['SCOPE']) || isset($_GET['IMG'])) {
        require __DIR__ . '/../../src/third_party/apc.php';

        exit;
    }

    if (isset($_GET['opcache'])) {
        require __DIR__ . '/../../src/third_party/opcache-gui.php';

        exit;
    }

    if (isset($_GET['spx'])) {
        header('Location: /?SPX_KEY=dev&SPX_UI_URI=/');

        exit;
    }

    if (isset($_GET['xhprof'])) {
        header('Location: /admin/xhprof/');

        exit;
    }
}
?><!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="theme-color" content="#495057">
    <title>Panell d'administració - Paremiologia catalana comparada digital</title>
    <style>
        body{font-family:system-ui,sans-serif;line-height:1.4;margin:20px;padding:0 10px;color:#dbdbdb;background:#202b38;text-rendering:optimizeLegibility}button,input,textarea{transition:background-color .1s linear,border-color .1s linear,color .1s linear,box-shadow .1s linear,transform .1s ease}h1{font-size:2.2em;margin-top:0}h1,h2,h3,h4,h5,h6{margin-bottom:12px}h1,h2,h3,h4,h5,h6,strong{color:#fff}b,h1,h2,h3,h4,h5,h6,strong,th{font-weight:600}button,input[type=button],input[type=checkbox],input[type=submit]{cursor:pointer}input:not([type=checkbox]),select{display:block}button,input,select,textarea{color:#fff;background-color:#161f27;font-family:inherit;font-size:inherit;margin-right:6px;margin-bottom:6px;padding:10px;border:none;border-radius:6px;outline:none}button,input:not([type=checkbox]),select,textarea{-webkit-appearance:none}textarea{margin-right:0;width:100%;box-sizing:border-box;resize:vertical}button,input[type=button],input[type=submit]{padding-right:30px;padding-left:30px}button:hover,input[type=button]:hover,input[type=submit]:hover{background:#324759}button:focus,input:focus,select:focus,textarea:focus{box-shadow:0 0 0 2px rgba(0,150,191,.67)}button:active,input[type=button]:active,input[type=checkbox]:active,input[type=submit]:active{transform:translateY(2px)}input:disabled{cursor:not-allowed;opacity:.5}::-webkit-input-placeholder{color:#a9a9a9}:-ms-input-placeholder{color:#a9a9a9}::-ms-input-placeholder{color:#a9a9a9}::placeholder{color:#a9a9a9}a{text-decoration:none;color:#fff}a:hover{text-decoration:underline}code,kbd{background:#161f27;color:#ffbe85;padding:5px;border-radius:6px}pre>code{padding:10px;display:block;overflow-x:auto}img{max-width:100%}hr{border:none;border-top:1px solid #dbdbdb}table{border-collapse:collapse;margin-bottom:10px;width:100%}td,th{padding:6px;text-align:left}th{border-bottom:1px solid #dbdbdb}tbody tr:nth-child(2n){background-color:#161f27}::-webkit-scrollbar{height:10px;width:10px}::-webkit-scrollbar-track{background:#161f27;border-radius:6px}::-webkit-scrollbar-thumb{background:#324759;border-radius:6px}::-webkit-scrollbar-thumb:hover{background:#415c73}p{margin:2em 0;}pre{overflow:auto;}
        details, details summary { color: yellowgreen; cursor: pointer; } details * { color: #dbdbdb; cursor: auto; } details a { cursor: pointer; } ul { margin: 0; } main { display: flex; gap: 3rem; flex-wrap: wrap; }
    </style>
</head>
<body>
    <h2>Panell d'administració<?php echo isset($_GET['test']) ? ' - informes' : ''; ?></h2>
    <hr>
<?php if (!isset($_SESSION['auth'])) { ?>
    <form method="post">
        <label for="password">Contrasenya:</label>
        <input type="password" id="password" name="password" autofocus>
        <input type="submit" value="Inicia sessió">
    </form><?php

    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    echo "<script>window.location.href = '/';</script>";
    echo '<p>Podeu visitar la pàgina principal de la PCCD a <a href=//pccd.dites.cat>https://pccd.dites.cat</a>.';

    exit;
}

session_write_close();

if (isset($_GET['test']) && $_GET['test'] !== '' && is_string($_GET['test'])) {
    $start_time = microtime(true);

    require __DIR__ . '/../../src/reports_common.php';

    $test_file = $_GET['test'];
    $test_functions = get_test_functions();
    if (isset($test_functions[$test_file])) {
        require __DIR__ . '/../../src/reports/' . $test_file . '.php';
        foreach ($test_functions[$test_file] as $function_name) {
            $function_name();

            // Delivering partial output to the browser may not provide a better UX.
            // ob_flush();
            // flush();
        }
    }

    echo "<p>[<a href='/admin/'>Torna endarrere</a>]</p>";

    $end_time = microtime(true);
    $total_time = (string) round($end_time - $start_time, 4);
    echo '<hr>';
    echo '<footer><small>Pàgina generada en ' . str_replace('.', ',', $total_time) . ' segons.</small></footer>';
    echo '</body>';
    echo '</html>';

    exit;
}
?>
<main>
<article>
<h3>Informes</h3>
    <ul>
        <li><a href="?test=dates">Dates</a></li>
        <li><a href="?test=deiec">DEIEC</a></li>
        <li><a href="?test=dsff">DSFF</a></li>
        <li><a href="?test=softcatala_sinonims">DSSC</a></li>
        <li><a href="?test=editorials">Editorials</a></li>
        <li><a href="?test=equivalents">Equivalents</a></li>
        <li><a href="?test=espais">Espais</a></li>
        <li><a href="?test=fonts">Fonts</a></li>
        <li><a href="?test=imatges">Imatges</a></li>
        <li><a href="?test=languagetool">LanguageTool</a></li>
        <li><a href="?test=longitud">Longitud</a></li>
        <li><a href="?test=majuscules">Majúscules</a></li>
        <li><a href="?test=puntuacio">Puntuació</a></li>
        <li><a href="?test=repeticions_caracters">Repeticions de caràcters</a></li>
        <li><a href="?test=repeticions_modismes">Repeticions de modismes</a></li>
        <li><a href="?test=repeticions_paremiotipus">Repeticions de paremiotipus</a></li>
        <li><a href="?test=sinonims">Sinònims</a></li>
        <li><a href="?test=urls">URLs</a></li>
    </ul>
</article>
<article>
    <h3>Estadístiques</h3>
    <ul>
        <li><a href="?test=stats_cerques">Cerques</a></li>
        <li><a href="?test=stats_autors">Autors</a></li>
        <li><a href="?test=stats_editorials">Editorials</a></li>
        <li><a href="?test=stats_equivalents">Equivalents</a></li>
        <li><a href="?test=stats_llocs">Llocs</a></li>
        <li><a href="?test=stats_obres">Obres</a></li>
        <li><a href="?test=stats_paremiotipus">Paremiotipus</a></li>
    </ul>
</article>
<article>
    <h3>Monitorització</h3>
    <ul>
        <?php echo function_exists('apcu_enabled') && apcu_enabled() ? '<li><a href="?apc">APCu</a></li>' : '<li><b>Atenció:</b> APCu no està habilitat, el rendiment de la pàgina es veurà afectat.</li>'; ?>
        <?php echo function_exists('opcache_get_status') && is_array(opcache_get_status()) ? '<li><a href="?opcache">OPcache</a></li>' : '<li><b>Atenció:</b> OPcache no està habilitat, el rendiment de la pàgina es veurà afectat.</li>'; ?>
        <?php echo function_exists('phpinfo') ? '<li><a href="?phpinfo">phpinfo</a></li>' : ''; ?>
        <?php echo function_exists('spx_profiler_start') ? '<li><a href="?spx">SPX</a></li>' : ''; ?>
        <?php echo function_exists('xhprof_enable') ? '<li><a href="?xhprof">XHProf</a></li>' : ''; ?>
        <li><a href="?test=stats_mysql">MariaDB</a></li>
    </ul>
</article>
</main>
<p>[<a href='?logout'>Tanca la sessió</a>]</p>
<hr>
<footer>
<small>
    Última base de dades: <?php require __DIR__ . '/../../tmp/db_date.txt'; ?>
<?php
if (function_exists('apcu_cache_info')) {
    $cache = apcu_cache_info(true);
    assert($cache !== false && is_int($cache['start_time']));
    echo '<br>Última arrencada: ' . date('Y/m/d H:i:s', $cache['start_time']);
} elseif (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    assert($status !== false);
    assert(is_int($status['opcache_statistics']['start_time']));
    echo '<br>Última arrencada: ' . date('Y/m/d H:i:s', $status['opcache_statistics']['start_time']);
}
$mysql_version = get_db()->getAttribute(PDO::ATTR_SERVER_VERSION);
$mysql_info = get_db()->getAttribute(PDO::ATTR_SERVER_INFO);
assert(is_string($mysql_version));
assert(is_string($mysql_info));
?>
    <br><?php echo 'PHP ' . PHP_VERSION . ', ' . apache_get_version() . ' (' . PHP_OS . '), ' . $mysql_version; ?>
    <br><?php echo 'MariaDB ' . $mysql_info; ?>
</small>
</footer>
</body>
</html>
