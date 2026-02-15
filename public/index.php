<?php
session_start();
require_once __DIR__ . '/../lib/db.php';
$pdo = get_db();
$doctors = $pdo->query('SELECT * FROM doctors LIMIT 6')->fetchAll(PDO::FETCH_ASSOC);
$isLoggedIn = isset($_SESSION['patient_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EasyConsult - Téléconsultation Médicale Moderne</title>
  <meta name="description" content="EasyConsult - Transformez votre accès à la santé. Consultez des médecins qualifiés en ligne, 24h/24. Paiement par USSD sécurisé. L'avenir de la télémédecine est ici.">
  
  <!-- Open Graph for Social Media -->
  <meta property="og:title" content="EasyConsult - Consulter un Médecin en Ligne">
  <meta property="og:description" content="Téléconsultation médicale moderne. Consultez partout, quand vous voulez. Santé accessible et abordable pour tous.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://easyconsult.wuaze.com">
  <meta property="og:image" content="https://easyconsult.wuaze.com/images/og-image.png">
  
  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="EasyConsult - Consulter un Médecin en Ligne">
  <meta name="twitter:description" content="Téléconsultation médicale moderne accessible à tous.">
  <meta name="twitter:image" content="https://easyconsult.wuaze.com/images/og-image.png">
  
  <link rel="stylesheet" href="./style.css">
</head>
<body>
  <?php include __DIR__ . '/../lib/nav.php'; ?>

  <!-- HERO SECTION -->
  <section class="hero">
    <div class="container">
      <div class="hero-content">
        <h1>Votre Santé, Notre Priorité</h1>
        <p>Consultez des médecins qualifiés depuis chez vous, simplement, rapidement et en toute confiance.</p>
        <div class="hero-buttons">
          <?php if (!$isLoggedIn): ?>
            <a href="register.php" class="btn btn-primary btn-lg">Commencer une Consultation</a>
            <a href="#apropos" class="btn btn-secondary btn-lg">En Savoir Plus</a>
          <?php else: ?>
            <a href="book.php" class="btn btn-primary btn-lg">Prendre un RDV</a>
            <a href="profile.php" class="btn btn-secondary btn-lg">Mon Profil</a>
          <?php endif; ?>
        </div>
        <div class="announcements mt-3">
          <div class="marquee"><span>Bienvenue au Centre Hospitalier Central — Restez en sécurité</span><span>Consultez en ligne 24/7</span><span>Paiement sécurisé via MTN / Orange</span></div>
        </div>
      </div>
    </div>
  </section>

  <!-- À PROPOS DE L'HÔPITAL -->
  <section id="apropos" class="content-wrapper" style="background: var(--white);">
    <div class="container">
      <h2 class="section-title">Centre Hospitalier Central de Bafoussam</h2>
      <p class="section-subtitle">Excellence Médicale depuis plus de 20 ans au service de la population camerounaise</p>

      <div class="grid grid-2">
        <div class="card">
          <div class="feature-box">
            <div class="feature-icon">🏥</div>
            <h3>Infrastructure Moderne</h3>
            <p>Équipements médicaux dernier cri, salles de consultation confortables, laboratoires accréditées et blocs opératoires aux normes internationales.</p>
          </div>
        </div>

        <div class="card">
          <div class="feature-box">
            <div class="feature-icon">👨‍⚕️</div>
            <h3>Équipe Expérimentée</h3>
            <p>Médecins généralistes, spécialistes et paramédicaux avec 10+ ans d'expérience chacun. Formation continue en protocoles modernes.</p>
          </div>
        </div>

        <div class="card">
          <div class="feature-box">
            <div class="feature-icon">📍</div>
            <h3>Localisation Centrale</h3>
            <p>Situé au cœur de Bafoussam, facilement accessible. Parking gratuit. Transports en commun à proximité. Service d'ambulance 24/24.</p>
          </div>
        </div>

        <div class="card">
          <div class="feature-box">
            <div class="feature-icon">⏰</div>
            <h3>Disponibilité 24/7</h3>
            <p>Consultations disponibles 7 jours sur 7. Service d'urgence permanent. Téléconsultation en ligne pour votre confort.</p>
          </div>
        </div>

        <div class="card">
          <div class="feature-box">
            <div class="feature-icon">💳</div>
            <h3>Paiements Sécurisés</h3>
            <p>Orange Money, MTN Mobile Money, espèces. Tarifs transparents. Factures numériques avec QR code et code-barres.</p>
          </div>
        </div>

        <div class="card">
          <div class="feature-box">
            <div class="feature-icon">🔒</div>
            <h3>Confidentialité Garantie</h3>
            <p>Vos données médicales sont protégées selon les standards internationaux. Conformité avec les normes RGPD et droit médical camerounais.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SERVICES -->
  <section id="services" class="content-wrapper">
    <div class="container">
      <h2 class="section-title">Nos Services</h2>
      <p class="section-subtitle">Une plateforme complète pour vos besoins de santé</p>

      <div class="grid grid-3">
        <a href="<?= $isLoggedIn ? 'book.php' : 'login.php' ?>" class="card" style="cursor: pointer; text-decoration: none; color: inherit; text-align: center;">
          <div class="feature-icon">📅</div>
          <h3>Téléconsultation</h3>
          <p>Réservez une consultation avec un médecin disponible. Paiement sécurisé. Facture immédiate.</p>
        </a>

        <a href="<?= $isLoggedIn ? 'profile.php' : 'login.php' ?>" class="card" style="cursor: pointer; text-decoration: none; color: inherit; text-align: center;">
          <div class="feature-icon">📊</div>
          <h3>Mon Dossier Médical</h3>
          <p>Gérez votre profil, groupe sanguin, IMC, documents médicaux. Historique complet de vos consultations.</p>
        </a>

        <a href="urgences.php" class="card" style="cursor: pointer; text-decoration: none; color: inherit; text-align: center;">
          <div class="feature-icon">🚑</div>
          <h3>Service d'Urgence</h3>
          <p>Accès prioritaire aux urgences. Localisation GPS. Appel direct. Chat WhatsApp. Paiement en ligne.</p>
        </a>
      </div>
    </div>
  </section>

  <!-- MÉDECINS -->
  <section id="medecins" class="content-wrapper" style="background: var(--white);">
    <div class="container">
      <h2 class="section-title">Notre Équipe Médicale</h2>
      <p class="section-subtitle">Des professionnels dédiés à votre bien-être</p>

      <div class="grid grid-4">
        <?php foreach($doctors as $d): 
          $avatar = 'https://i.pravatar.cc/200?img=' . ($d['id'] % 70) . '&size=200';
          $statusClass = $d['status'] === 'libre' ? 'status-libre' : 'status-occupé';
        ?>
          <div class="doctor-card">
            <img src="<?= htmlspecialchars($avatar) ?>" alt="<?= htmlspecialchars($d['name']) ?>" class="doctor-avatar">
            <div class="doctor-name"><?= htmlspecialchars($d['name']) ?></div>
            <div class="doctor-specialty"><?= htmlspecialchars($d['specialty']) ?></div>
            <p class="doctor-description">Expérience: 8+ ans</p>
            <span class="doctor-status <?= $statusClass ?>">
              <?= $d['status'] === 'libre' ? '🟢 Disponible' : '🔴 En consultation' ?>
            </span>
            <div style="width: 100%; margin-top: 1rem;">
              <p style="font-size: 0.75rem; color: var(--gray-500); margin-bottom: 0.75rem;">
                Horaires: <?= htmlspecialchars($d['start_time']) ?> - <?= htmlspecialchars($d['end_time']) ?>
              </p>
              <?php if ($isLoggedIn && $d['status'] === 'libre'): ?>
                <a href="book.php?doctor_id=<?= $d['id'] ?>" class="btn btn-primary btn-block">Réserver</a>
              <?php elseif (!$isLoggedIn): ?>
                <a href="login.php" class="btn btn-primary btn-block">Se Connecter</a>
              <?php else: ?>
                <button class="btn btn-secondary btn-block" disabled>Occupé</button>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div style="text-align: center; margin-top: 3rem;">
        <p style="color: var(--gray-500); margin-bottom: 1rem;">Besoin de voir plus de médecins?</p>
        <a href="<?= $isLoggedIn ? 'book.php' : 'login.php' ?>" class="btn btn-primary btn-lg">Voir Tous les Médecins</a>
      </div>
    </div>
  </section>

  <!-- ASSISTANT IA FLOTTANT -->
  <button id="float-assist" class="float-assist" aria-label="Assistant IA">💬</button>
  <div id="assist-panel" class="assist-panel">
    <h3>Assistant IA Médical</h3>
    <div style="background: #fff3cd; border-left: 4px solid #ff9800; padding: 0.75rem; border-radius: 0.25rem; margin-bottom: 1rem; font-size: 0.875rem; color: #8b6914;">
      ⚠️ <strong>Important:</strong> Cet outil est informatif uniquement. Il ne remplace pas une consultation médicale professionnelle.
    </div>
    <form id="symptom-form">
      <div class="form-group">
        <label>Décrivez vos symptômes</label>
        <textarea id="symptoms" placeholder="Ex: maux de tête, fièvre, toux..." required></textarea>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Analyser</button>
    </form>
    <div id="assist-result"></div>
  </div>

  <!-- FOOTER -->
  <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-section">
          <h4>EasyConsult</h4>
          <p>Plateforme de téléconsultation médicale au service de votre santé.</p>
          <p style="font-size: 0.875rem; margin-top: 1rem;">« Pour votre santé, nous sommes là »</p>
        </div>

        <div class="footer-section">
          <h4>Services</h4>
          <ul>
            <li><a href="#services">Téléconsultation</a></li>
            <li><a href="#">Urgences</a></li>
            <li><a href="#">Dossier Médical</a></li>
            <li><a href="#">Factures & Paiements</a></li>
          </ul>
        </div>

        <div class="footer-section">
          <h4>Hôpital</h4>
          <ul>
            <li><a href="#">À Propos</a></li>
            <li><a href="#">Localisation</a></li>
            <li><a href="#">Contact</a></li>
            <li><a href="#">Horaires</a></li>
          </ul>
        </div>

        <div class="footer-section">
          <h4>Informations</h4>
          <ul>
            <li><a href="#">Conditions d'Utilisation</a></li>
            <li><a href="#">Confidentialité</a></li>
            <li><a href="#">Support</a></li>
            <li><a href="#">FAQ</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; 2024 Centre Hospitalier Central - EasyConsult. Tous droits réservés.</p>
      </div>
    </div>
  </footer>

  <script src="./app.js"></script>
</body>
</html>
