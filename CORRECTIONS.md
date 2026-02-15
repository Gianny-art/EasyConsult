# Corrections appliquées - EasyConsult

## Problème identifié
Le projet utilisait des chemins absolus (`/style.css`, `/login.php`, etc.) qui ne fonctionnaient que si tous lesfichiers se trouvaient à la racine du serveur web. Cependant, les fichiers sont organisés dans un dossier `public/`, causant des erreurs de chemins, l'absence de CSS, et l'impossibilité de naviguer entre les pages.

## Solutions mises en place

### 1. **Chemins CSS (Relatifs)**
- ✓ Tous les fichiers PHP: `/style.css` → `./style.css`
- Le CSS se trouve dans le même répertoire que les fichiers PHP (`public/`), donc les chemins relatifs fonctionnent correctement

### 2. **Liens de navigation (Relatifs)**
- ✓ Tous les liens: `/page.php` → `page.php`
- Ajout d'une **barre de navigation commune** sur toutes les pages avec les liens :
  - Accueil (`index.php`)
  - Inscription (`register.php`)
  - Connexion (`login.php`)
  - Profil (`profile.php`)
  - Prendre RDV (`book.php`)
  - Urgences (`urgences.php`)

### 3. **Redirections de formulaires (Relatives)**
- ✓ `header('Location: /login.php')` → `header('Location: login.php')`
- ✓ `header('Location: /profile.php')` → `header('Location: profile.php')`
- ✓ `action="/upload.php"` → `action="upload.php"`

### 4. **Fichiers statiques (JavaScript)**
- ✓ `src="/app.js"` → `src="./app.js"` dans index.php
- ✓ `fetch('/symptom_submit.php')` → `fetch('symptom_submit.php')` dans app.js

### 5. **Gestion des uploads (Chemins relatifs)**
- ✓ Les uploads stockent: `/uploads/` → `../uploads/`
- Les fichiers uploadés sont maintenant accessibles depuis les pages du dossier `public/`

### 6. **Génération de factures (Chemins corrects)**
- ✓ Les code-barres générés sont stockés dans `../uploads/invoices/` au lieu de `/invoices/`

### 7. **Configuration Apache/WAMP**
- ✓ Création d'un fichier `.htaccess` à la racine du projet qui redirige automatiquement les requêtes vers `public/`
- ✓ Création d'un fichier `index.php` à la racine qui redirige vers `public/` (fallback si mod_rewrite est désactivé)

### 8. **Helper PHP (Bonus)**
- ✓ Ajout d'une fonction `base_url()` dans `lib/db.php` pour générer les URLs dynamiquement (utile pour une migration future vers des chemins plus complexes)

## Résultat
- ✓ Tous les chemins CSS fonctionnent
- ✓ Tous les liens de navigation sont opérationnels
- ✓ Les pages sont connectées les unes aux autres
- ✓ Le CSS et le JavaScript se chargent correctement
- ✓ Les fichiers peuvent être uploadés et accessibles

## Comment démarrer

### Option 1 : Serveur PHP intégré (développement)
```powershell
cd C:\wamp64\www\EasyConsult
C:\wamp64\bin\php\php7.4.9\php.exe -S localhost:8080 -t public
```
Accédez à `http://localhost:8080`

### Option 2 : WAMP (Apache)
1. Démarrez WAMP/Apache
2. Accédez à `http://localhost/EasyConsult` (le `.htaccess` redirige automatiquement vers `public/`)

### Option 3 : Configuration VirtualHost
Configurez un VirtualHost Apache pointant vers `C:\wamp64\www\EasyConsult\public\`
