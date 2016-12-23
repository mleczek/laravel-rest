# Laravel REST Package
Laravel package with a set of tools helpful for building REST API.

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#basic-usage)
  - [Query params](#query-params)
    - [With](#with)
    - [Offset](#offset)
    - [Limit](#limit)
    - [Fields](#fields)
    - [Sort](#sort)
    - [Filter](#filter)
  - [Responses](#responses)
    - [Item](#item)
    - [Collection](#collection)
    - [Accepted](#accepted)
    - [No Content](#no-content)
    - [Created](#created)
    - [Updated](#updated)
    - [Patched](#patched)
    - [Deleted](#deleted)
- [Tips and tricks](#tips-and-tricks)
  - [Default Context](#default-context)
- [Contributing](#contributing)
- [License](#license)


## Installation
To install this package you will need:
- Laravel 5.3+
- PHP 5.6.4+

Require this package with composer:
```
composer require mleczek/rest
```

In `config/app.php` add the `RestServiceProvider`:
```php
'providers' => [
    Mleczek\Rest\RestServiceProvider::class,
]
```

## Configuration
Publish the package configuration and `ContextServiceProvider`:
```
php artisan vendor:publish --provider="Mleczek\Rest\RestServiceProvider"
```

This command will create 2 files for you:
- `config/rest.php`
- `app/Providers/ContextServiceProvider.php`

Register new local copy of the `ContextServiceProvider` in the `config/app.php` file:
```php
'providers' => [
    App\Providers\ContextServiceProvider::class,
]
```


## Usage

### Query params
Supplied by the client. They control the format of the response most often
by narrowing result.

#### With
Include related data in response:
```
users?with=messages,permissions
```

In the background the library will attach related models using Eloquent defined relations,
which is quite similar to calling `$quer->with(['messages', 'permissions'])`.

By default all relations are disabled, which means that you have to explicitly
define which relations can be used in API call. You can set up this in the
previously published `ContextServiceProvider`:
```php
$with = [
    User::class => 'messages',
    Message::class => ['author', 'recipient'],
]
```

If you'd like to do some policy checks then you can define context class.
In this class you can create methods which name is equal to the relation name.
Whenever you try to access this relation the new instance of this class will be created
and the result of the method will determine if the relation can be used or not.
```php
class UserWithContext
{
    public function messages()
    {
        return Auth::check() && Auth::user()->is_root;
    }
}
```

Of course you have to register that context in the `ContextServiceProvider`:
```php
$with = [
    User::class => UserWithContext::class,
]
```

Now if someone without root access call the `with=messages` then nothing will happen.
If you'd like you can throw 401 or 403 response code from the context class.

In `UserWithContext` class and other context classes you can inject your dependencies
in the constructor, because class is resolved using service container.

#### Offset
Skip *n* first items:
```
users?offset=3
```

You can use this as well for the related data:
```
users?with=messages&offset=3,messages.5
```

#### Limit
Limit results to *n* items:
```
users?limit=5
```

You can use this as well for the related data:
```
users?with=messages&limit=messages.1
```

#### Fields
Get only specified fields:
```
users?fields=first_name,last_name
```

You can use this as well for the related data:
```
users?with=messages&fields=first_name,last_name,messages.id
```

If you specify fields only for the primary model
then all fields will be retrieved for the related one:
```
users?with=messages&fields=first_name,last_name
```

Above example will return `first_name`, `last_name` and all columns for the
messages model (eq. `id`, `author_id`, `recipient_id`, `content`).
This helps in finding a sub-optimal query - if you don't want any field from
the related model then just simply remove redundant values from the `with` 
query param.

#### Sort
Return results in specified order:
```
users?sort=score,last_name_desc
```

You can use this as well for the related data:
```
users?with=messages&sort=messages.latest
```

By default no sort methods are available, which means that you have to explicitly
define sort that can be used for specific model. You can set up this in the
previously published `ContextServiceProvider`:
```php
$sort = [
    Message::class => MessageSortContext::class,
]
```

Then you have to create context class. Sort name will be converted to method name using
camelCase style (eq. `last_name_desc` will call `lastNameDesc` method). As a first
argument you will receive the `Illuminate\Database\Query\Builder`.

```php
class MessageSortContext
{
    public function latest($query)
    {
        $query->latest();
    }
}
```

Now you can sort messages using `latest` method in any context:
```
users?with=messages&sort=messages.latest
messages?with=author&sort=latest
```

In `MessageSortContext` class and other context classes you can inject your dependencies
in the constructor, because class is resolved using service container.

#### Filter
Put constraint on request:
```
users?filter=score_above:30,last_name_in:[Smith,Bloggs]
```

Unlike `sort` param the `filter` query param can also accept arguments:
```
users?filter=without_args
users?filter=one_arg:5
users?filter=special_chars:"O'X\" []],,"
users?filter=two_or_more_args:[12,"Lorem lipsum"]
```

You can use this as well for the related data:
```
users?with=messages&filter=messages.recipient_id:5
```

By default no filter methods are available, which means that you have to explicitly
define filters that can be used for specific model. You can set up this in the
previously published `ContextServiceProvider`:
```php
$filter = [
    User::class => UserFilterContext::class,
]
```

Then you have to create context class. Filter name will be converted to method name using
camelCase style (eq. `last_name_in` will call `lastNameIn` method). As a first
argument you will receive the `Illuminate\Database\Query\Builder`.

```php
class UserFilterContext
{
    public function scoreAbove($query, $value)
    {
        // Validation of the $value argument...
        
        $query->where('score', '>', $value);
    }
}
```

Now you can filter users using `scoreAbove` method in any context:
```
users?filter=score_above:15
groups?with=users&filter=users.score_above:5
```

In `UserFilterContext` class and other context classes you can inject your dependencies
in the constructor, because class is resolved using service container.


### Responses
Library extends the `Response` class using some helpful macros.

#### Item
```php
response()->item($query);
```

Response single model item with response code `200 OK`:
```php
return response()->item(User::query());
```

This macro will use the `fields` and `with` query param.

This is not recommended to use `with`, `select` and `addSelect` on the `$query`
parameter passed to the method. After all if you would like to do this the behavior
it is as follows:
- if someone pass the same relation name in `with` query param
the one you created will be overridden
- if someone pass the `fields` query parameter then only
fields specified in this parameter will be returned

Often you will need to do some operations using retrieved model, in this case
use `rest()` helper funtion:
```php
public function show()
{
    $user = rest()->item(User::query());
    $this->authorize('show', $user);

    return response()->item($user);
}
```

#### Collection
```php
response()->collection($query);
```

Response collection of models with response code `206 Partial Content`:
```php
return response()->collection(User::query());
```

This macro will use the `fields`, `sort`, `filter`, `offset`, `limit`
and `with` query param. Again, using `orderBy`, `select`, `addSelect`, `limit/take`,
`offset/skip` methods on the `$query` argument is not recommended, but fell free
to add some constraints using `where` method.
```php
return response()->collection(User::where('is_root', false));
```

If you will need to make some operations before returning response you can use
`rest()` helper function:
```php
public function show()
{
    $users = rest()->collection(User::query());
    // Some operations goes here...
    // $users->count  - number of retrieved models [0,limit]
    // $users->limit  - max number of retrieved models
    // $users->offset - number of skipped models
    // $users->data   - retrieved models

    return response()->collections($users);
}
```

#### Accepted
```php
response()->accepted();
```

Empty response with status code `202 Accepted`.

#### No Content
```php
response()->noContent();
```

Empty response with status code `204 No Content`.

#### Created
```php
response()->created($model[, $location]);
```

Response created model with status code `201 Created`. If `$location` is specified
then appropriate `Location` header will be added to the response.

#### Updated
```php
response()->updated([$model]);
```

Response updated model (if provided) with status code `200 OK`.

#### Patched
```php
response()->patched([$model]);
```

Response part of updated model (if provided) with status code `200 OK`.

#### Deleted
```php
response()->deleted();
```

Empty response with status code `204 No Content`.


## Tips and tricks

### Default Context

By default library implements 2 context classes:
```php
protected $sort = [
    // Your context classes...
    User::class => \Mleczek\Rest\Context\DefaultSortContext::class,
];

protected $filter = [
    // Your context classes...
    User::class => \Mleczek\Rest\Context\DefaultFilterContext::class,
];
```

These context can be used with any class and allow sorting and filtering using
fillable attributes. Example usage for the default User model:
```
// ?filter=<attribute_name>:<expected_value>
users?filter=email:"rest@example.com"
users?filter=password:some_string // side effect

// ?sort=<attribute_name> or ?sort=<attribute_name>_desc
users?sort=name,email_desc
users?sort=name_desc
```

**It's recommended to use only for the dev purposes**. In future releases implementation
will change in order to prevent accidental security vulnerabilities (like the above one
with password column). Any ideas are welcome.


## Contributing
Thank you for considering contributing! If you would like to fix a bug or propose
a new feature, you can submit a Pull Request.

Some tasks requiring attention have been listed below:

- [ ] Write tests
- [x] Resolve context classes using service container
- [x] Default sort and filter context
- [ ] Timestamp sort and filter context
- [ ] Params validation
- [x] Pre processing (query)
- [ ] Post processing (results)
- [x] Add macro `response()->item($model)`
- [x] Add macro `response()->collection($models)`
- [x] Implement `QueryExecutor` and associated `rest()` helper: `rest()->item($query)`
- [ ] Support for all versions of Laravel 5 (currently tested only on v5.3)
- [ ] Contracts and drivers (currently depends on Eloquent)
- [ ] Content negotiation (support xml response)
- [ ] Add option which when enabled allow to use only one filter/sort method
- [ ] Better documentation (GitHub pages)
- [ ] Example usage (tutorial)


## License
The library is licensed under the [MIT license](http://opensource.org/licenses/MIT).