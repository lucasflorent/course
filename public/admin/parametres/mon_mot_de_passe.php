<?php

declare(strict_types=1);

use App\Auth\AdminAuth;
use App\Config\Database;
use App\Support\Csrf;
use App\View\Layout;

require __DIR__ . '/../../../config/bootstrap.php';

AdminAuth::requireLogin();

$pdo = Database::pdo();
$adminId = AdminAuth::currentId();
$erreurs = [];
$succes = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $actuel = (string) ($_POST['mot_de_passe_actuel'] ?? '');
    $nouveau = (string) ($_POST['nouveau_mot_de_passe'] ?? '');
    $confirmation = (string) ($_POST['confirmation'] ?? '');

    if (mb_strlen($nouveau) < 4) {
        $erreurs[] = 'Le nouveau mot de passe doit contenir au moins 4 caractères.';
    }

    if ($nouveau !== $confirmation) {
        $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
    }

    if ($erreurs === []) {
        if (AdminAuth::changePassword($pdo, $adminId, $actuel, $nouveau)) {
            $succes = true;
        } else {
            $erreurs[] = 'Mot de passe actuel incorrect.';
        }
    }
}
Layout::debut('Mon mot de passe — Administration', ['admin' => true, 'adminNav' => 'pwAdmin', 'js' => '/assets/app.js']);
?>
<h3 style="margin-bottom:2px">Mot de passe administrateur</h3>
<p class="hint">Réservé à l'administrateur général du site.</p>
<?php if ($succes): ?>
    <p class="succes">Mot de passe mis à jour.</p>
<?php endif; ?>
<?php foreach ($erreurs as $erreur): ?>
    <p class="erreur"><?= htmlspecialchars($erreur) ?></p>
<?php endforeach; ?>
<form method="post" style="max-width:320px">
    <?= Csrf::champHtml() ?>
    <div class="field">
        <label for="mot_de_passe_actuel">Mot de passe actuel</label>
        <input type="password" id="mot_de_passe_actuel" name="mot_de_passe_actuel" required autofocus>
    </div>
    <div class="field">
        <label for="nouveau_mot_de_passe">Nouveau mot de passe</label>
        <input type="password" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" required>
    </div>
    <div class="field">
        <label for="confirmation">Confirmation</label>
        <input type="password" id="confirmation" name="confirmation" required>
    </div>
    <button type="submit" class="btn-block">Enregistrer</button>
</form>
<?php Layout::fin(); ?>
