# ADR-039 : Deploiement local -> preprod par rsync, sans synchronisation de base de donnees

## Statut
Accepte

## Date
2026-09-02

## Contexte

La preprod va etre ouverte a la consultation (le chef d'Audrey doit pouvoir suivre l'avancement). Le PRD ([docs/PRD.md:33](../../docs/PRD.md)) n'avait verrouille aucun hebergeur ni mecanisme de deploiement ("acces serveur cote client en attente") : c'est la premiere decision concrete sur le sujet.

La base de donnees preprod a deja ete copiee une fois manuellement depuis le local. A partir de maintenant, la preprod doit pouvoir evoluer independamment (contenu ajoute directement en preprod par les personnes qui la consultent) sans jamais etre ecrasee par un futur deploiement.

Contraintes retenues avec l'utilisatrice :
- Contenu : plus aucune copie de base locale -> preprod, seulement la configuration (`drush config:import`).
- Declenchement : manuel uniquement, pas de CI/CD automatique.
- Medias (`sites/default/files/`) : non synchronises.
- Acces serveur : SSH deja fourni par l'hebergeur (`user2100@51.210.206.166:22008`, racine du projet `/website`, PHP 8.3 + Composer + Drush 13 deja installes).

## Options considerees

### Option A : `git pull` sur le serveur
- Avantages : historique git cote serveur, rollback trivial (`git checkout <commit>`).
- Inconvenients : suppose un acces sortant du serveur vers GitHub, non confirme ; ajoute une dependance reseau supplementaire au moment du deploiement.

### Option B : rsync depuis le local (retenue)
- Avantages : ne depend que de l'acces SSH deja verifie dans les deux sens ; fonctionne quel que soit l'acces sortant du serveur ; en pilotant la liste des fichiers via `git ls-files`, on obtient gratuitement la meme exclusion que `.gitignore` (settings.php, vendor/, node_modules/, sites/default/files/...) sans avoir a maintenir une liste d'exclusion rsync separee qui risquerait de diverger.
- Inconvenients : pas d'historique de deploiement cote serveur ; un rollback demande de re-deployer un commit anterieur depuis le local plutot qu'un simple `git checkout` distant.

## Decision

Rsync depuis le local, avec `git ls-files -z` comme source de verite pour la liste des fichiers transferes (`--files-from=- --from0`). Composer tourne cote serveur (`composer install --no-dev`) plutot que de rsyncer `vendor/`. Cote base de donnees : plus jamais de copie de la base locale, uniquement `drush deploy` (mises a jour de schema + `config:import` + rebuild de cache), precede d'un `drush sql:dump` de securite sur le serveur (dump horodate, pas de purge automatique).

L'authentification SSH se fait exclusivement par cle (pas de mot de passe stocke nulle part, coherent avec l'interdiction de committer des secrets). Les parametres de connexion (host/user/port/chemin) vivent dans `.env.deploy`, gitignore, avec `.env.deploy.example` comme gabarit committe : ce ne sont pas des secrets exploitables seuls (inutiles sans la cle privee), mais on evite quand meme de publier la topologie d'infra dans l'historique git.

## Consequences

- `scripts/deploy-preprod.sh` est le seul point d'entree du deploiement. Il refuse de partir hors de la branche `main`, avec un working tree sale, ou si `npm run lint`/`format:check` echouent (sauf `--skip-checks`).
- Toute nouvelle regle d'exclusion de fichiers (`.gitignore`) beneficie automatiquement au deploiement, sans modification du script.
- Un rollback de code se fait en redeployant un commit anterieur depuis le local (`git checkout <commit> && scripts/deploy-preprod.sh`), pas via un historique git distant.
- Le contenu de la preprod n'est plus jamais synchronise depuis le local : toute divergence de contenu entre local et preprod est normale et attendue, pas un bug.
- Un dump de securite de la base preprod s'accumule dans `backups/` sur le serveur a chaque deploiement (sauf `--no-backup`) ; aucune purge automatique n'est mise en place, a nettoyer manuellement si l'espace disque devient un sujet.
