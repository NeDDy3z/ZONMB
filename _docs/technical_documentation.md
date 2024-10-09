# Technical Documentation
- here are (or will be) explained some of the functions of the project

### Technologies
- PHP 8.3 

## File structure of the project
```
/"root"
│
├── /_database                              # database related files
│   └── database_scheme.sql                 # SQL file to create database
│
├── /_docs                                  # documentation (install, technical, usermanual, requirements, PHPDoc,...)
│
├── /src                   
│   ├── /app                                # app logic
│   │   ├── /controllers                    # controllers for each page
│   │   │   ├── HomepageController.php
│   │   │   ├── NewsController.php
│   │   │   ├── ArticleController.php
│   │   │   ├── UserProfileController.php
│   │   │   └── AuthController.php          
│   │   │
│   │   ├── /models                         # logic for communication with database (User, Article, Comment...)
│   │   │   ├── User.php
│   │   │   ├── Article.php
│   │   │   └── Comment.php
│   │   │
│   │   └── /views                          # templates for individual pages (HTML)
│   │       ├── homepage.php
│   │       ├── news.php
│   │       ├── article.php
│   │       ├── userprofile.php
│   │       ├── login.php
│   │       └── register.php
│   │
│   └── /public                             # "publicly" accessible part of website
│       ├── /assets                         # static files
│       │   ├── /css                        # CSS - styling
│       │   ├── /js                         # JS - client side logic
│       │   └── /images                     # images (not uploaded by users)
│       │
│       ├── /uploads                        # files uploaded by users
│       └── index.php                       # entering point of the webpage (route controller)
│       
└── /config                                 # application config (e.g. database connection, env,...)
    └── config.php
```
