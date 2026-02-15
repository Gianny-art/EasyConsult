<?php
require_once __DIR__ . '/../lib/db.php';
session_start();

// Hospital emergency contact information
$hospital_name = 'Centre Hospitalier Central de Bafoussam';
$hospital_phone = '+237612345678';
$hospital_whatsapp = '+237612345678';
$hospital_lat = 5.7626;  // Approximate coordinates for Bafoussam
$hospital_lng = 10.4157;
$hospital_address = 'Rue Principale, Bafoussam, Cameroun';

// Emergency services
$emergency_services = [
    [
        'name' => 'Urgences Générales',
        'description' => 'Services d\'urgence 24/7 pour toutes situations d\'urgence',
        'icon' => '🚑',
        'phone' => '+237612345678'
    ],
    [
        'name' => 'Traumatologie',
        'description' => 'Accidents, fractures, blessures graves',
        'icon' => '🦴',
        'phone' => '+237612345679'
    ],
    [
        'name' => 'Cardiologie d\'Urgence',
        'description' => 'Crises cardiaque, douleurs thoraciques',
        'icon' => '❤️',
        'phone' => '+237612345680'
    ],
    [
        'name' => 'SAMU Pédiatrique',
        'description' => 'Urgences pour enfants et nourrissons',
        'icon' => '👶',
        'phone' => '+237612345681'
    ],
    [
        'name' => 'Intoxications',
        'description' => 'Empoisonnement, surdose, réactions allergiques',
        'icon' => '☠️',
        'phone' => '+237612345682'
    ],
    [
        'name' => 'Maternité d\'Urgence',
        'description' => 'Complications de grossesse, accouchement d\'urgence',
        'icon' => '👩‍⚕️',
        'phone' => '+237612345683'
    ]
];

