<?php

namespace App\Http\Controllers;

use App\Actions\Customers\StoreCustomerAction;
use App\Actions\Customers\UpdateCustomerAction;
use App\Data\StoreCustomerData;
use App\Data\UpdateCustomerData;
use App\Models\Customer;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class CustomerController extends Controller
{
    public function __construct(
        private readonly StoreCustomerAction $storeCustomerAction,
        private readonly UpdateCustomerAction $updateCustomerAction,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return CustomerResource::collection(Customer::query()->paginate());
    }

    public function store(StoreCustomerRequest $request): CustomerResource
    {
        $customer = $this->storeCustomerAction->handle(
            StoreCustomerData::fromRequest($request),
        );

        return new CustomerResource($customer);
    }

    public function show(Customer $customer): CustomerResource
    {
        return new CustomerResource($customer);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): CustomerResource
    {
        $customer = $this->updateCustomerAction->handle(
            $customer,
            UpdateCustomerData::fromRequest($request),
        );

        return new CustomerResource($customer);
    }

    public function destroy(Customer $customer): Response
    {
        $customer->delete();

        return response()->noContent();
    }
}
