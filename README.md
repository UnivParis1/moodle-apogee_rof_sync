# moodle-apogee_rof_sync

## Pré-requis :

Installation du module PHP oci8 

## Installation :

Dans le fichier config.php renserigner les valeurs des contantes :

```
$CFG->user_oracle = '';// nom de l'utilisateur Oracle
$CFG->passwd_oracle = '';// Mot de passe de l'utilisateur Oracle
$CFG->base_oracle = '';// Nom de la base de donnée APOGEE
```
Placer l'ensemble des fichier du plugin dans le repertoire /local/apogee_rof_sync de Moodle
 
