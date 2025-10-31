<?php

namespace App\Repositories;

use App\Models\Customer;

class CustomerRepository extends Repository
{
    protected $fieldSearchable = [
        'name' => 'like',
        'email' => 'like',
        'phone' => 'like',
        'attribution_user_id' => 'in',
        'attribution_location_id' => 'in',
    ];

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model(): string
    {
        return Customer::class;
    }
}
