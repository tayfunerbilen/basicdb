# BasicDb
BasicDb, easy database tool based on PDO
## All
Get all data
```php
$db = basicdb(...);
$db->from("table_name")->all()
```
```php

Array
(
    [0] => stdClass Object
        (
            ...
        )

    [1] => stdClass Object
        (
            ...
        )
    ...

)
```
## First
Get all data
```php
$db = basicdb(...);
$db->from("table_name")->first()
```
```php

Array
(
    [variable] => value
    ...
)
```