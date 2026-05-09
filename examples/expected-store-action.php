<?php

namespace App\Actions\Customers;

use App\Data\StoreCustomerData;
use App\Models\Customer;

final class StoreCustomerAction
{
    public function handle(StoreCustomerData $data): Customer
    {
        return Customer::query()->create($data->toArray());
    }
}
