# Déploiement — Clinique Achifaa Oujda

## Hébergement Hostinger + SMTP

### 1. Prérequis

- Compte Hostinger (Premium Web Hosting ou plus)
- Email Hostinger configuré : `info@cliniqueachifaaoujda.com`
- Domaine `cliniqueachifaaoujda.com` pointé sur Hostinger

### 2. Configurer les credentials SMTP

Dans le panel Hostinger : **Email → Manage** → récupérer le mot de passe SMTP de la boîte `info@cliniqueachifaaoujda.com`.

Sur le serveur, créer `/public_html/.env` avec ce contenu (remplir `SMTP_PASSWORD`) :

```env
SMTP_HOST=smtp.hostinger.com
SMTP_PORT=465
SMTP_USER=info@cliniqueachifaaoujda.com
SMTP_PASSWORD=LE_MOT_DE_PASSE_HOSTINGER

MAIL_TO=info@cliniqueachifaaoujda.com
MAIL_FROM_NAME=Site Clinique Achifaa Oujda
SITE_URL=https://cliniqueachifaaoujda.com
MAIL_SUBJECT=Nouvelle demande de RDV — Clinique Achifaa Oujda
```

> ⚠️ **Ne jamais committer `.env` sur Git.** Il est déjà dans `.gitignore`.

### 3. Uploader les fichiers

Méthode A — **Git** (recommandé) :
```bash
ssh u123456@cliniqueachifaaoujda.com
cd public_html
git clone https://github.com/al1-afk/clinique-achifaa-oujda.git .
# Créer .env manuellement (cf section 2)
nano .env
```

Méthode B — **File Manager Hostinger** : zipper le projet et uploader dans `/public_html/`. Créer `.env` via l'éditeur du panel.

### 4. Permissions

```bash
chmod 644 .env submit.php
chmod 755 php
chmod 644 php/smtp.php
chmod 644 .htaccess
```

### 5. Vérifier que le formulaire fonctionne

1. Aller sur `https://cliniqueachifaaoujda.com/#appointment`
2. Remplir + soumettre le formulaire
3. Email arrive dans `info@cliniqueachifaaoujda.com`
4. La page redirige vers `?rdv=ok#appointment` avec écran de confirmation

### 6. Debug en cas de problème

Si le formulaire ne fonctionne pas :

```bash
# Voir les logs PHP Hostinger (souvent dans ~/logs/)
tail -f ~/logs/error_log

# Tester le SMTP en ligne de commande
php -r "require '/home/u123456/public_html/php/smtp.php'; (new SimpleSMTP('smtp.hostinger.com',465,'info@cliniqueachifaaoujda.com','PASS'))->send('info@cliniqueachifaaoujda.com','Test','info@cliniqueachifaaoujda.com','Test','<p>Test</p>');"
```

Erreurs fréquentes :
- **`SMTP credentials manquants`** → `.env` introuvable ou `SMTP_PASSWORD` vide
- **`Connexion SMTP impossible`** → port 465 bloqué par Hostinger (utiliser 587)
- **`AUTH pass : 535 ...`** → mauvais mot de passe
- **Pas de redirection** → `submit.php` non exécuté (vérifier que PHP est activé)

### 7. Tester en local

PHP intégré :
```bash
cd "/Users/said/les app projets/Clinique Achifaa Oujda"
php -S localhost:8000
# Aller sur http://localhost:8000/
```

Le serveur Python (`python3 -m http.server`) ne supporte **pas** PHP — utiliser obligatoirement `php -S` pour tester `submit.php`.

---

## Architecture du backend mail

```
/.env                     ← credentials SMTP (NOT in git)
/.env.example             ← template versionné
/.htaccess                ← bloque accès à .env et php/
/submit.php               ← handler POST du formulaire
/php/smtp.php             ← client SMTP minimal (zéro dépendance)
/index.html               ← <form action="submit.php">
```

Flow :
1. Utilisateur soumet `<form>` → POST `/submit.php`
2. `submit.php` charge `.env`, valide les champs, construit l'email HTML
3. Instance `SimpleSMTP` → connexion SSL `smtp.hostinger.com:465`
4. Authentification AUTH LOGIN (Base64)
5. Envoi du message en UTF-8 Base64-subject
6. Redirection 302 vers `/?rdv=ok#appointment`
