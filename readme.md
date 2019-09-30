## Criteria

Use this library to create agnostic query filters. This enables loose coupling between the business code and 
the underlying datasets.

### Basic criteria creation

Create a basic filter that will find any elements where the name property is equal to anonymous:

```php
$criteria = Criteria::where()->name->eq('May B. Wright');
```  

Create a filter with multiple requirements:

```php
$criteria = Criteria::where()->name->eq('Justin Case')
    ->and->age->gte(20)
    ->and->age->lt(30)
;
``` 
 
This filter will match on any of the criterion:

```php
$criteria = Criteria::where()->shape->eq('square')
    ->or->color->eq('red')
    ->or->size('medium')
;
```  

Match on any or none of a list of multiple values. The list can either be provided as multiple arguments or as an 
array:

```php
$criteria = Criteria::where()->rank->in(1, 2, 3);

$criteria = Criteria::where()->rank->nin([1, 2, 3);
```

Nesting is required to mix and/or criteria:

```php
$criteria = Criteria::where(Criteria::where()->created->gte('2017-01-01')->and->created->lt('2018-01-01'))
    ->or(Criteria::where()->created->gte('2015-01-01')->and->created->lt('2016-01-01'))
;
``` 

If one of the elements is a single criterion, the initial criteria can be skipped:

```php
$criteria = Criteria::where()->created->gte('2019-01-01')
    ->or(Criteria::where()->color->ne('white')->and->size->in('small', 'medium'))
;
```  

### Transformation of criteria

Use a transformer to generate output the criteria in a specific format. For example use the Mongo transformer to 
generate a query filter for MongoDB\Collection::find():

```php
use Criteria\Criteria;
use Criteria\Transformers\Mongo;

$criteria = Criteria::where()->type->eq('desktop')
    ->and->bit->eq(64)
    ->and(Criteria::where(Criteria::where()->OS->eq('ubuntu')->and->version->gte(18.04))
        ->or(Criteria::where(Criteria::where()->OS->gte('')->and->hertz->gte(31)))
    )->and->release_date->gte(Carbon::parse('2019-01-01', 'UTC'))
;

$criteria->transform(new Mongo());

{
    "$and": [
        {
            "type": {
                "$eq": "desktop"
            }
        },
        {
            "bit": {
                "$eq": 64
            }
        },
        {
            "$or": [
                {
                    "$and": [
                        {
                            "OS": {
                                "$eq": "ubuntu"
                            }
                        },
                        {
                            "version": {
                                "$gte": 18.039999999999999
                            }
                        }
                    ]
                },
                {
                    "$and": [
                        {
                            "OS": {
                                "$gte": ""
                            }
                        },
                        {
                            "hertz": {
                                "$gte": 31
                            }
                        }
                    ]
                }
            ]
        },
        {
            "release_date": {
                "$gte": {
                    "$date": {
                        "$numberLong": "1546300800000"
                    }
                }
            }
        }
    ]
}
--```  
