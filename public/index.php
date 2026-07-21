<?php

declare(strict_types=1);

use App\Auth\SiteAuth;
use App\Config\Database;
use App\Support\Icone;
use App\View\Layout;

require __DIR__ . '/../config/bootstrap.php';

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motDePasse = (string) ($_POST['mot_de_passe'] ?? '');

    if (SiteAuth::attempt(Database::pdo(), $motDePasse)) {
        header('Location: /index.php');
        exit;
    }

    $erreur = 'Mot de passe incorrect.';
}

$authentifie = SiteAuth::check();

Layout::debut('Course de fond CM2', ['js' => '/assets/app.js']);
?>
<div class="loginwrap">
<div class="logincard">
<img class="logo-mark" src="/assets/images/logo.png" alt="Logo de l'école">
<?php if ($authentifie): ?>
    <h1>Bienvenue !</h1>
    <p>Choisis ce que tu veux faire.</p>
    <a class="btn btn-block" style="margin-bottom:.6rem" href="/saisie/index.php">Saisir des temps de passage</a>
    <a class="btn btn-secondary btn-block" href="/graphiques/export.php">Voir les graphiques et exporter</a>
    <div class="adminlink"><a href="/logout.php"><?= Icone::svg('box-arrow-left') ?>Se déconnecter</a></div>
<?php else: ?>
    <h1>Bonjour !</h1>
    <p>Entre le mot de passe pour continuer.</p>
    <?php if ($erreur): ?><p class="erreur"><?= htmlspecialchars($erreur) ?></p><?php endif; ?>
    <form method="post">
        <div class="field">
            <label for="mot_de_passe">Mot de passe</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe" required autofocus>
        </div>
        <button type="submit" class="btn-block">Valider</button>
    </form>
<?php endif; ?>
<div class="adminlink"><a href="/admin/login.php"><?= Icone::svg('gear') ?>Espace administrateur</a></div>
</div>
</div>
<?php Layout::fin(); ?>
