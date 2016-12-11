# Laravel REST Package
Laravel package with a set of tools helpful for building REST API.

Table of content:
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
    - [item](#item)
    - [collection](#collection)
    - [accepted](#accepted)
    - [noContent](#nocontent)
    - [created](#created)
    - [updated](#updated)
    - [patched](#patched)
    - [deleted](#deleted)
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
php artisan vendor:publish --provider="Mleczek\Rest\RestServiceProvider::class"
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
...

#### With
Include related data in response.
```
users?with=messages
```

In the background if you call the `response()->item(User::query())`
the library will attach related models using Eloquent defined relations,
which is equal to `$quer->with('messages')`.

By default all relations are disabled, which means that you have to explicitly
define which relations can be used in API call. You can set up this in the
previously published `ContextServiceProvider`:
```php
$with = [
    User::class => 'messages',
    Message::class => ['author', 'recipient'],
]
```

If you'd like to do some policy checks then you can use define context class.
In this class you can create methods which name is equal to the relation name.
Whenever you try to access relation the new instance of this class will be created
and the result of the method will determine if the relation can be used or not.
```php
class UserWithContext
{
    public function messages()
    {
        return Auth::user()->is_root;
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

#### Offset
Skip *n* first items.
```
users?offset=3
```

You can use this as well for the related data:
```
users?with=messages&offset=3,messages.5
```

#### Limit
Limit results to *n* models.
```
users?limit=5
```

You can use this as well for the related data:
```
users?with=messages&limit=messages.1
```

#### Fields
Get only specified fields
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

Aboce example will return `first_name`, `last_name` and all columns for the
messages model (eq. `id`, `author_id`, `recipient_id`, `content`).
This helps in finding a sub-optimal query - if you don't want any field from
the related model then just simply remove redundant values from the `with` 
query param.

#### Sort
...

#### Filter
...

### Responses
Library extends the `Response` class using some helpful macros.

#### item($query[, $defaults])
Response single model item with response code `200 OK`.
```php
return response()->item(User::query());
```

This macro will use the `fields` and `with` query param.

This is not recommended to use `with`, `select` and `addSelect` on the `$query`
parameter passed to the class. After all if you would like to do this the behavior
it is as follows:
- if someone pass the same relation name in `with` query param
the one you created will be overridden
- if someone pass the `fields` query parameter then only
fields specified in this parameter will be returned

#### collection($query[, $defaults])
...

#### accepted()
...

#### noContent()
...

#### created($model[, $location])
...

#### updated()
...

#### patched()
...

#### deleted()
...


## Contributing
Thank you for considering contributing! If you would like to fix a bug or propose a new feature, you can submit a Pull Request.


## License
The library is licensed under the [MIT license](http://opensource.org/licenses/MIT).