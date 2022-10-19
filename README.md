snowTricks

codacy : https://app.codacy.com/gh/Rte29/snowTricks/dashboard?branch=develop

Configuration requise:
PHP 8.1 - Symfony 6 - Symfony CLI - 

Installation :
Créez un dossier portant le nom que vous désirez

Clonez ou téléchargez la branche main du repository GitHub dans le dossier que vous venez de créer:
https://github.com/Rte29/snowTricks.git

Ouvrez le dossier avec votre IDE

Editez le fichier .env pour mettre à jour DATABASE_URL et MAILER_DSN vous pouvez également passer APP_ENV=prod au lieu de dev.

Ouvrez un terminal

Installer les dépendances avec composer
composer install

Créer une base de données:
php bin/console doctrine:database:create

Importer les entités:
php bin/console doctrine:migrations:migrate

Lancer le serveur local:
symfony serve

Pour se connecter, récuperer un nom d'utilisateur dans la base de données dont l'attribut "activated" est à 1. Le mot de passe est "azerty"

Option: 
Si vous utilisez gmail, lancez la synchronisation: 
php bin/console messenger:consume async
