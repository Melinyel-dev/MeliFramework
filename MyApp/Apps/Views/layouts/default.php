<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="author" content="Melinyel">

    <link rel="canonical" href="<?= $_SERVER['REQUEST_URI'] ?>">

    <meta property="og:url" content="<?= $_SERVER['REQUEST_URI'] ?>" />
    <meta property="og:title" content="MeliFramework" />
    <meta property="og:site_name" content="<?= $GLOBALS['conf']['display_name'] ?>"/>

    <link rel="icon" type="image/png" href="/imgs/favicon.png">

    <link rel="stylesheet" href="/css/style.css" />

    <title>Template <?= FRAMEWORK ?></title>
</head>
<body>
    <header>
        <nav>
            <a href="http://melidev.evade-multimedia.net/" id="logo"><img class="inlineBlock" src="/imgs/logo.png" title="" src="" />&nbsp;<span class="inlineBlock"><?= FRAMEWORK ?></span></a>
            <a href="http://melidev.evade-multimedia.net/get/">Télécharger</a>
            <a href="http://melidev.evade-multimedia.net/community/">Communautée</a>
            <a href="http://melidev.evade-multimedia.net/docs/">Documentation</a>
            <a href="http://melidev.evade-multimedia.net/contribute/">Participer</a>
        </nav>
    </header>

    <?=$content?>

    <section class="signature centerTxt">
        <?= FRAMEWORK ?> est co-développé par<br />
        <strong>Anaeria</strong> <em>(lead)</em>, <strong>Sugatasei</strong> <em>(backend)</em>, <strong>Eloha</strong> <em>(frontend)</em>

        <img id="sign" src="/imgs/sign.png" title="" alt="" />
    </section>

    <div id="gradients"></div>
</body>
</html>