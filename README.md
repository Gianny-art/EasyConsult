EasyConsult - Prototype

Prototype minimal pour EasyConsult (PHP + MySQL, sans framework). Conçu pour fonctionner sous WAMP.

Prérequis:
- PHP 8+
- WAMP (Apache + MySQL + PHP) ou équivalent

Configuration rapide:

1. Ouvrez `lib/config.php` et ajustez les paramètres MySQL (`db_host`, `db_name`, `db_user`, `db_pass`). Le nom de base par défaut est `easyconsult`.

2. Initialisez la base de données et les tables (depuis le dossier du projet) :

```powershell
php lib/init_db.php
```

Cette commande créera la base `easyconsult` (si elle n'existe pas) et toutes les tables nécessaires.

3. Lancez le serveur de développement (ou utilisez Apache/WAMP):

```powershell
php -S localhost:8080 -t public
```

4. Ouvrez http://localhost:8080

Configuration avec WAMP/Apache:
- **Option 1 (recommandée)**: Apache accédera directement `http://localhost/EasyConsult/` car les chemins sont maintenant relatifs au dossier public/
- **Option 2**: Configurez un VirtualHost qui pointe vers le dossier `public/` du projet
- **Option 3**: Le fichier `.htaccess` à la racine du projet redirige automatiquement vers `public/` si mod_rewrite est activé

Notes importantes:
- Les paiements sont simulés pour le prototype (voir `public/webhook_stub.php`).
- Les QR codes utilisent l'API Google Charts (pour prototype). Pour production, générez et stockez localement.
- Les images de code-barres sont générées par PHP GD (simple rendu texte pour prototype).

