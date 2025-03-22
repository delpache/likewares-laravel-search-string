# 🔍 Laravel Search String

Génère des requêtes de base de données basées sur une chaîne unique en utilisant une syntaxe simple et personnalisable.

![Exemple de syntaxe d'une chaîne de recherche et son résultat](https://user-images.githubusercontent.com/3642397/40266921-6f7b4c70-5b54-11e8-8e40-000ae3b4e201.png)


## Introduction

Laravel Search String fournit une solution simple pour définir la portée de vos requêtes de base de données en utilisant une syntaxe lisible par l'homme et personnalisable. Il transforme une simple chaîne de caractères en un puissant générateur de requêtes.

Par exemple, la chaîne de recherche suivante permet de récupérer les derniers articles de blog non publiés ou intitulés « Mon article de blog ».

```php
Article::usingSearchString('title:"My blog article" or not published sort:-created_at');

// Equivalent à:
Article::where('title', 'My blog article')
       ->orWhere('published', false)
       ->orderBy('created_at', 'desc');
```

L'exemple suivant recherche le terme « John » dans les colonnes `customer` et `description` tout en s'assurant que les factures sont soit payées, soit archivées.

```php
Invoice::usingSearchString('John and status in (Paid,Archived) limit:10 from:10');

// Equivalent à:
Invoice::where(function ($query) {
           $query->where('customer', 'like', '%John%')
               ->orWhere('description', 'like', '%John%');
       })
       ->whereIn('status', ['Paid', 'Archived'])
       ->limit(10)
       ->offset(10);
```

Vous pouvez également rechercher l'existence d'enregistrements connexes, par exemple des articles publiés en 2020 et comportant plus de 100 commentaires qui ne sont pas des spams ou qui ont été rédigés par John.

```php
Article::usingSearchString('published = 2020 and comments: (not spam or author.name = John) > 100');

// Equivalent à:
Article::where('published_at', '>=', '2020-01-01 00:00:00')
        ->where('published_at', '<=', '2020-12-31 23:59:59')
        ->whereHas('comments', function ($query) {
            $query->where('spam', false)
                ->orWhereHas('author' function ($query) {
                    $query->where('name', 'John');
                });
        }, '>', 100);
```

Comme vous pouvez le constater, il s'agit non seulement d'un moyen pratique de communiquer avec votre API Laravel (au lieu d'autoriser des dizaines de champs de requête), mais il peut également être présenté à vos utilisateurs comme un outil permettant d'explorer leurs données.

## Installation

```bash
# Installation via composer
composer require likewares/laravel-search-string

# (Facultatif) Publier le fichier de configuration search-string.php
php artisan vendor:publish --tag=search-string
```

## Utilisation de base

Ajoutez le trait `SearchString` à vos modèles et configurez les colonnes qui doivent être utilisées dans votre chaîne de recherche.

```php
use Likewares\LaravelSearchString\Concerns\SearchString;

class Article extends Model
{
    use SearchString;

    protected $searchStringColumns = [
        'title', 'body', 'status', 'rating', 'published', 'created_at',
    ];
}
```

Notez que vous pouvez les définir dans [d'autres parties de votre code] (#autres endroits à configurer) et [personnaliser le comportement de chaque colonne] (#configuration des colonnes).

Voilà, c'est fait ! Vous pouvez maintenant créer une requête de base de données en utilisant la syntaxe des chaînes de recherche.

```php
Article::usingSearchString('title:"Hello world" sort:-created_at,published')->get();
```

## La syntaxe de la chaîne de recherche

Notez que les espaces entre les opérateurs n'ont pas d'importance.

### Correspondances exactes

```php
'rating: 0'
'rating = 0'
'title: Hello'               // Les chaînes sans espaces n'ont pas besoin de guillemets
'title: "Hello World"'       // Les chaînes avec espaces ont besoin de guillemets
"title: 'Hello World'"       // Les guillemets simples peuvent également être utilisés
'rating = 99.99'
'created_at: "2018-07-06 00:00:00"'
```

### Comparisons

```php
'title < B'
'rating > 3'
'created_at >= "2018-07-06 00:00:00"'
```

### Lists

```php
'title in (Hello, Hi, "My super article")'
'status in(Finished,Archived)'
'status:Finished,Archived'
```

### Dates

La colonne doit être transformée en date ou explicitement marquée comme telle dans les [options de la colonne](#date).

```php
// Year precision
'created_at >= 2020'                    // 2020-01-01 00:00:00 <= created_at
'created_at > 2020'                     // 2020-12-31 23:59:59 < created_at
'created_at = 2020'                     // 2020-01-01 00:00:00 <= created_at <= 2020-12-31 23:59:59
'not created_at = 2020'                 // created_at < 2020-01-01 00:00:00 et created_at > 2020-12-31 23:59:59

// Month precision
'created_at = 01/2020'                  // 2020-01-01 00:00:00 <= created_at <= 2020-01-31 23:59:59
'created_at <= "Jan 2020"'              // created_at <= 2020-01-31 23:59:59
'created_at < 2020-1'                   // created_at < 2020-01-01 00:00:00

// Précision du jour
'created_at = 2020-12-31'               // 2020-12-31 00:00:00 <= created_at <= 2020-12-31 23:59:59
'created_at >= 12/31/2020"'             // 2020-12-31 23:59:59 <= created_at
'created_at > "Dec 31 2020"'            // 2020-12-31 23:59:59 < created_at

// Précisions sur les heures et les minutes
'created_at = "2020-12-31 16"'          // 2020-12-31 16:00:00 <= created_at <= 2020-12-31 16:59:59
'created_at = "2020-12-31 16:30"'       // 2020-12-31 16:30:00 <= created_at <= 2020-12-31 16:30:59
'created_at = "Dec 31 2020 5pm"'        // 2020-12-31 17:00:00 <= created_at <= 2020-12-31 17:59:59
'created_at = "Dec 31 2020 5:15pm"'     // 2020-12-31 17:15:00 <= created_at <= 2020-12-31 17:15:59

// Précision exacte
'created_at = "2020-12-31 16:30:00"'    // created_at = 2020-12-31 16:30:00
'created_at = "Dec 31 2020 5:15:10pm"'  // created_at = 2020-12-31 17:15:10

// Dates relatives
'created_at = today'                    // aujourd'hui entre 00:00 et 23:59
'not created_at = today'                // avant 00:00 et après 23:59
'created_at >= tomorrow'                // à partir de demain à 00:00
'created_at <= tomorrow'                // jusqu'à demain à 23:59
'created_at > tomorrow'                 // à partir d'après-demain à 00:00
'created_at < tomorrow'                 // jusqu'à aujourd'hui à 23:59
```

### Booleans

La colonne doit être soit castée en tant que booléen, soit explicitement marquée comme booléenne dans les [options de la colonne](#boolean).

Alternativement, si la colonne est marquée comme une date, elle sera automatiquement marquée comme un booléen en utilisant `is null` et `is not null`.

```php
'published'         // published = true
'created_at'        // created_at n'est pas null
```

### Negations

```php
'not title:Hello'
'not title="My super article"'
'not rating:0'
'not rating>4'
'not status in (Finished,Archived)'
'not published'     // published = false
'not created_at'    // created_at est null
```

### Valeurs nulles

Le terme `NULL` est sensible à la casse.

```php
'body:NULL'         // le body est nul
'not body:NULL'     // le body n'est pas nul
```

### Recherchable

Au moins une colonne doit être [définie comme recherchable] (#searchable-1).

Le terme interrogé ne doit pas correspondre à une colonne booléenne, sinon il sera traité comme une requête booléenne.

```php
'Apple'             // %Apple% like at least one of the searchable columns
'"John Doe"'        // %John Doe% like at least one of the searchable columns
'not "John Doe"'    // %John Doe% not like any of the searchable columns
```

### And/Or

```php
'title:Hello body:World'        // Implicite et
'title:Hello and body:World'    // Explicite et
'title:Hello or body:World'     // Explicite ou
'A B or C D'                    // Équivalent à « (A et B) ou (C et D) »
'A or B and C or D'             // Équivalent à « A ou (B et C) ou D »
'(A or B) and (C or D)'         // Priorité explicite imbriquée
'not (A and B)'                 // Équivalent à « pas A ou pas B »
'not (A or B)'                  // Équivalent à « pas A et pas B ».
```

### Relations

La colonne doit être explicitement [définie comme une relation] (#relationship) et le modèle associé à cette relation doit également utiliser le trait `SearchString`.

Lors d'une requête imbriquée dans une relation, Laravel Search String utilisera la définition de la colonne du modèle lié.

Dans les exemples suivants, `comments` est une relation `HasMany` et `author` est une relation `BelongsTo` imbriquée dans le modèle `Comment`.

```php
// Simple vérification "has"
'comments'                              // A des commentaires
'not comments'                          // N'a pas de commentaires
'comments = 3'                          // A 3 commentaires
'not comments = 3'                      // N'a pas 3 commentaires
'comments > 10'                         // A plus de 10 commentaires
'not comments <= 10'                    // Idem
'comments <= 5'                         // A 5 commentaires ou moins
'not comments > 5'                      // Idem

// "WhereHas"
'comments: (title: Superbe)'            // A des commentaires avec le titre "Superbe"
'comments: (not title: Superbe)'        // A des commentaires dont le titre est différent de "Superbe"
'not comments: (title: Superbe)'        // N'a pas de commentaires avec le titre "Superbe"
'comments: (quality)'                   // A des commentaires dont les colonnes consultables correspondent à "%quality%"
'not comments: (spam)'                  // N'a pas de commentaires marqués comme spam
'comments: (spam) >= 3'                 // A au moins 3 commentaires spam
'not comments: (spam) >= 3'             // A au plus 2 commentaires spam
'comments: (not spam) >= 3'             // A au moins 3 commentaires qui ne sont pas du spam
'comments: (likes < 5)'                 // A des commentaires avec moins de 5 likes
'comments: (likes < 5) <= 10'           // A au plus 10 commentaires avec moins de 5 likes
'not comments: (likes < 5)'             // N'a pas de commentaires avec moins de 5 likes
'comments: (likes > 10 and not spam)'   // A des commentaires non-spam avec plus de 10 likes

// Raccourcis "WhereHas"
'comments.title: Superbe'               // Comme 'comments: (title: Superbe)'
'not comments.title: Superbe'           // Comme 'not comments: (title: Superbe)'
'comments.spam'                         // Comme 'comments: (spam)'
'not comments.spam'                     // Comme 'not comments: (spam)'
'comments.likes < 5'                    // Comme 'comments: (likes < 5)'
'not comments.likes < 5'                // Comme 'not comments: (likes < 5)'

// Relations imbriquées
'comments: (author: (name: John))'      // A des commentaires de l'auteur nommé John
'comments.author: (name: John)'         // Identique au précédent
'comments.author.name: John'            // Identique au précédent

// Les relations imbriquées sont optimisées
'comments.author.name: John and comments.author.age > 21'   // Comme: 'comments: (author: (name: John and age > 21))
'comments.likes > 10 or comments.author.age > 21'           // Comme: 'comments: (likes > 10 or author: (age > 21))
```

Notez que toutes ces expressions sont déléguées à la méthode de requête `has`. Par conséquent, cette méthode fonctionne immédiatement avec les types de relations suivants: `HasOne`, `HasMany`, `HasOneThrough`, `HasManyThrough`, `BelongsTo`, `BelongsToMany`, `MorphOne`, `MorphMany` et `MorphToMany`.

Le seul type de relation non supporté actuellement est `MorphTo` car Laravel Search String a besoin d'un modèle apparenté explicite pour l'utiliser dans les requêtes imbriquées.

### Mots-clés spéciaux

Notez que ces mots-clés [peuvent être personnalisés] (#configuring-special-keywords).

```php
'fields:title,body,created_at'  // Sélectionner uniquement title, body, created_at
'not fields:rating'             // Sélectionner toutes les colonnes sauf le classement
'sort:rating,-created_at'       // Ordre de classement asc, created_at desc
'limit:1'                       // Limit 1
'from:10'                       // Offset 10
```

## Configuration de colonnes

### Alias de colonne

Si vous souhaitez qu'une colonne soit interrogée sous un nom différent, vous pouvez la définir comme une paire clé/valeur où la clé est le nom de la colonne dans la base de données et la valeur est l'alias que vous souhaitez utiliser.

```php
protected $searchStringColumns = [
    'title',
    'body' => 'content',
    'published_at' => 'published',
    'created_at' => 'created',
];
```

Vous pouvez également fournir une expression rationnelle pour une définition plus souple de l'alias.

```php
protected $searchStringColumns = [
    'published_at' => '/^(published|live)$/',
    // ...
];
```

### Options des colonnes

You can configure a column even further by assigning it an array of options.

```php
protected $searchStringColumns = [
    'created_at' => [
        'key' => 'created',         // Valeur par défaut du nom de la colonne : /^created_at$/
        'date' => true,             // La valeur par défaut est true uniquement si la colonne est convertie en date.
        'boolean' => true,          // Vrai par défaut uniquement si la colonne est convertie en booléen ou en date.
        'searchable' => false       // La valeur par défaut est false.
        'relationship' => false     // Valeur par défaut : false.
        'map' => ['x' => 'y']       // Mappage des données provenant de l'entrée de l'utilisateur vers les valeurs de la base de données. Valeur par défaut : [].
    ],
    // ...
];
```

#### Key
L'option `key` est ce que nous avons configuré jusqu'à présent, c'est à dire l'alias de la colonne. Il peut s'agir d'un motif regex (permettant ainsi des correspondances multiples) ou d'une chaîne de caractères régulière pour une correspondance exacte.

#### Date
Si une colonne est marquée comme `date`, la valeur de la requête sera analysée en utilisant `Carbon` tout en conservant le niveau de précision donné par l'utilisateur. Par exemple, si la colonne `created_at` est marquée comme une `date` :

```php
'created_at >= demain' // Equivalent à:
$query->where('created_at', '>=', 'YYYY-MM-DD 00:00:00');
// where `YYYY-MM-DD` correspond à la date de demain.

'created_at = "July 6, 2018"' // Equivalent à:
$query->where('created_at', '>=', '2018-07-06 00:00:00');
      ->where('created_at', '<=', '2018-07-06 23:59:59');
```

Par défaut, toute colonne qui est considérée comme une date (en utilisant les propriétés de Laravel), sera marquée comme une date pour SearchString. Vous pouvez forcer une colonne à ne pas être marquée comme une date en assignant `date` à `false`.

#### Boolean
Si une colonne est marquée comme `boolean`, elle peut être utilisée sans opérateur ni valeur. Par exemple, si la colonne `paid` est marquée comme `boolean` :

```php
'paid' // Equivalent à:
$query->where('paid', true);

'not paid' // Equivalent à:
$query->where('paid', false);
```

Si une colonne est marquée à la fois comme `boolean` et `date`, elle sera comparée à `null` lorsqu'elle est utilisée comme booléen. Par exemple, si la colonne `published_at` est marquée comme `boolean` et `date` et utilise l'alias `published` :

```php
'published' // Equivalent à:
$query->whereNotNull('published');

'not published_at' // Equivalent à:
$query->whereNull('published');
```

By default any column that is cast as a boolean or as a date (using Laravel properties), will be marked as a boolean. You can force a column to not be marked as a boolean by assigning `boolean` to `false`.

#### Recherchable
Si une colonne est marquée comme `searchable`, elle sera utilisée pour répondre à des requêtes de recherche, c'est-à-dire des termes qui sont seuls mais qui ne sont pas des booléens comme `Banane pomme` ou ``Jean Dupont``.

Par exemple, si les deux colonnes `title` et `description` sont marquées comme `searchable` :

```php
'Apple Banana' // Equivalent to:
$query->where(function($query) {
          $query->where('title', 'like', '%Apple%')
                ->orWhere('description', 'like', '%Apple%');
      })
      ->where(function($query) {
          $query->where('title', 'like', '%Banana%')
                ->orWhere('description', 'like', '%Banana%');
      });

'"John Doe"' // Equivalent à:
$query->where(function($query) {
          $query->where('title', 'like', '%John Doe%')
                ->orWhere('description', 'like', '%John Doe%');
      });
```

If no searchable columns are provided, such terms or strings will be ignored.

#### Relation

Si une colonne est marquée comme `relation`, elle sera utilisée pour interroger les relations.

Le nom de la colonne doit correspondre à une méthode de relation valide sur le modèle mais, comme d'habitude, des alias peuvent être créés en utilisant l'option [`key`](#key).

Le modèle associé à cette méthode de relation doit également utiliser le trait `SearchString` afin d'imbriquer les requêtes de relation.

Par exemple, supposons que vous ayez un modèle d'article et que vous souhaitiez interroger les commentaires associés. Il doit y avoir une méthode de relation `comments` valide et le modèle `Comment` doit lui-même utiliser le trait `SearchString`.

```php
use Likewares\LaravelSearchString\Concerns\SearchString;

class Article extends Model
{
    use SearchString;

    protected $searchStringColumns = [
        'comments' => [
            'key' => '/^comments?$/',   // alias de la colonne `comments` ou `comment`.
            'relationship' => true,     // Il doit y avoir une méthode `comments` qui définit une relation.
        ],
    ];

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}

class Comment extends Model
{
    use SearchString;

    protected $searchStringColumns = [
        // ...
    ];
}
```

Notez que, puisque Search String délègue simplement la méthode `$builder->has(...)`, vous pouvez fournir n'importe quelle méthode de relation fantaisiste et les contraintes seront conservées. Par exemple :

```php
protected $searchStringColumns = [
    'myComments' => [
        'key' => 'my_comments',
        'relationship' => true,
    ],
];

public function myComments()
{
    return $this->hasMany(Comment::class)->where('author_id', Auth::user()->id);
}
```

## Configuration des mots-clés spéciaux

Vous pouvez personnaliser le nom d'un mot-clé en définissant une paire clé/valeur dans la propriété `$searchStringKeywords`.

```php
protected $searchStringKeywords = [
    'select' => 'fields',   // Met à jour les colonnes sélectionnées de la requête
    'order_by' => 'sort',   // Met à jour l'ordre des résultats de la requête
    'limit' => 'limit',     // Limite le nombre de résultats
    'offset' => 'from',     // Commence les résultats à un autre index
];
```

De la même manière que pour les valeurs de colonnes, vous pouvez fournir un tableau pour définir un `key` personnalisé du mot-clé. Notez que les options `date`, `boolean`, `searchable` et `relationship` ne sont pas applicables aux mots-clés.

```php
protected $searchStringKeywords = [
    'select' => [
        'key' => 'fields',
    ],
    // ...
];
```

## Autres configuration

Comme nous l'avons vu jusqu'à présent, vous pouvez configurer vos colonnes et vos mots-clés spéciaux en utilisant les propriétés `searchStringColumns` et `searchStringKeywords` de votre modèle.

Vous pouvez également surcharger la méthode `getSearchStringOptions` de votre modèle qui est par défaut :

```php
public function getSearchStringOptions()
{
    return [
        'columns' => $this->searchStringColumns ?? [],
        'keywords' => $this->searchStringKeywords ?? [],
    ];
}
```

Si vous préférez ne pas définir ces configurations sur le modèle lui-même, vous pouvez les définir directement dans le fichier `config/search-string.php` comme ceci :

```php
// config/search-string.php
return [
    'default' => [
        'keywords' => [ /* ... */ ],
    ],

    Article::class => [
        'columns'  => [ /* ... */ ],
        'keywords' => [ /* ... */ ],
    ],
];
```

Lors de la résolution des options pour un modèle particulier, SearchString fusionnera ces configurations dans l'ordre suivant :
1. D'abord en utilisant les configurations définies sur le modèle
2. Puis en utilisant le fichier de configuration à la clé correspondant à la classe du modèle
3. Puis en utilisant le fichier de configuration à la clé `default`
4. Enfin, en utilisant quelques configurations de repli

## Configuration des recherches insensibles à la casse

Lorsque vous utilisez des bases de données comme PostgreSql, vous pouvez ignorer le comportement par défaut des recherches sensibles à la casse en définissant case_insensitive à true dans vos options parmi les colonnes et les mots-clés. Par exemple, dans le fichier config/search-string.php

```php
return [
    'default' => [
        'case_insensitive' => true, // <- Globalement.
        // ...
    ],

    Article::class => [
        'case_insensitive' => true, // <- Uniquement pour la classe Article.
        // ...
    ],
];
```

Lorsque cette valeur est fixée à true, la colonne et la valeur sont mises en minuscules avant d'être comparées à l'aide de l'opérateur like.

```
$value = mb_strtolower($value, 'UTF8');
$query->whereRaw("LOWER($column) LIKE ?", ["%$value%"]);
```


## Error handling

La chaîne de recherche fournie peut être invalide pour de nombreuses raisons.
- Elle ne respecte pas la syntaxe de la chaîne de recherche
- Elle tente d'interroger une colonne inexistante ou un alias de colonne
- Elle fournit des valeurs invalides à des mots-clés spéciaux tels que `limit`.
- Etc.

L'une ou l'autre de ces erreurs provoquera une `InvalidSearchStringException`.

Cependant, vous pouvez choisir si vous voulez que ces exceptions remontent jusqu'au gestionnaire d'exception de Laravel ou si vous voulez qu'elles échouent silencieusement. Pour cela, vous devez choisir une stratégie d'échec dans votre fichier de configuration `config/search-string.php` :

```php
// config/search-string.php
return [
    'fail' => 'all-results', // (Default) Silently fail with a query containing everything.
    'fail' => 'no-results',  // Silently fail with a query containing nothing.
    'fail' => 'exceptions',  // Throw exceptions.

    // ...
];
```
