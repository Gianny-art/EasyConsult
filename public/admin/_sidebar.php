<?php
$current = basename($_SERVER['PHP_SELF']);
$items = [
    'index.php' => ['icon' => 'fa-chart-line', 'label' => '📊 Mon Dashboard'],
    'super.php' => ['icon' => 'fa-globe', 'label' => '🌐 Vue Globale'],
    'consultations.php' => ['icon' => 'fa-calendar-check', 'label' => '📋 Consultations'],
    'caisse.php' => ['icon' => 'fa-cash-register', 'label' => '💰 Caisse/Paiements'],
    'medecins.php' => ['icon' => 'fa-user-md', 'label' => '👨‍⚕️ Médecins'],
    'profil.php' => ['icon' => 'fa-user-circle', 'label' => '⚙️ Mon profil'],
];
?>
<div class="admin-sidebar">
    <h2>👨‍⚕️ EasyConsult</h2>
    <ul class="admin-menu">
        <?php foreach ($items as $file => $meta):
            $href = ($file === 'profil.php' || $file === 'index.php' || $file === 'super.php' || $file === 'consultations.php' || $file === 'caisse.php' || $file === 'medecins.php') ? './' . $file : './' . $file;
            $active = ($current === $file) ? ' active' : '';
        ?>
            <li><a href="<?php echo $href; ?>" class="<?php echo trim($active); ?>"><i class="fa <?php echo $meta['icon']; ?>"></i> <?php echo $meta['label']; ?></a></li>
        <?php endforeach; ?>
        <li><a href="../logout.php"><i class="fa fa-sign-out"></i> 🚪 Déconnexion</a></li>
    </ul>
</div>
