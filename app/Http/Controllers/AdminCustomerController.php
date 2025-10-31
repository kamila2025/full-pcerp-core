<?php

namespace App\Http\Controllers;

use App\Enums\PermissionNameEnum;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Order;
use App\Models\User;
use App\Repositories\CustomerRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Prettus\Validator\Contracts\ValidatorInterface;
use Prettus\Validator\LaravelValidator;
use Spatie\Activitylog\Models\Activity;

class AdminCustomerController extends Controller
{
    public function __construct(protected CustomerRepository $customerRepository, protected LaravelValidator $validator)
    {
        $this->validator
            ->setRules([
                'name'                         => 'required|string',
                'email'                        => 'nullable|email|required_without:phone|unique:customers,email',
                'phone'                        => 'nullable|string|required_without:email|unique:customers,phone',
                'additional.customer_source'   => 'nullable|string', // 客戶來源
                'additional.id_number'         => 'nullable|string', // 身分證
                'additional.carrier'           => 'nullable|string', // 電信
            ])
            ->setMessages([
                //
            ])
            ->setAttributes([
                'name'                         => '名稱',
                'email'                        => '電子郵件',
                'phone'                        => '電話',
                'additional.customer_source'   => '客戶來源',
                'additional.id_number'         => '身分證',
                'additional.carrier'           => '電信',
            ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize(PermissionNameEnum::所有客戶);

        $attributes = $request->validate([
            'page'      => 'nullable|integer',
            'per_page'  => 'nullable|max:100|integer|multiple_of:5',
        ]);

        return Inertia::render('Customer/Index', [
            'customers' => $this->customerRepository
                ->with('attributionLocation', 'attributionUser')
                ->withSum('orders', 'amount')
                ->unless(Gate::check(PermissionNameEnum::所有權限), fn ($query) => $query->ownedByUser())
                ->latest()
                ->paginate($attributes['per_page'] ?? null),
            'locations' => Location::query()->pluck('name', 'id'),
            'users' => User::query()->pluck('name', 'id'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize(PermissionNameEnum::所有權限);

        return Inertia::render('Customer/CreateOrEdit');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize(PermissionNameEnum::所有客戶);

        $attributes = $request->validate($this->validator->getRules(ValidatorInterface::RULE_CREATE), $this->validator->getMessages(), $this->validator->getAttributes());

        DB::transaction(function () use ($attributes) {
            Customer::create([
                'attribution_user_id'   => auth()->id(),
                'name'                  => $attributes['name'],
                'email'                 => $attributes['email'] ?? null,
                'phone'                 => $attributes['phone'] ?? null,
                'additional'            => $attributes['additional'] ?? null,
            ]);
        });

        return redirect()->route('customers.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        Gate::authorize(PermissionNameEnum::所有客戶);

        return Inertia::render('Customer/CreateOrEdit', [
            'customer' => $customer = Customer::query()
                ->with(['attributionLocation', 'attributionUser',
                       'orders' => function($query) {
                           $query->latest()
                                ->select('id', 'customer_id', 'order_number', 'created_at', 'financial_status', 'fulfillment_status', 'total_price')
                                ->take(5);
                       }])
                ->unless(Gate::check(PermissionNameEnum::所有權限), fn ($query) => $query->ownedByUser())
                ->findOrFail($id),
            'orders' => Order::query()
                ->with('attributionUser:id,name')
                ->where('customer_id', $id)
                ->latest()
                ->take(10)
                ->get(),
            'activities' => Activity::query()
                ->forSubject($customer)
                ->with('subject', 'causer')
                ->latest()
                ->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::所有客戶);

        $attributes = $request->validate($this->validator->setId($id)->getRules(ValidatorInterface::RULE_UPDATE), $this->validator->getMessages(), $this->validator->getAttributes());

        DB::transaction(function () use ($attributes, $id) {
            Customer::findOrFail($id)->update([
                'name'                  => $attributes['name'],
                'email'                 => $attributes['email'] ?? null,
                'phone'                 => $attributes['phone'] ?? null,
                'additional'            => $attributes['additional'] ?? null,
            ]);
        });

        return redirect()->route('customers.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize(PermissionNameEnum::所有客戶);

        DB::transaction(function () use ($id) {
            Customer::findOrFail($id)->delete();
        });

        return redirect()->back();
    }
}
