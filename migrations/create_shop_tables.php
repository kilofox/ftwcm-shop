<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;

class CreateShopTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('分类名称');
            $table->string('image')->nullable()->comment('图像');
            $table->unsignedInteger('parent_id')->default(0)->comment('上级分类ID');
            $table->boolean('top')->default(0)->comment('顶部菜单显示');
            $table->unsignedTinyInteger('column')->default(0)->comment('子菜单显示的列数');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->boolean('status')->default(0)->comment('启用状态');
            $table->text('description')->comment('描述');
            $table->string('meta_title')->comment('Meta Tag 标题');
            $table->string('meta_description')->comment('Meta Tag 描述');
            $table->string('meta_keyword')->comment('Meta Tag 关键词');
        });

        Schema::create('category_path', function(Blueprint $table) {
            $table->unsignedInteger('category_id');
            $table->unsignedInteger('path_id');
            $table->unsignedSmallInteger('level');

            $table->index(['category_id', 'path_id'], 'category_path');
        });

        Schema::create('products', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('商品名称');
            $table->text('description')->comment('描述');
            $table->text('tag')->commentcomment('商品标签');
            $table->string('meta_title')->comment('Meta Tag 标题');
            $table->string('meta_description')->comment('Meta Tag 描述');
            $table->string('meta_keyword')->comment('Meta Tag 关键词');
            $table->string('model', 64)->default('型号');
            $table->string('sku', 64)->comment('库存保有单位');
            $table->string('upc', 12)->comment('统一产品编码');
            $table->string('location', 128)->comment('生产地');
            $table->unsignedInteger('quantity')->default(0)->comment('数量');
            $table->unsignedTinyInteger('stock_status')->default(0)->comment('缺货时状态');
            $table->string('image')->nullable()->comment('图像');
            $table->unsignedInteger('manufacturer_id')->default(0)->comment('制造商ID');
            $table->boolean('shipping')->default(1)->comment('需要配送');
            $table->decimal('price', 10, 2)->default(0)->comment('价格');
            $table->unsignedInteger('points')->default(0)->comment('所需积分');
            $table->unsignedInteger('tax_class_id')->default(0)->comment('税率');
            $table->date('date_available')->comment('上架日期');
            $table->decimal('weight', 12, 4)->default(0)->comment('重量');
            $table->unsignedInteger('weight_class')->default(0)->comment('重量单位');
            $table->decimal('length', 12, 4)->default(0)->comment('尺寸-长');
            $table->decimal('width', 12, 4)->default(0)->comment('尺寸-宽');
            $table->decimal('height', 12, 4)->default(0)->comment('尺寸-高');
            $table->unsignedInteger('length_class')->default(0)->comment('尺寸单位');
            $table->boolean('subtract')->default(1)->comment('减少库存');
            $table->unsignedInteger('minimum')->default(1)->comment('最小购买单位');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->boolean('status')->default(0)->comment('状态，1-启用，2-停用');
            $table->unsignedInteger('viewed')->default(0);
            $table->timestamps();
        });

        Schema::create('product_category', function(Blueprint $table) {
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('category_id');
        });

        Schema::create('product_store', function(Blueprint $table) {
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('store_id');
        });

        Schema::create('product_attribute', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('attribute_id');
            $table->text('text');
        });

        Schema::create('product_option', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('option_id');
            $table->text('value')->nullable()->comment('选项值');
            $table->boolean('required')->comment('必填项');
        });

        Schema::create('product_option_value', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_option_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('option_id');
            $table->unsignedInteger('option_value_id')->comment('选项值ID');
            $table->unsignedInteger('quantity')->comment('数量');
            $table->boolean('subtract')->comment('减少库存');
            $table->decimal('price', 10, 2)->comment('价格');
            $table->char('price_prefix', 1);
            $table->unsignedInteger('points')->comment('所需积分');
            $table->char('points_prefix', 1);
            $table->decimal('weight', 12, 4)->comment('重量');
            $table->char('weight_prefix', 1);
        });

        Schema::create('attributes', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name', 64)->comment('属性名称');
            $table->unsignedInteger('group_id')->default(0)->comment('属性分组ID');
            $table->integer('sort_order')->default(0)->comment('排序');
        });

        Schema::create('attribute_groups', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name', 64)->comment('属性分组名称');
            $table->integer('sort_order')->default(0)->comment('排序');
        });

        Schema::create('options', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name', 128)->comment('选项名称');
            $table->string('type', 16)->comment('类型');
            $table->integer('sort_order')->default(0)->comment('排序');
        });

        Schema::create('option_values', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('option_id');
            $table->string('name', 128)->comment('选项值');
            $table->string('image')->comment('图像');
            $table->integer('sort_order')->default(0)->comment('排序');
        });

        Schema::create('manufacturers', function(Blueprint $table) {
            $table->mediumIncrements('id');
            $table->string('name', 64)->comment('制造商名称');
            $table->string('image')->nullable()->comment('图像');
            $table->mediumInteger('sort_order')->default(0)->comment('排序');
        });

        Schema::create('stores', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name', 64)->comment('商店名称');
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('product_id');
            $table->text('option')->comment('选项');
            $table->unsignedInteger('quantity')->comment('数量');
            $table->timestamp('created_at');

            $table->index(['user_id', 'product_id'], 'user_product');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('parent_id')->default(0);
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('store_id');
            $table->char('order_no', 18);
            $table->string('first_name', 32)->nullable();
            $table->string('telephone', 16)->nullable();
            $table->string('address', 128)->nullable();
            $table->tinyInteger('payment_method')->default(0)->comment('1-支付宝，2-微信，3-陶币');
            $table->string('payment_no', 32)->comment('付款单号')->nullable();
            $table->decimal('total', 10, 2);
            $table->decimal('amount', 10, 2)->comment('实付金额');
            $table->timestamp('pay_time')->comment('付款时间')->nullable();
            $table->decimal('equiv_amount', 10, 2)->comment('等值金额');
            $table->decimal('shipping_fee', 8, 2)->default(0)->comment('运费');
            $table->decimal('shipping_fee_free', 8, 2)->default(0)->comment('包邮运费');
            $table->unsignedTinyInteger('status')->default(0);
            $table->boolean('urged')->comment('是否已催促')->default(0);
            $table->timestamp('place_time')->comment('下单时间')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('order_no');
            $table->index('parent_id');
            $table->index('user_id');
            $table->index('store_id');
        });

        Schema::create('order_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('parent_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('store_id');
            $table->string('code', 64)->nullable()->comment('商品编码');
            $table->string('name', 128);
            $table->string('model_id', 64);
            $table->string('model', 64);
            $table->unsignedInteger('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('image')->nullable();
            $table->text('materials')->nullable();
            $table->decimal('purchase_price', 10, 2)->comment('进货单价');
            $table->unsignedInteger('category_id')->comment('经营类型ID');
            $table->decimal('category_tax', 5, 2)->comment('经营类型税率');
            $table->string('sku', 64)->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('parent_id');
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('first_name', 32);
            $table->string('telephone', 16);
            $table->string('address', 64);
            $table->unsignedInteger('province')->default(0);
            $table->unsignedInteger('city')->default(0);
            $table->unsignedInteger('zone')->default(0);
            $table->boolean('as_default')->default(0);

            $table->index('user_id');
        });

        Schema::create('order_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->tinyInteger('order_status');
            $table->boolean('notify')->default(0);
            $table->text('comment');
            $table->dateTime('created_at');
            $table->integer('operator')->default(0);

            $table->index('order_id');
        });

        Schema::create('order_shipment', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('courier_id');
            $table->string('tracking_number');
            $table->timestamp('created_at');

            $table->index('order_id');
        });

        Schema::create('shipping_couriers', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('courier_code');
            $table->string('courier_name');
        });

        Schema::create('order_refunds', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('parent_id');
            $table->decimal('amount', 10, 2);
            $table->decimal('equiv_amount', 10, 2)->comment('等额金额')->nullable();
            $table->unsignedTinyInteger('refund_method')->default(0)->comment('退款方式，1-支付宝，2-微信，3-陶币');
            $table->unsignedTinyInteger('status')->default(0)->comment('退款状态，0-未处理，1-处理中，2-成功，3-失败');
            $table->timestamp('refund_time')->nullable();
            $table->timestamps();

            $table->unique('order_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('categories');
        Schema::dropIfExists('attribute_groups');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('order_products');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('order_after_sale');
        Schema::dropIfExists('order_history');
        Schema::dropIfExists('after_sale_process');
        Schema::dropIfExists('order_shipment');
        Schema::dropIfExists('shipping_couriers');
        Schema::dropIfExists('order_refunds');
    }

}
