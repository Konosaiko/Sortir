# Sortir.com

## Etapes d'installation 

Créer un fichier .env.local à la racine du projet et y ajouter les variables d'environnements suivantes :  
```
DATABASE_URL="mysql://root:root@127.0.0.1:3306/sortirdb?serverVersion=5.7.36&charset=utf8mb4"
```
Installer les dépendances du projet : 
```
composer install
```
Créer la base de données : 
```
php bin/console doctrine:database:create
```
Créer les tables de la base de données : 
```
php bin/console doctrine:migrations:migrate
```
Charger les données de tests :
```
php bin/console doctrine:fixtures:load
```
