<?php

declare(strict_types=1);

use App\Auth\AdminAuth;
use App\Config\Database;
use App\Support\Icone;
use App\View\Layout;

require __DIR__ . '/../../config/bootstrap.php';

if (AdminAuth::check()) {
    header('Location: /admin/index.php');
    exit;
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = (string) ($_POST['identifiant'] ?? '');
    $motDePasse = (string) ($_POST['mot_de_passe'] ?? '');

    if (AdminAuth::attempt(Database::pdo(), $identifiant, $motDePasse)) {
        header('Location: /admin/index.php');
        exit;
    }

    $erreur = 'Identifiant ou mot de passe incorrect.';
}
Layout::debut('Administration — Course de fond CM2', ['js' => '/assets/app.js']);
?>
<div class="loginwrap">
<div class="logincard">
<img class="logo-mark" src="/assets/images/logo.png" alt="Logo de l'école">
<h3>Espace administrateur</h3>
<p>Identifiants distincts du mot de passe de la classe.</p>
<?php if ($erreur): ?><p class="erreur"><?= htmlspecialchars($erreur) ?></p><?php endif; ?>
<form method="post">
    <div class="field">
        <label for="identifiant">Identifiant</label>
        <input type="text" id="identifiant" name="identifiant" required autofocus>
    </div>
    <div class="field">
        <label for="mot_de_passe">Mot de passe</label>
        <input type="password" id="mot_de_passe" name="mot_de_passe" required>
    </div>
    <button type="submit" class="btn-block">Se connecter</button>
</form>
<div class="adminlink"><a href="/index.php"><?= Icone::svg('arrow-left') ?>Retour à l'appli</a></div>
</div>
</div>
<?php Layout::fin(); ?>
