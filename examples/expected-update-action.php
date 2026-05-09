<?php

namespace App\Actions\Customers;

use App\Data\UpdateCustomerData;
use App\Models\Customer;

final class UpdateCustomerAction
{
    public function handle(Customer $customer, UpdateCustomerData $data): Customer
    {
        $customer->update($data->toArray());

        return $customer;
    }
}
