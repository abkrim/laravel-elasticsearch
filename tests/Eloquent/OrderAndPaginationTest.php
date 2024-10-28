<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Workbench\App\Models\Product;

ini_set('memory_limit', '1024M');

function isSorted(Collection $collection, $key, $descending = false): bool
{
    $values = $collection->pluck($key)->toArray();
    for ($i = 0; $i < count($values) - 1; $i++) {
        if ($descending) {
            if ($values[$i] < $values[$i + 1]) {
                return false;
            }
        } else {
            if ($values[$i] > $values[$i + 1]) {
                return false;
            }
        }
    }

    return true;
}

test('products are ordered by status', function () {
    $products = Product::factory(50)->make();
    Product::insert($products->toArray());

    $products = Product::orderBy('status')->get();
    expect(isSorted($products, 'status'))->toBeTrue();
});

test('products are ordered by created_at descending', function () {

    $products = Product::factory(10)->make();
    Product::insert($products->toArray());

    $products = Product::orderBy('created_at', 'desc')->get();
    expect(isSorted($products, 'created_at', true))->toBeTrue();
});

test('products are ordered by name using keyword subfield', function () {
    $products = Product::factory(50)->make();
    Product::insert($products->toArray());

    $products = Product::orderBy('name.keyword')->get();
    expect(isSorted($products, 'name'))->toBeTrue();
});

test('products are paginated', function () {
    $products = Product::factory(50)->make();
    Product::insert($products->toArray());

    $products = Product::where('is_active', true)->paginate(10);
    expect($products)->toHaveCount(10);
});

test('sort products by color with missing values treated as first', function () {
    Product::factory()->state(['color' => null])->create();
    Product::factory()->state(['color' => 'blue'])->create();
    $products = Product::orderBy('color', 'desc')->withSort('color', 'missing', '_first')->get();
    expect($products->first()->color)->toBeNull();
});

test('sort products by geographic location closest to London', function () {
    Product::factory()->state(['manufacturer' => ['location' => ['lat' => 51.50853, 'lon' => -0.12574]]])->create(); // London
    $products = Product::orderByGeo('manufacturer.location', [-0.12574, 51.50853])->get();
    expect(! empty($products))->toBeTrue();
});

test('sort products by geographic location farthest from Paris using multiple points and plane type', function () {
    Product::factory()->state(['manufacturer' => ['location' => ['lat' => 48.85341, 'lon' => 2.3488]]])->create(); // Paris
    $products = Product::orderByGeo('manufacturer.location', [[2.3488, 48.85341], [-0.12574, 51.50853]], 'desc', 'km', 'avg', 'plane')->get();
    expect(! empty($products))->toBeTrue();
});

test('sort products by random', function () {
    $products = Product::factory(50)->make();
    Product::insert($products->toArray());

    $sortA = Product::where('orders', '>', 0)->orderByRandom('orders', 5)->limit(5)->get();
    $sortAFirstId = $sortA->first()->id;
    $sortB = Product::where('orders', '>', 0)->orderByRandom('orders', 7)->limit(5)->get();
    $sortBFirstId = $sortB->first()->id;
    expect($sortAFirstId == $sortBFirstId)->toBeFalse('Seed 5 and 7 should have different results');
    $sortC = Product::where('orders', '>', 0)->orderByRandom('orders', 5)->limit(5)->get();
    $sortCFirstId = $sortC->first()->id;
    expect($sortAFirstId == $sortCFirstId)->toBeTrue('Same Seeds should have same results');

});
