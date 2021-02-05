<?php

namespace Ftwcm\Shop;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\DbConnection\Db;
use Ftwcm\Shop\Model\Cart as CartModel;
use Ftwcm\Shop\Event\CartSaved;
use Ftwcm\Shop\Product;

/**
 * 购物车。
 */
class Cart
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var Product
     */
    protected $productService;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->eventDispatcher = $container->get(EventDispatcherInterface::class);
        $this->productService = $container->get(Product::class);
    }

    /**
     * 加载购物车。
     *
     * @param int $id 购物车ID
     * @return object
     */
    public function load(int $id)
    {
        return CartModel::findFromCache($id);
    }

    /**
     * 将商品添加到购物车。
     *
     * @param int $userId 用户ID
     * @param int $productId 商品ID
     * @param int $quantity 数目
     * @param array $option 选项
     * @return mixed
     */
    public function addToCart(int $userId, int $productId, int $quantity = 1, array $option)
    {
        if ($quantity < 1) {
            throw new \Exception('Invalid quantity', 400);
        }

        $productOptions = $this->productService->getProductOptions($productId);
        foreach ($productOptions as $po) {
            if ($po->required && empty($option[$po->id])) {
                throw new \Exception('选项 ' . $po->name . ' 为必填项', 400);
            }
        }

        try {
            $cart = $this->getUserCartByProduct($userId, $productId, json_encode($option));

            if ($cart) {
                $cart->quantity += $quantity;
            } else {
                $cart = new CartModel;
                $cart->user_id = $userId;
                $cart->product_id = $productId;
                $cart->quantity = $quantity;
                $cart->option = json_encode($option);
            }

            $cart->save();

            // Clear caches
            $this->eventDispatcher->dispatch(new CartSaved($cart));

            return $cart;
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * 取得用户购物车中的商品。
     *
     * @param int $userId 用户ID
     * @return array
     */
    public function getProducts(int $userId)
    {
        $products = [];
        $cartIds = $this->getUserCartIds($userId);
        $carts = CartModel::findManyFromCache($cartIds);

        foreach ($carts as $k => &$cart) {
            $product = $this->productService->load($cart->product_id);

            if (!$product) {
                unset($carts[$k]);
                $this->removeFromCart($userId, [$cart->id]);
                continue;
            }

            if ($product->quantity || $product->quantity <= $cart->quantity) {
                $product->hasStock = true;
            } else {
                $product->hasStock = false;
            }

            $optionData = [];

            foreach (json_decode($cart->option) as $poId => $value) {
                $productOption = $this->productService->getProductOption($poId);

                if (!$productOption) {
                    continue;
                }

                if (in_array($productOption->type ?? '', ['select', 'radio'])) {
                    $productOptionValue = $this->productService->getProductOptionValue((int) $value);

                    if ($productOptionValue->product_option_id === $poId) {
                        if ($productOptionValue->price_prefix === '+') {
                            $product->price += $productOptionValue->price;
                        } elseif ($productOptionValue->price_prefix === '-') {
                            $product->price -= $productOptionValue->price;
                        }

                        if ($productOptionValue->points_prefix === '+') {
                            $product->points += $productOptionValue->points;
                        } elseif ($productOptionValue->points_prefix === '-') {
                            $product->points -= $productOptionValue->points;
                        }

                        if ($productOptionValue->weight_prefix === '+') {
                            $product->weight += $productOptionValue->weight;
                        } elseif ($productOptionValue->weight_prefix === '-') {
                            $product->weight -= $productOptionValue->weight;
                        }

                        if ($productOptionValue->subtract && (!$productOptionValue->quantity || $productOptionValue->quantity < $cart->quantity)) {
                            $product->hasStock = false;
                        }

                        $optionData[] = [
                            'product_option_id' => $poId,
                            'product_option_value_id' => $value,
                            'option_id' => $productOption->option_id,
                            'option_value_id' => $productOptionValue->option_value_id,
                            'name' => $productOption->name,
                            'value' => $productOptionValue->name,
                            'type' => $productOption->type,
                            'quantity' => $productOptionValue->quantity,
                            'subtract' => $productOptionValue->subtract,
                            'price' => $productOptionValue->price,
                            'price_prefix' => $productOptionValue->price_prefix,
                            'points' => $productOptionValue->points,
                            'points_prefix' => $productOptionValue->points_prefix,
                            'weight' => $productOptionValue->weight,
                            'weight_prefix' => $productOptionValue->weight_prefix
                        ];
                    }
                } elseif (($productOption->type ?? '') === 'checkbox' && is_array($value)) {
                    foreach ($value as $povId) {
                        $productOptionValue = $this->productService->getProductOptionValue((int) $povId);

                        if ($productOptionValue->product_option_id === $poId) {
                            if ($productOptionValue->price_prefix === '+') {
                                $product->price += $productOptionValue->price;
                            } elseif ($productOptionValue->price_prefix === '-') {
                                $product->price -= $productOptionValue->price;
                            }

                            if ($productOptionValue->points_prefix === '+') {
                                $product->points += $productOptionValue->points;
                            } elseif ($productOptionValue->points_prefix === '-') {
                                $product->points -= $productOptionValue->points;
                            }

                            if ($productOptionValue->weight_prefix === '+') {
                                $product->weight += $productOptionValue->weight;
                            } elseif ($productOptionValue->weight_prefix === '-') {
                                $product->weight -= $productOptionValue->weight;
                            }

                            if ($productOptionValue->subtract && (!$productOptionValue->quantity || $productOptionValue->quantity < $cart->quantity)) {
                                $product->hasStock = false;
                            }

                            $optionData[] = [
                                'product_option_id' => $poId,
                                'product_option_value_id' => $povId,
                                'option_id' => $productOption->option_id,
                                'option_value_id' => $productOptionValue->option_value_id,
                                'name' => $productOption->name,
                                'value' => $productOptionValue->name,
                                'type' => $productOption->type,
                                'quantity' => $productOptionValue->quantity,
                                'subtract' => $productOptionValue->subtract,
                                'price' => $productOptionValue->price,
                                'price_prefix' => $productOptionValue->price_prefix,
                                'points' => $productOptionValue->points,
                                'points_prefix' => $productOptionValue->points_prefix,
                                'weight' => $productOptionValue->weight,
                                'weight_prefix' => $productOptionValue->weight_prefix
                            ];
                        }
                    }
                } elseif (in_array($productOption->type ?? '', ['text', 'textarea', 'file', 'date', 'datetime', 'time'])) {
                    $optionData[] = [
                        'product_option_id' => $poId,
                        'product_option_value_id' => '',
                        'option_id' => $productOption->option_id,
                        'option_value_id' => '',
                        'name' => $productOption->name,
                        'value' => $value,
                        'type' => $productOption->type,
                        'quantity' => '',
                        'subtract' => '',
                        'price' => '',
                        'price_prefix' => '',
                        'points' => '',
                        'points_prefix' => '',
                        'weight' => '',
                        'weight_prefix' => ''
                    ];
                }
            }

            $product->cart_id = $cart->id;
            $product->quantity = $cart->quantity;
            $product->total = $product->price * $cart->quantity;
            $product->points = $product->points * $cart->quantity;
            $product->weight = $product->weight * $cart->quantity;
            $product->option = $optionData;
            $products[] = $product;
        }
        unset($cart);

        return $products;
    }

    /**
     * Get user cart IDs.
     *
     * @Cacheable(prefix="s_carts", ttl=86400)
     * @param int $userId 用户ID
     * @return array
     */
    public function getUserCartIds(int $userId)
    {
        $ids = CartModel::query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->pluck('id')
            ->toArray();

        return $ids;
    }

    /**
     * 更新购物车中的商品数量。
     *
     * @param object $cart 购物车
     * @return bool
     */
    public function updateCart(object $cart)
    {
        if ($cart->quantity < 1) {
            throw new \Exception('Invalid quantity', 400);
        }

        try {
            $result = $cart->save();

            return $result;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * 从购物车删除商品。
     *
     * @param int $userId 用户ID
     * @param array $keys 商品ID
     * @return int
     */
    public function removeFromCart(int $userId, array $keys)
    {
        $cartItems = CartModel::query()
            ->whereIn('id', $keys)
            ->where('user_id', $userId)
            ->get();
        $ids = [];

        foreach ($cartItems as $cart) {
            $ids[] = $cart->id;
        }

        try {
            $result = CartModel::query()->whereIn('id', $ids)->delete();

            if ($result > 0) {
                foreach ($cartItems as $cart) {
                    // Clear caches
                    $this->eventDispatcher->dispatch(new CartSaved($cart));
                }
            }

            return $result;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * Get user cart by product.
     *
     * @param int $userId 用户ID
     * @param int $productId 商品ID
     * @param string $option 选项
     * @return array
     */
    public function getUserCartByProduct(int $userId, int $productId, string $option)
    {
        $cart = CartModel::query()
            ->where([
                ['user_id', $userId],
                ['product_id', $productId],
                ['option', $option]
            ])
            ->first();

        return $cart;
    }

}
