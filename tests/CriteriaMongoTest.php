<?php

namespace Criteria\Tests;

use Carbon\Carbon;
use Criteria\Criteria;
use Criteria\Transformers\Mongo;
use PHPUnit\Framework\TestCase;

class CriteriaMongoTest extends TestCase
{
    /** @test */
    public function transformation()
    {
        $criteria = Criteria::where()->type->eq('desktop')
            ->and->bit->eq(64)
            ->and(Criteria::where(Criteria::where()->OS->eq('ubuntu')->and->version->gte("18.04"))
                ->or(Criteria::where(Criteria::where()->OS->eq('fedora')->and->version->gte(30)))
            )->and->release_date->gte(Carbon::parse('2019-01-01', 'UTC'))
        ;
        $json = json_encode($criteria->transform(new Mongo()), JSON_PRETTY_PRINT);

        $expect = <<<'JSON'
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
                                "$gte": "18.04"
                            }
                        }
                    ]
                },
                {
                    "$and": [
                        {
                            "OS": {
                                "$eq": "fedora"
                            }
                        },
                        {
                            "version": {
                                "$gte": 30
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
JSON;
        $this->assertEquals($expect, $json);
    }

    /** @test */
    public function comparisonMethods()
    {
        $methods = [
            'in' => [1, 2, 3],
            'nin' => [1, 2, 3],
            'eq' => 1,
            'ne' => 2,
            'gt' => 3,
            'gte' => 4,
            'lt' => 5,
            'lte' => 6,
        ];

        foreach ($methods as $method => $value) {
            $criteria = Criteria::where()->$method->$method($value);
            $transformation = $criteria->transform(new Mongo());

            $this->assertArrayHasKey($method, $transformation);
            $this->assertArrayHasKey('$'. $method, $transformation[$method]);
            $this->assertEquals($value, $transformation[$method]['$'. $method]);
        }
    }

    /** @test */
    public function dateValue()
    {
        $criteria = Criteria::where()->date->eq(Carbon::parse('2019-01-01', 'UTC'));
        $transformation = $criteria->transform(new Mongo());

        $this->assertEquals(1546300800000, (string) $transformation['date']['$eq']);
    }
}