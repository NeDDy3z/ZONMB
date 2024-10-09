# ZONMB
- Webová stránka pro Základní organizace neslyšících Mladá Boleslav p.s

## File structure of the project
```
/projekt
│
├── /public                # veřejná část projektu, dostupná přes web
│   ├── /assets            # statické soubory (obrázky, CSS, JavaScript)
│   │   ├── /css           # všechny CSS soubory
│   │   ├── /js            # všechny JavaScript soubory
│   │   └── /images        # všechny obrázky
│   │
│   ├── /uploads           # nahrané soubory od uživatelů (např. profilové obrázky)
│   ├── index.php          # vstupní bod webové stránky (route controller)
│   └── .htaccess          # nastavení serveru (např. přesměrování na index.php)
│
├── /src                   # aplikační logika
│   ├── /controllers       # kontrolery pro jednotlivé stránky
│   │   ├── HomepageController.php
│   │   ├── NewsController.php
│   │   ├── ArticleController.php
│   │   ├── UserProfileController.php
│   │   └── AuthController.php    # správa přihlášení/registrace
│   │
│   ├── /models            # třídy, které pracují s databází (User, Article, Comment...)
│   │   ├── User.php
│   │   ├── Article.php
│   │   └── Comment.php
│   │
│   └── /views             # šablony jednotlivých stránek (HTML)
│       ├── homepage.php
│       ├── news.php
│       ├── article.php
│       ├── userprofile.php
│       ├── login.php
│       └── register.php
│
├── /config                # konfigurace aplikace (např. připojení k databázi)
│   └── config.php
│
├── /logs                  # logy aplikace (chybové záznamy)
│
├── /database              # soubory databáze, migrace
│   └── migrations.sql     # SQL soubor s vytvářením databázových tabulek
│
└── /docs                  # dokumentace (zadání, manuály, PHPDoc)
```