// Emergency tips
$emergency_tips = [
    'Composez le 112 ou le numéro local pour une ambulance d\'urgence',
    'Décrivez clairement la situation au téléphone',
    'Préparez les informations médicales du patient (allergies, médicaments)',
    'Éloignez-vous de la source de danger si possible',
    'Appliquez les gestes de premiers secours en attendant l\'ambulance',
    'Gardez le calme et restez en ligne'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urgences - EasyConsult</title>
    <meta name="description" content="Service d'urgences EasyConsult. Accédez à une consultation d'urgence 24h/24 en ligne. Médecins qualifiés disponibles immédiatement.">
    <meta property="og:title" content="EasyConsult - Service d'Urgences">
    <meta property="og:description" content="Consulter un médecin d'urgence en ligne maintenant. Disponible 24h/24, 7j/7.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://easyconsult.wuaze.com/urgences.php">
    <link rel="stylesheet" href="./style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .emergency-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #fee2e2 0%, #fff5f5 100%);
        }

        .emergency-hero {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
            animation: slideDown 0.5s ease-out;
        }

        .emergency-hero h1 {
            font-size: 2.5rem;
            margin: 0 0 0.5rem 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .emergency-hero p {
            margin: 0.5rem 0 0 0;
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .emergency-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .action-card {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.5s ease-out;
            transition: all 0.3s ease;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .action-card h3 {
            color: #dc2626;
            margin-top: 0;
            font-size: 1.3rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-direction: column;
        }

        .btn-emergency {
            padding: 1rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .btn-call {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
        }

        .btn-call:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 38, 38, 0.4);
        }

        .btn-whatsapp {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-whatsapp:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }

        .map-section {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.5s ease-out 0.1s both;
        }

        .map-section h2 {
            color: var(--primary);
            margin-top: 0;
        }

        .map-container {
            width: 100%;
            height: 400px;
            border-radius: 1rem;
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .map-info {
            background: #f9fafb;
            border-left: 4px solid var(--primary);
            padding: 1.5rem;
            border-radius: 0.75rem;
        }

        .map-address {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .services-section {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.5s ease-out 0.2s both;
        }

        .services-section h2 {
            color: var(--primary);
            margin-top: 0;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .service-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fef08a 100%);
            border-left: 4px solid #dc2626;
            border-radius: 0.75rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .service-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .service-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .service-name {
            font-weight: 600;
            color: #b45309;
            margin-bottom: 0.5rem;
        }

        .service-description {
            font-size: 0.9rem;
            color: #92400e;
            margin-bottom: 1rem;
        }

        .service-phone {
            display: inline-block;
            background: rgba(220, 38, 38, 0.1);
            color: #dc2626;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .service-phone:hover {
            background: rgba(220, 38, 38, 0.2);
        }

        .tips-section {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.5s ease-out 0.3s both;
        }

        .tips-section h2 {
            color: var(--primary);
            margin-top: 0;
        }

        .tips-list {
            display: grid;
            gap: 1rem;
        }

        .tip-item {
            background: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 1rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .tip-number {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            flex-shrink: 0;
        }

        .tip-text {
            color: #1f2937;
            line-height: 1.5;
        }

        .emergency-warning {
            background: rgba(220, 38, 38, 0.1);
            border: 2px solid #dc2626;
            border-radius: 1rem;
            padding: 1.5rem;
            margin: 2rem 0;
            animation: slideDown 0.5s ease-out;
        }

        .emergency-warning h3 {
            color: #dc2626;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .emergency-warning p {
            margin: 0.5rem 0 0 0;
            color: #b91c1c;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .emergency-hero h1 {
                font-size: 1.75rem;
            }

            .map-container {
                height: 300px;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../lib/nav.php'; ?>
    <div class="emergency-container">
        <!-- Emergency Hero -->
        <div class="emergency-hero">
            <h1>🚨 Urgences Médicales</h1>
            <p>Assistance d'urgence disponible 24/7</p>
        </div>

        <div class="emergency-content">
            <!-- Warning Banner -->
            <div class="emergency-warning">
                <h3>🚑 En cas d'urgence vitale</h3>
                <p>Composez immédiatement le <strong>112</strong> ou le <strong><?php echo htmlspecialchars($hospital_phone); ?></strong></p>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <div class="action-card">
                    <h3>☎️ Appeler les urgences</h3>
                    <p>Parlé directement à un opérateur d'urgence qui peut dépêcher une ambulance</p>
                    <div class="action-buttons">
                        <a href="tel:<?php echo htmlspecialchars($hospital_phone); ?>" class="btn-emergency btn-call">
                            📞 Appeler maintenant
                        </a>
                    </div>
                </div>

                <div class="action-card">
                    <h3>💬 WhatsApp Urgences</h3>
                    <p>Décrivez votre situation via WhatsApp pour une assistance rapide</p>
                    <div class="action-buttons">
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $hospital_whatsapp); ?>?text=Urgent:%20J'ai%20besoin%20d'une%20assistance%20médicale" class="btn-emergency btn-whatsapp" target="_blank">
                            💚 WhatsApp Urgences
                        </a>
                    </div>
                </div>

                <div class="action-card">
                    <h3>📍 Localisation</h3>
                    <p>Partager notre localisation avec l'ambulance ou votre contact</p>
                    <div class="action-buttons">
                        <a href="https://maps.google.com/?q=<?php echo number_format($hospital_lat, 6); ?>,<?php echo number_format($hospital_lng, 6); ?>" class="btn-emergency" style="background: linear-gradient(135deg, #0066cc, #0052a3); color: white;" target="_blank">
                            🗺️ Ouvrir la carte
                        </a>
                    </div>
                </div>
            </div>

            <!-- Map Section -->
            <div class="map-section">
                <h2>📍 Localisation du Centre Hospitalier</h2>
                <div class="map-container">
                    <iframe 
                        width="100%" 
                        height="100%" 
                        style="border:0;" 
                        loading="lazy" 
                        allowfullscreen="" 
                        referrerpolicy="no-referrer-when-downgrade"
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3973.4896837154944!2d10.41570842376154!3d5.762599929421945!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNcKwNDUnNDUuNCJOIDEwwrAyNCc1OC4xIkU!5e0!3m2!1sfr!2scm!4v1234567890123">
                    </iframe>
                </div>
                <div class="map-info">
                    <div class="map-address">
                        📍 <?php echo htmlspecialchars($hospital_address); ?>
                    </div>
                    <p style="margin: 0; color: #6b7280; font-size: 0.9rem;">
                        Pour les questions générales: <strong><?php echo htmlspecialchars($hospital_phone); ?></strong>
                    </p>
                </div>
            </div>

            <!-- Services Section -->
            <div class="services-section">
                <h2>🏥 Services d'Urgence Disponibles</h2>
                <div class="services-grid">
                    <?php foreach ($emergency_services as $index => $service): ?>
                        <div class="service-card">
                            <div class="service-icon"><?php echo htmlspecialchars($service['icon']); ?></div>
                            <div class="service-name"><?php echo htmlspecialchars($service['name']); ?></div>
                            <div class="service-description"><?php echo htmlspecialchars($service['description']); ?></div>
                            <a href="tel:<?php echo htmlspecialchars($service['phone']); ?>" class="service-phone">
                                📞 <?php echo htmlspecialchars($service['phone']); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Emergency Tips -->
            <div class="tips-section">
                <h2>💡 Conseils en cas d'urgence</h2>
                <div class="tips-list">
                    <?php foreach ($emergency_tips as $index => $tip): ?>
                        <div class="tip-item">
                            <div class="tip-number"><?php echo ($index + 1); ?></div>
                            <div class="tip-text"><?php echo htmlspecialchars($tip); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Return Button -->
            <div style="text-align: center; padding: 2rem 0;">
                <a href="index.php" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 1.1rem;">
                    ← Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</body>
</html>
