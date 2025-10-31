<?php

namespace App\Http\Controllers;

use App\Enums\PermissionNameEnum;
use App\Models\Location;
use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Prettus\Validator\Contracts\ValidatorInterface;
use Prettus\Validator\LaravelValidator;

class AdminLocationController extends Controller
{
    public function __construct(protected LaravelValidator $validator)
    {
        $this->validator
            ->setRules([
                ValidatorInterface::RULE_CREATE => [
                    'name'          => 'required|string|max:255',
                    'code'          => 'nullable|string|unique:locations,code|max:255',
                    'address1'      => 'required|string|max:255',
                    'address2'      => 'nullable|string|max:255',
                    'phone'         => 'nullable|string|max:255',
                    'email'         => 'nullable|string|max:255|email',
                    'channels'      => 'nullable|array',
                    'channels.*'    => 'exists:channels,id',
                ],
                ValidatorInterface::RULE_UPDATE => [
                    'name'          => 'required|string|max:255',
                    'code'          => 'nullable|string|unique:locations,code|max:255',
                    'address1'      => 'required|string|max:255',
                    'address2'      => 'nullable|string|max:255',
                    'phone'         => 'nullable|string|max:255',
                    'email'         => 'nullable|string|max:255|email',
                    'channels'      => 'nullable|array',
                    'channels.*'    => 'exists:channels,id',
                ],
            ])
            ->setMessages([
                //
            ])
            ->setAttributes([
                //
            ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize(PermissionNameEnum::地址設定);

        return Inertia::render('Location/Index', [
            'locations' => Location::query()
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize(PermissionNameEnum::地址設定);

        return Inertia::render('Location/CreateOrEdit', [
            'channels' => Channel::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize(PermissionNameEnum::地址設定);

        $attributes = $request->validate($this->validator->getRules(ValidatorInterface::RULE_CREATE), $this->validator->getMessages(), $this->validator->getAttributes());

        DB::transaction(function () use ($attributes) {
            $location = Location::create([
                'name'          => $attributes['name'],
                'code'          => $attributes['code'] ?? null,
                'address1'      => $attributes['address1'],
                'address2'      => $attributes['address2'] ?? null,
                'phone'         => $attributes['phone'] ?? null,
                'email'         => $attributes['email'] ?? null,
                'is_primary'    => Location::where('is_primary', true)->count() === 0,
            ]);

            // 同步頻道關聯
            if (isset($attributes['channels'])) {
                $location->channels()->sync($attributes['channels']);
            }
        });

        return redirect()->route('locations.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        Gate::authorize(PermissionNameEnum::地址設定);

        $location = Location::with('channels')->findOrFail($id);

        return Inertia::render('Location/CreateOrEdit', [
            'location' => $location,
            'channels' => Channel::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::地址設定);

        $attributes = $request->validate($this->validator->setId($id)->getRules(ValidatorInterface::RULE_UPDATE), $this->validator->getMessages(), $this->validator->getAttributes());

        DB::transaction(function () use ($attributes, $id) {
            $location = Location::findOrFail($id);

            $location->update([
                'name'      => $attributes['name'],
                'code'      => $attributes['code'] ?? null,
                'address1'  => $attributes['address1'],
                'address2'  => $attributes['address2'] ?? null,
                'phone'     => $attributes['phone'] ?? null,
                'email'     => $attributes['email'] ?? null,
            ]);

            // 同步頻道關聯
            if (isset($attributes['channels'])) {
                $location->channels()->sync($attributes['channels']);
            }
        });

        return redirect()->route('locations.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize(PermissionNameEnum::地址設定);

        DB::transaction(function () use ($id) {
            $location = Location::findOrFail($id);

            $location->delete();
        });

        return redirect()->route('locations.index');
    }
}
