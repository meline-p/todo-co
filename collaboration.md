# Guide de Collaboration et Qualité pour le Projet To Do & Co

## Introduction
Ce document a pour objectif de définir les bonnes pratiques pour contribuer au projet, en détaillant les processus de collaboration, les outils à utiliser, ainsi que les normes de qualité à respecter.

## Sommaire
- [Processus de Qualité](#processus-de-qualité)
- [Collaboration avec Git](#collaboration-avec-git)
- [Intégration avec SymfonyInsight](#intégration-avec-symfonyinsight)
- [Respect des Normes PSR](#respect-des-normes-psr)
- [Tests Unitaires et Tests d'intégration](#tests-unitaires-et-tests-dintégration)


## Processus de Qualité

1. **Tests Unitaires**

    Avant toute Pull Request, assurez-vous que des [tests unitaires](#tests-unitaires-et-tests-dintégration) sont ajoutés pour les nouvelles fonctionnalités ou corrections.

2. **Tests d'Intégration**

    Les [tests d'intégration](#tests-unitaires-et-tests-dintégration) doivent être exécutés pour vérifier le bon fonctionnement du système dans son ensemble.

3. **Revue de Code**

    Une revue de code est essentielle avant d'accepter une Pull Request. Les autres développeurs doivent examiner le code pour vérifier sa lisibilité, sa logique et son respect des bonnes pratiques.

4. **Documentation**

    Toute fonctionnalité ajoutée ou modifiée doit être correctement documentée dans le code, avec des commentaires pertinents expliquant les choix techniques.

## Collaboration avec Git

1. **Cloner le Dépôt** 

   Assurez-vous d'avoir cloné le dépôt Git à partir de la branche principale (`master`) :
   ```bash
   git clone https://github.com/meline-p/todo-co.git
   ```

2. **Création de Branche**

    Pour toute nouvelle fonctionnalité ou correction de bug, créez une branche à partir de la branche principale :

    ```bash
    git checkout -b nom_de_la_fonctionnalité
    ```

3. **Travail sur votre Branche**

    Vous devez toujours travailler sur votre propre branche et ne jamais pousser directement sur la branche `master`. Assurez-vous que vos changements sont isolés dans la branche spécifique à la fonctionnalité ou au bug que vous traitez. Assurez-vous de tester votre code avec des [tests unitaires et des tests d'intégration](#tests-unitaires-et-tests-dintégration)

4. **Ajouter les Fichiers Modifiés**

    Avant de faire un commit, vous devez d'abord ajouter les fichiers modifiés à l'index de Git. Utilisez la commande suivante pour ajouter tous les fichiers modifiés :

    ```bash
    git add .
    ```

    Cela permet de marquer tous les fichiers modifiés pour qu'ils soient inclus dans le commit. Si vous souhaitez ajouter des fichiers spécifiques, vous pouvez remplacer `.` par le chemin du fichier ou du dossier.

5. **Commits et Messages de Commit**

    Faites des commits réguliers avec des messages clairs et concis :

    ```bash
    git commit -m "Description de la fonctionnalité ou du bug corrigé"
    ```

6. **Push sur votre Branche**

    Une fois que vous avez effectué des modifications, vous pouvez pousser vos commits sur votre branche en utilisant la commande suivante :

    ```bash
    git push origin nom_de_la_fonctionnalité
    ```

7. **Pull Request**

    Avant de fusionner votre branche dans la branche principale (`master`), ouvrez une Pull Request (PR) sur GitHub. Avant de soumettre une PR, assurez-vous que le projet passe les tests de qualité sur [SymfonyInsight](#intégration-avec-symfonyinsight).

## Intégration avec SymfonyInsight

1. **Analyse de la Qualité du Code**

    SymfonyInsight est utilisé pour analyser la [qualité du code](#respect-des-normes-psr).

2. **Fixer les Problèmes de Qualité**

    Si des problèmes sont signalés par SymfonyInsight, corrigez-les avant de soumettre vos modifications. Cela peut inclure des problèmes de sécurité, des erreurs de syntaxe ou des violations des bonnes pratiques.

## Respect des Normes PSR

### PHP CS Fixer

PHP CS Fixer est un outil de formatage et de correction du code PHP.
**Installer PHP CS Fixer**

```bash
composer require --dev friendsofphp/php-cs-fixer
```

Créer un fichier de configuration .php-cs-fixer.php et le personaliser.

### Normes PSR

1. **PSR-1 (Coding Standard)**

    Toutes les modifications doivent suivre la norme PSR-1 qui définit les standards fondamentaux pour l'écriture du code en PHP.
    
    Exemple :

    ```bash
    <?php

    namespace MonProjet;

    class Exemple
    {
        public function maFonction()
        {
            echo 'Hello World';
        }
    }
    ```

    - Noms de fichiers : Le nom du fichier doit correspondre à celui de la classe (`Exemple.php` pour la classe Exemple).
    - Déclarations PHP : Le fichier PHP doit commencer par `<?php` et ne doit pas contenir de code en dehors des balises PHP.

2. **PSR-2 (Coding Style Guide)**

    Le style de codage doit respecter la PSR-2. Cela inclut les indentations, les espaces, et la longueur des lignes.

    Exemple :

    ```bash
    <?php

    namespace MonProjet;

    class Exemple
    {
        public function maFonction($param)
        {
            if ($param) {
                echo 'True';
            } else {
                echo 'False';
            }
        }
    }
    ```

    - Indentation : Utilisez des espaces (pas de tabulations) pour indenter le code, avec 4 espaces par niveau.
    - Brace Style : Placez les accolades sur la même ligne que la déclaration (if, for, etc.).

3. **PSR-4 (Autoloading)**

    Assurez-vous que le code suit la norme PSR-4 pour l'autoloading. Les noms de classes doivent correspondre au chemin du fichier.

    Exemple :

    ```bash
    <?php

    // Structure des dossiers : src/MonProjet/Exemple.php

    namespace MonProjet;

    class Exemple
    {
        public function __construct()
        {
            echo 'Classe Exemple chargée automatiquement';
        }
    }
    ```

    - Autoloading : Les classes doivent être placées dans des dossiers correspondant à leur espace de noms (namespace). Par exemple, la classe Exemple dans le namespace MonProjet doit se trouver dans src/MonProjet/Exemple.php.

4. **PSR-12 (Extended Coding Style)**

    La norme PSR-12 étend la PSR-2 et offre des recommandations supplémentaires sur la structuration des fichiers, les déclarations, et les espacements.

    Exemple :

    ```bash
    <?php

    namespace MonProjet;

    class Exemple
    {
        public function afficherMessage(string $message): void
        {
            echo $message;
        }
    }
    ```

    - Type-hinting : Utilisez des déclarations de types pour les arguments et les valeurs de retour des fonctions (exemple : `string` et `: void`).


## Tests Unitaires et Tests d'intégration

### Tests Unitaires: 

- Idéaux pour tester la logique métier de manière exhaustive. (se concentrent uniquement sur des méthodes isolées, comme la validation d'une tâche ou une méthode logique dans un service)
- Doivent couvrir la majorité des scénarios possibles au niveau des fonctions ou méthodes.

### Tests d'Intégration : 

- Complètent les tests unitaires en vérifiant que l'application fonctionne bien dans un environnement proche de la réalité.
- Essentiels pour tester des workflows complets et des fonctionnalités critiques. (Le système d'authentification, les contrôleurs Symfony, les réponses HTTP, les données de test (fixtures), les formulaires et les réponses HTML)

1. **Installer PHPUnit (si nécessaire)**

    Si PHPUnit n'est pas encore installé, vous pouvez l'installer via Composer :

    ```bash
    composer require --dev phpunit/phpunit
    ```

2. **Exécuter les Tests**

    Pour exécuter les tests dans ton projet Symfony, vous pouvez utiliser la commande suivante :

    ```bash
    php vendor/bin/phpunit
    ```

    Cela exécutera tous les tests présents dans le projet. Si vous voulez exécuter un fichier de test spécifique, vous pouvez préciser le chemin vers ce fichier :

    ```bash
    php vendor/bin/phpunit tests/Unit/ExempleTest.php
    ```

3. **Exécuter les Tests avec un Rapport**

    Si vous voulez générer un rapport sur l'exécution des tests, vous pouvez utiliser l'option `--coverage-html` pour obtenir un rapport de couverture sous forme de fichier HTML.

    ```bash
    php vendor/bin/phpunit --coverage-html public/test-coverage
    ```

    Cela générera un rapport de couverture des tests dans le dossier public/coverage. Toute PR présentant une couverture de code inférieure à 70 % sera rejetée.
