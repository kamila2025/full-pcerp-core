<?php

namespace App\Http\Controllers;

use App\Enums\PermissionNameEnum;
use App\Models\Channel;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Prettus\Validator\Contracts\ValidatorInterface;
use Prettus\Validator\LaravelValidator;

class AdminChannelController extends Controller
{
    public function __construct(protected LaravelValidator $validator)
    {
        $this->validator
            ->setRules([
                ValidatorInterface::RULE_CREATE => [
                    'type'                          => 'required|string|in:telegram',
                    'name'                          => 'required|string|max:255',
                    'settings'                      => 'required|array',
                    'settings.telegram_chat_id'     => 'required_if:type,telegram|string',
                    'settings.telegram_bot_token'   => 'required_if:type,telegram|string',
                ],
                ValidatorInterface::RULE_UPDATE => [
                    //
                ],
            ])
            ->setMessages([
                //
            ])
            ->setAttributes([
                'settings.telegram_chat_id' => 'Telegram Chat ID',
                'settings.telegram_bot_token' => 'Telegram Bot Token',
            ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize(PermissionNameEnum::通知設定);

        return Inertia::render('Channel/Index', [
            'channels' => Channel::query()
                ->latest()
                ->paginate($attributes['per_page'] ?? null),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize(PermissionNameEnum::通知設定);

        $attributes = $request->validate($this->validator->getRules(ValidatorInterface::RULE_CREATE), $this->validator->getMessages(), $this->validator->getAttributes());

        DB::transaction(function () use ($attributes) {
            Channel::create([
                'name'          => $attributes['name'],
                'type'          => $attributes['type'],
                'settings'      => $attributes['settings'],
            ]);
        });

        return redirect()->route('channels.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize(PermissionNameEnum::通知設定);

        DB::transaction(function () use ($id) {
            $channel = Channel::findOrFail($id);

            $channel->delete();
        });

        return redirect()->route('channels.index');
    }
}
