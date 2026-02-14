# Instalare - Smash Cup 5x5

## Cerințe

- PHP 8.0 sau mai nou
- MySQL 5.7 sau mai nou
- Apache/Nginx cu mod_rewrite activat
- Composer (pentru dependențe)

## Pași de instalare

### 1. Clonează sau descarcă proiectul

```bash
git clone <repository-url>
cd PHP-PADEL-CLASH-CUP
```

### 2. Instalează dependențele

```bash
composer install
```

Dacă nu ai Composer instalat, descarcă-l de la https://getcomposer.org/

### 3. Configurează baza de date

1. Creează o bază de date MySQL:
```sql
CREATE DATABASE padel_tournament CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Importă schema:
```bash
mysql -u root -p padel_tournament < sql/schema.sql
```

Sau folosește phpMyAdmin pentru a importa fișierul `sql/schema.sql`.

### 4. Configurează aplicația

Editează fișierul `config.php` și actualizează:
- Credențialele bazei de date (DB_HOST, DB_NAME, DB_USER, DB_PASS)
- URL-ul aplicației (APP_URL)
- Setările email (SMTP_USER, SMTP_PASS) - opțional pentru funcționalitate completă email

### 5. Configurează serverul web

#### Apache
Asigură-te că mod_rewrite este activat și că `.htaccess` este permis.

#### Nginx
Adaugă următoarea configurare în server block:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 6. Setează permisiuni (Linux/Mac)

```bash
chmod -R 755 .
chmod -R 777 uploads/  # Dacă există directorul uploads
```

### 7. Accesează aplicația

Deschide browserul și accesează:
- http://localhost/PHP-PADEL-CLASH-CUP/

## Utilizator default

- **Username:** admin
- **Parolă:** admin123

**IMPORTANT:** Schimbă parola imediat după prima autentificare!

## Funcționalități

### Email
Pentru funcționalitatea completă de email, configurează SMTP în `config.php`. 
Alternativ, aplicația va folosi funcția PHP `mail()` care necesită configurare SMTP pe server.

### Import/Export
- **Export:** Disponibil din meniul Export (Excel, PDF, DOC)
- **Import:** Disponibil pe pagina Import pentru echipe

### Informații externe
Aplicația integrează date externe (vreme, știri sportive). 
Pentru funcționalitate completă, actualizează API keys în `includes/external_data.php`.

## Depanare

### Eroare de conexiune la baza de date
- Verifică credențialele în `config.php`
- Asigură-te că MySQL rulează
- Verifică că baza de date există

### Eroare la export Excel/PDF
- Asigură-te că ai rulat `composer install`
- Verifică că extensiile PHP necesare sunt instalate (gd, zip, xml)

### Email nu funcționează
- Verifică configurația SMTP în `config.php`
- Pentru Gmail, folosește "App Password" în loc de parola normală
- Verifică că serverul permite trimiterea de email

## Suport

Pentru probleme sau întrebări, contactează administratorul.

