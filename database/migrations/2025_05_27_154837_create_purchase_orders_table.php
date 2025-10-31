<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issuer_user_id')->constrained('users')->comment('發起人');
            $table->foreignId('executor_user_id')->nullable()->constrained('users')->comment('執行者');
            $table->foreignId('location_id')->constrained('locations')->comment('地點');
            $table->string('type')->comment('訂單類型: purchase, return');
            $table->string('order_number')->unique()->comment('訂單編號');
            $table->string('custom_number')->unique()->nullable()->comment('自訂編號');
            $table->decimal('other_fee', 10, 2)->default(0)->comment('其他費用');
            $table->decimal('total_amount', 10, 2)->default(0)->comment('總金額');
            $table->dateTime('scheduled_time')->nullable()->comment('預定到貨時間');
            $table->dateTime('actual_time')->nullable()->comment('實際到貨時間');
            $table->longText('remark')->nullable()->comment('備註');
            $table->string('arrival_status')->comment('到貨狀態');
            $table->string('status')->comment('狀態');
            $table->timestamps();
        });
        // {
        //     "created_at": "2025-05-28T03:27:57.640Z",
        //     "updated_at": "2025-05-28T03:27:57.640Z",
        //     "type": "purchase",
        //     "number": "20250528032757163",
        //     "custom_number": "CUS_2020",
        //     "status": "pending",
        //     "arrival_status": "pending_to_receive",
        //     "scheduled_time": null,
        //     "actual_time": null,
        //     "other_fee": {
        //         "cents": 120,
        //         "currency_symbol": "NT$",
        //         "currency_iso": "TWD",
        //         "label": "NT$120",
        //         "dollars": 120.0
        //     },
        //     "total_amount": {
        //         "cents": 419,
        //         "currency_symbol": "NT$",
        //         "currency_iso": "TWD",
        //         "label": "NT$419",
        //         "dollars": 419.0
        //     },
        //     "current_amount": {
        //         "cents": 0,
        //         "currency_symbol": "NT$",
        //         "currency_iso": "TWD",
        //         "label": "",
        //         "dollars": 0.0
        //     },
        //     "total_quantity": 1,
        //     "current_quantity": 0,
        //     "note": "只送",
        //     "child_id": null,
        //     "group_id": null,
        //     "id": "683682bd7104cb000c492dfb",
        //     "issuer": {
        //         "email": "hul12445@toaik.com",
        //         "name": "hul12445@toaik.com",
        //         "phone": "",
        //         "status": "active",
        //         "merchant_id": "536148ab1311667d72000001",
        //         "alias_name": null,
        //         "channel_ids": [],
        //         "id": "683043baa41101000c9c3fdf",
        //         "type": "User",
        //         "pin_code": null,
        //         "is_working": false,
        //         "working_hour": 0
        //     },
        //     "executor": null,
        //     "channel": {
        //         "name": {
        //             "en": "hul12445@toaik.com"
        //         },
        //         "id": "683043d9665a6f002586b012",
        //         "warehouse_id": "683043d9665a6f002586b00c"
        //     },
        //     "items": [
        //         {
        //             "product_id": "68308867400c98000baf308a",
        //             "variation_id": "",
        //             "product_name": {
        //                 "en": "客製化店曩",
        //                 "zh-hant": "客製化店曩"
        //             },
        //             "variation_titles": null,
        //             "variation_name": null,
        //             "image_url": "https://img.shoplineapp.com/media/image_clips/68308867e35ccf000a9ee409/original.png?1748011110",
        //             "sku": null,
        //             "gtin": "067255652055",
        //             "total_quantity": 1,
        //             "current_quantity": 0,
        //             "purchase_price": {
        //                 "cents": 299,
        //                 "currency_symbol": "NT$",
        //                 "currency_iso": "TWD",
        //                 "label": "NT$299",
        //                 "dollars": 299.0
        //             },
        //             "supplier_id": null,
        //             "subtotal": {
        //                 "cents": 299,
        //                 "currency_symbol": "NT$",
        //                 "currency_iso": "TWD",
        //                 "label": "NT$299",
        //                 "dollars": 299.0
        //             },
        //             "price": {
        //                 "cents": 500,
        //                 "currency_symbol": "NT$",
        //                 "currency_iso": "TWD",
        //                 "label": "NT$500",
        //                 "dollars": 500.0
        //             },
        //             "product_removed": false,
        //             "id": "683682bd7104cb000c492dfa",
        //             "stocks": {
        //                 "683043d9665a6f002586b00c": 0
        //             },
        //             "unlimited_quantity": false,
        //             "total_stocks_cost": {
        //                 "cents": 0,
        //                 "currency_symbol": "NT$",
        //                 "currency_iso": "TWD",
        //                 "label": "",
        //                 "dollars": 0.0
        //             },
        //             "same_price": true,
        //             "supplier_name": null
        //         }
        //     ],
        //     "has_removed_items": false,
        //     "group_purchase_orders": null
        // }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
