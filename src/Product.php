<?php

namespace Ftwcm\Shop;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\DbConnection\Db;
use Ftwcm\Shop\Model\Product as ProductModel;
use Ftwcm\Shop\Model\ProductCategory;
use Ftwcm\Shop\Model\ProductStore;
use Ftwcm\Shop\Model\ProductAttribute;
use Ftwcm\Shop\Model\ProductOption;
use Ftwcm\Shop\Model\ProductOptionValue;
use Ftwcm\Shop\Model\Option;
use Ftwcm\Shop\Model\OptionValue;
use Ftwcm\Shop\Event\ProductSaved;

/**
 * 商品。
 */
class Product
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->eventDispatcher = $container->get(EventDispatcherInterface::class);
    }

    /**
     * 加载商品。
     *
     * @param int $id 商品ID
     * @return object
     */
    public function load(int $id)
    {
        return ProductModel::findFromCache($id);
    }

    /**
     * 创建商品。
     *
     * @param array $data 商品数据
     * @return mixed
     */
    public function create(array $data)
    {
        $node = new ProductModel;
        $node->name = $data['name'] ?? null;
        $node->description = $data['description'] ?? '';
        $node->tag = $data['tag'] ?? '';
        $node->meta_title = $data['meta_title'] ?? '';
        $node->meta_description = $data['meta_description'] ?? '';
        $node->meta_keyword = $data['meta_keyword'] ?? '';
        $node->model = $data['model'] ?? '';
        $node->sku = $data['sku'] ?? '';
        $node->upc = $data['upc'] ?? '';
        $node->location = $data['location'] ?? '';
        $node->price = $data['price'] ?? 0;
        $node->tax_class_id = $data['tax_class_id'] ?? 0;
        $node->quantity = $data['quantity'] ?? 0;
        $node->minimum = $data['minimum'] ?? 1;
        $node->subtract = $data['subtract'] ?? 1;
        $node->stock_status = $data['stock_status'] ?? 0;
        $node->shipping = $data['shipping'] ?? 1;
        $node->date_available = $data['date_available'] ?? date('Y-m-d');
        $node->length = $data['length'] ?? 0;
        $node->width = $data['width'] ?? 0;
        $node->height = $data['height'] ?? 0;
        $node->length_class = $data['length_class'] ?? 0;
        $node->weight = $data['height'] ?? 0;
        $node->weight_class = $data['weight_class'] ?? 0;
        $node->status = $data['status'] ?? 0;
        $node->sort_order = $data['sort_order'] ?? 0;
        $node->image = $data['image'] ?? '';
        $node->manufacturer_id = $data['manufacturer_id'] ?? 0;

        if (!$node->name) {
            throw new \Exception('无效的商品名称', 400);
        }

        if (mb_strlen($node->name) > 255) {
            throw new \Exception('商品名称太长', 400);
        }

        if (!$node->model) {
            throw new \Exception('无效的型号', 400);
        }

        if (mb_strlen($node->model) > 64) {
            throw new \Exception('型号太长', 400);
        }

        try {
            Db::beginTransaction();

            $node->save();

            foreach ($data['category_ids'] ?? [] as $cid) {
                $productCategory = new ProductCategory;
                $productCategory->product_id = $node->id;
                $productCategory->category_id = (int) $cid;
                $productCategory->save();
            }

            foreach ($data['store_ids'] ?? [] as $sid) {
                $productStore = new ProductStore;
                $productStore->product_id = $node->id;
                $productStore->store_id = (int) $sid;
                $productStore->save();
            }

            foreach ($data['product_attributes'] ?? [] as $pa) {
                $productAttribute = new ProductAttribute;
                $productAttribute->product_id = $node->id;
                $productAttribute->attribute_id = (int) $pa['id'];
                $productAttribute->text = $pa['text'];
                $productAttribute->save();
            }

            foreach ($data['product_options'] ?? [] as $po) {
                if (in_array($po['type'], ['select', 'radio', 'checkbox', 'image'])) {
                    if (isset($po['product_option_value'])) {
                        $productOption = new ProductOption;
                        $productOption->product_id = $node->id;
                        $productOption->option_id = (int) $po['option_id'];
                        $productOption->required = (int) $po['required'];
                        $productOption->save();

                        foreach ($po['product_option_value'] as $pov) {
                            $productOptionValue = new ProductOptionValue;
                            $productOptionValue->product_option_id = $productOption->id;
                            $productOptionValue->product_id = $node->id;
                            $productOptionValue->option_id = (int) $po['option_id'];
                            $productOptionValue->option_value_id = (int) $pov['option_value_id'];
                            $productOptionValue->quantity = (int) $pov['quantity'];
                            $productOptionValue->subtract = (int) $pov['subtract'];
                            $productOptionValue->price = (float) $pov['price'];
                            $productOptionValue->price_prefix = $pov['price_prefix'];
                            $productOptionValue->points = (int) $pov['points'];
                            $productOptionValue->points_prefix = $pov['points_prefix'];
                            $productOptionValue->weight = (float) $pov['weight'];
                            $productOptionValue->weight_prefix = $pov['weight_prefix'];
                            $productOptionValue->save();
                        }
                    }
                } else {
                    $productOption = new ProductOption;
                    $productOption->product_id = $node->id;
                    $productOption->option_id = (int) $po['option_id'];
                    $productOption->value = $po['value'];
                    $productOption->required = (int) $po['required'];
                    $productOption->save();
                }
            }

            Db::commit();

            // Clear caches
            $this->eventDispatcher->dispatch(new ProductSaved($node));

            return $node;
        } catch (\Throwable $e) {
            Db::rollBack();

            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * 查看商品。
     *
     * @param int $id 商品ID
     * @return object
     */
    public function view(int $id)
    {
        $node = $this->load($id);

        if (!$node) {
            throw new \Exception('商品不存在', 404);
        }

        return $node;
    }

    /**
     * 更新商品。
     *
     * @param int $id 商品ID
     * @param array $data 商品
     * @return bool
     */
    public function update(int $id, array $data)
    {
        $node = $this->load($id);

        if (!$node) {
            throw new \Exception('商品不存在', 404);
        }

        if (empty($data['name'])) {
            throw new \Exception('无效的商品名称', 400);
        }

        if (mb_strlen($data['name']) > 255) {
            throw new \Exception('商品名称太长', 400);
        }

        if (!$node->model) {
            throw new \Exception('无效的型号', 400);
        }

        if (mb_strlen($node->model) > 64) {
            throw new \Exception('型号太长', 400);
        }

        $attrs = $node->getAttributes();

        foreach ($data as $key => $value) {
            if ($key !== 'id' && $value !== null && array_key_exists($key, $attrs)) {
                $node->$key = $value;
            }
        }

        try {
            Db::beginTransaction();

            $result = $node->save();

            ProductCategory::query()
                ->where('product_id', $node->id)
                ->delete();

            foreach ($data['category_ids'] ?? [] as $cid) {
                $productCategory = new ProductCategory;
                $productCategory->product_id = $node->id;
                $productCategory->category_id = (int) $cid;
                $productCategory->save();
            }

            ProductStore::query()
                ->where('product_id', $node->id)
                ->delete();

            foreach ($data['store_ids'] ?? [] as $sid) {
                $productStore = new ProductStore;
                $productStore->product_id = $node->id;
                $productStore->store_id = (int) $sid;
                $productStore->save();
            }

            ProductAttribute::query()
                ->where('product_id', $node->id)
                ->delete();

            foreach ($data['product_attributes'] ?? [] as $pa) {
                $productAttribute = new ProductAttribute;
                $productAttribute->product_id = $node->id;
                $productAttribute->attribute_id = (int) $pa['id'];
                $productAttribute->text = $pa['text'];
                $productAttribute->save();
            }

            ProductOption::query()
                ->where('product_id', $node->id)
                ->delete();
            ProductOptionValue::query()
                ->where('product_id', $node->id)
                ->delete();

            foreach ($data['product_options'] ?? [] as $po) {
                if (in_array($po['type'], ['select', 'radio', 'checkbox', 'image'])) {
                    if (isset($po['product_option_value'])) {
                        $productOption = new ProductOption;
                        $productOption->id = (int) $po['product_option_id'];
                        $productOption->product_id = $node->id;
                        $productOption->option_id = (int) $po['option_id'];
                        $productOption->required = (int) $po['required'];
                        $productOption->save();

                        foreach ($po['product_option_value'] as $pov) {
                            $productOptionValue = new ProductOptionValue;
                            $productOptionValue->id = $pov['product_option_value_id'];
                            $productOptionValue->product_option_id = $productOption->id;
                            $productOptionValue->product_id = $node->id;
                            $productOptionValue->option_id = (int) $po['option_id'];
                            $productOptionValue->option_value_id = (int) $pov['option_value_id'];
                            $productOptionValue->quantity = (int) $pov['quantity'];
                            $productOptionValue->subtract = (int) $pov['subtract'];
                            $productOptionValue->price = (float) $pov['price'];
                            $productOptionValue->price_prefix = $pov['price_prefix'];
                            $productOptionValue->points = (int) $pov['points'];
                            $productOptionValue->points_prefix = $pov['points_prefix'];
                            $productOptionValue->weight = (float) $pov['weight'];
                            $productOptionValue->weight_prefix = $pov['weight_prefix'];
                            $productOptionValue->save();
                        }
                    }
                } else {
                    $productOption = new ProductOption;
                    $productOption->id = (int) $po['product_option_id'];
                    $productOption->product_id = $node->id;
                    $productOption->option_id = (int) $po['option_id'];
                    $productOption->value = $po['value'];
                    $productOption->required = (int) $po['required'];
                    $productOption->save();
                }
            }

            Db::commit();

            // Clear caches
            $this->eventDispatcher->dispatch(new ProductSaved($node));

            return $result;
        } catch (\Exception $e) {
            Db::rollBack();

            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * 删除商品。
     *
     * @param int $id 商品ID
     * @return bool
     */
    public function delete(int $id)
    {
        $node = $this->load($id);

        if (!$node) {
            throw new \Exception('商品不存在', 404);
        }

        try {
            $result = $node->delete();

            if ($result > 0) {
                // Clear caches
                $this->eventDispatcher->dispatch(new ProductSaved($node));
            }

            return $result;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * 分页列出商品。
     *
     * @param string $sort 排序字段
     * @param string $order 排序方向
     * @param int $page 页号
     * @param int $perPage 每页记录数
     * @return array
     */
    public function list(string $sort = '', string $order = '', int $page = 1, int $perPage = 20)
    {
        $sortData = ['name', 'model', 'price', 'quantity', 'status', 'sort_order'];
        $sortKey = array_search($sort, $sortData);
        $sort = $sortKey !== false ? $sortData[$sortKey] : false;
        $items = [];

        $paginator = ProductModel::query()
            ->when($sort, function ($query, $sort) use ($order) {
                return $query->orderBy($sort, strtolower($order) === 'desc' ? 'desc' : 'asc');
            })
            ->paginate($perPage, ['*'], 'page', $page);

        foreach ($paginator->items() as $node) {
            $items[] = $node->id;
        }

        return [
            'items' => $items,
            'firstItem' => $paginator->firstItem(),
            'lastItem' => $paginator->lastItem(),
            'perPage' => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'hasMorePages' => $paginator->hasMorePages(),
            'onFirstPage' => $paginator->onFirstPage(),
            'count' => $paginator->count(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * 商品属性列表。
     *
     * @param int $productId 商品ID
     * @return bool
     */
    public function getAttributes(int $productId)
    {
        $ids = $this->getAttributeIds($productId);

        return ProductAttribute::findManyFromCache($ids);
    }

    /**
     * 商品属性ID列表。
     *
     * @Cacheable(prefix="s_product_attributes", ttl=86400)
     * @param int $productId 商品ID
     * @return bool
     */
    public function getAttributeIds(int $productId)
    {
        return ProductAttribute::query()
                ->pluck('id')
                ->toArray();
    }

    /**
     * 取得商品选项。
     *
     * @param int $productOptionId 商品选项ID
     * @return object
     */
    public function getProductOption(int $productOptionId)
    {
        $productOption = ProductOption::findFromCache($productOptionId);

        if ($productOption) {
            $option = Option::findFromCache($productOption->option_id);
            $productOption->name = $option->name;
            $productOption->type = $option->type;
        }

        return $productOption;
    }

    /**
     * 取得商品选项值。
     *
     * @param int $productOptionValueId 商品选项值ID
     * @return object
     */
    public function getProductOptionValue(int $productOptionValueId)
    {
        $productOptionValue = ProductOptionValue::findFromCache($productOptionValueId);

        if ($productOptionValue) {
            $optionValue = OptionValue::findFromCache($productOptionValue->option_id);
            $productOptionValue->name = $optionValue->name;
        }

        return $productOptionValue;
    }

    /**
     * 取得商品选项列表。
     *
     * Cacheable(prefix="s_product_options", ttl=60)
     * @param int $productId 商品ID
     * @return bool
     */
    public function getProductOptions(int $productId)
    {
        $productOptions = Db::table('product_option')
            ->select(['product_option.*', 'options.name', 'options.type'])
            ->leftJoin('options', 'product_option.option_id', '=', 'options.id')
            ->where('product_option.product_id', $productId)
            ->orderBy('options.sort_order')
            ->get();

        foreach ($productOptions as &$po) {
            $productOptionValue = Db::table('product_option_value')
                ->select(['product_option_value.*', 'option_values.name', 'option_values.image'])
                ->leftJoin('option_values', 'product_option_value.option_value_id', '=', 'option_values.id')
                ->where([
                    ['product_option_value.product_id', $productId],
                    ['product_option_value.product_option_id', $po->id]
                ])
                ->orderBy('option_values.sort_order')
                ->get();
            $po->product_option_value = $productOptionValue;
        }
        unset($po);

        return $productOptions;
    }

}
