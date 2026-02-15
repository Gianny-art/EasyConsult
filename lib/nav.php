<?php
// Simple reusable navigation include
// Place this file in lib/ and include it from pages in public/
if (!isset($_SESSION)) @session_start();
$is_patient_logged_in = isset($_SESSION['patient_id']) && !empty($_SESSION['patient_id']);
$is_doctor_logged_in = isset($_SESSION['doctor_id']) && !empty($_SESSION['doctor_id']);
$patient_name = $_SESSION['patient_name'] ?? '';
?>
<!-- FontAwesome for mobile icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<nav class="site-nav">
    <div class="nav-inner">
        <a class="nav-brand" href="index.php">🏥 EasyConsult</a>

        <button id="nav-toggle" class="nav-toggle" aria-label="Ouvrir le menu">☰</button>

        <ul class="nav-links" id="nav-links">
            <li><a href="index.php">Accueil</a></li>
            <?php if ($is_patient_logged_in): ?>
                <li><a href="book.php">Réserver</a></li>
                <li><a href="profile.php">👤 <?php echo htmlspecialchars($patient_name); ?></a></li>
                <li><a href="list_invoices.php">Factures</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            <?php else: ?>
                <li><a href="book.php">Réserver</a></li>
                <li><a href="profile.php">Mon profil</a></li>
                <li><a href="list_invoices.php">Factures</a></li>
                <li><a href="urgences.php">Urgences</a></li>
                <li><a href="login.php">Se connecter</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<style>
/* Navigation simple et responsive */
.site-nav{background:linear-gradient(90deg,#0066cc,#004a9f);color:#fff;padding:0.6rem 1rem;position:sticky;top:0;z-index:1000}
.nav-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:1rem}
.nav-brand{font-weight:800;color:#fff;text-decoration:none}
.nav-links{list-style:none;display:flex;gap:0.75rem;margin:0;padding:0}
.nav-links li a{color:#fff;text-decoration:none;padding:0.5rem 0.75rem;border-radius:0.5rem;display:inline-block;transition:all 0.3s ease}
.nav-links li a:hover{background:rgba(255,255,255,0.15)}
.nav-toggle{display:none;background:transparent;border:0;color:#fff;font-size:1.4rem}
@media(max-width:768px){
    /* hide top horizontal links and hamburger on small screens — use bottom icon bar instead */
    .nav-links{display:none !important}
    .nav-toggle{display:none !important}
    .nav-inner{justify-content:space-between}
    .nav-brand{font-size:1rem}
}

/* Mobile bottom icon bar */
.mobile-bottom-nav{display:none}
@media(max-width:768px){
    .mobile-bottom-nav{display:flex;position:fixed;left:0;right:0;bottom:0;height:56px;background:linear-gradient(90deg,#ffffff,#f8fafc);border-top:1px solid rgba(0,0,0,0.06);justify-content:space-around;align-items:center;z-index:1100}
    .mobile-bottom-nav a{display:flex;flex-direction:column;align-items:center;color:#374151;text-decoration:none;font-size:12px}
    .mobile-bottom-nav a .icon{font-size:20px}
    .site-nav{padding-bottom:0.6rem}
}
</style>

<script>
// Toggle mobile nav
document.addEventListener('DOMContentLoaded', function(){
    var t = document.getElementById('nav-toggle');
    var links = document.getElementById('nav-links');
    if(t && links){
        t.addEventListener('click', function(e){
            e.stopPropagation();
            links.classList.toggle('show');
        });

        document.addEventListener('click', function(e){
            if(!links.contains(e.target) && !t.contains(e.target)) links.classList.remove('show');
        });
    }
    // Create mobile bottom nav icons
    if (!document.getElementById('mobile-bottom')){
        var mb = document.createElement('div'); mb.id='mobile-bottom'; mb.className='mobile-bottom-nav';
        mb.innerHTML = '<a href="index.php"><div class="icon"><i class="fa fa-home"></i></div><div>Accueil</div></a><a href="book.php"><div class="icon"><i class="fa fa-calendar-days"></i></div><div>RDV</div></a><a href="profile.php"><div class="icon"><i class="fa fa-user"></i></div><div>Profil</div></a><a href="list_invoices.php"><div class="icon"><i class="fa fa-file-invoice-dollar"></i></div><div>Factures</div></a><a href="assistant.php"><div class="icon"><i class="fa fa-robot"></i></div><div>Assist</div></a>';
        document.body.appendChild(mb);
    }
});
</script>
