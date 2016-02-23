# Update Checker

Trouver les versions d'une liste de sites en SPIP.

## Installation

Cloner le dépôt :

```bash
git clone git@github.com:VertigeASBL/update_checker.git
```

Installer Composer (https://getcomposer.org/) se rendre dans le
dossier du dépôt, puis installer les dépendances :

```bash
php composer.phar install
```

## Utilisation

Exporter la liste des site en SPIP dans le google doc de
Vertige. N'importe quel autre fichier csv fera l'affaire, à condition
d'être formatté correctement : une ligne par site, la 6ème colonne
doit être l'url de la racine du site.

On peut alors lancer le script ainsi :

```bash
php update_checker.php source.csv sortie.csv
```

où `source.csv` est le fichier csv contenant les urls des sites à
tester, et `sortie.csv` est le nom du fichier dans lequel seront
écrits les résultats.

Le fichier de résultats est une copie du fichier de départ, auquel on
ajoute trois colonnes : une pour le n° de version de SPIP, une autre
pour la version de l'écran de sécurité installé et enfin le nom du serveur.
