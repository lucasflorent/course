<?php

declare(strict_types=1);

use App\Auth\AdminAuth;
use App\Auth\SiteAuth;
use App\Config\Database;
use App\Support\Csrf;
use App\View\Layout;

require __DIR__ . '/../../../config/bootstrap.php';

AdminAuth::requireLogin();

$pdo = Database::pdo();
$erreurs = [];
$succes = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $nouveau = (string) ($_POST['nouveau_mot_de_passe'] ?? '');
    $confirmation = (string) ($_POST['confirmation'] ?? '');

    if (mb_strlen($nouveau) < 4) {
        $erreurs[] = 'Le mot de passe doit contenir au moins 4 caractères.';
    }

    if ($nouveau !== $confirmation) {
        $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
    }

    if ($erreurs === []) {
        SiteAuth::setPassword($pdo, $nouveau);
        $succes = true;
    }
}
Layout::debut('Mot de passe site — Administration', ['admin' => true, 'adminNav' => 'pwSite', 'js' => '/assets/app.js']);
?>
<h3 style="margin-bottom:2px">Mot de passe du site</h3>
<p class="hint">Partagé par tous les enseignants et élèves pour ouvrir l'appli (saisie et exports).</p>
<?php if ($succes): ?>
    <p class="succes">Mot de passe mis à jour.</p>
<?php endif; ?>
<?php foreach ($erreurs as $erreur): ?>
    <p class="erreur"><?= htmlspecialchars($erreur) ?></p>
<?php endforeach; ?>
<form method="post" style="max-width:320px">
    <?= Csrf::champHtml() ?>
    <div class="field">
        <label for="nouveau_mot_de_passe">Nouveau mot de passe</label>
        <input type="password" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" required autofocus>
    </div>
    <div class="field">
        <label for="confirmation">Confirmation</label>
        <input type="password" id="confirmation" name="confirmation" required>
    </div>
    <button type="submit" class="btn-block">Enregistrer</button>
</form>
<?php Layout::fin(); ?>
