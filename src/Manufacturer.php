<?php

namespace Ftwcm\Shop;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Hyperf\Cache\Annotation\Cacheable;
use Ftwcm\Shop\Model\Manufacturer as ManufacturerModel;
use Ftwcm\Shop\Event\ManufacturerSaved;

/**
 * 制造商/品牌。
 */
class Manufacturer
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
     * 加载制造商/品牌。
     *
     * @param int $id 制造商/品牌ID
     * @return object
     */
    public function load(int $id)
    {
        return ManufacturerModel::findFromCache($id);
    }

    /**
     * 创建制造商/品牌。
     *
     * @param array $data 制造商/品牌数据
     * @return mixed
     */
    public function create(array $data)
    {
        $node = new ManufacturerModel;
        $node->name = $data['name'] ?? null;
        $node->image = $data['image'] ?? '';
        $node->sort_order = $data['sort_order'] ?? 0;

        if (!$node->name) {
            throw new \Exception('无效的制造商/品牌名称', 400);
        }

        if (mb_strlen($node->name) > 64) {
            throw new \Exception('制造商/品牌名称太长', 400);
        }

        try {
            $node->save();

            // Clear caches
            $this->eventDispatcher->dispatch(new ManufacturerSaved($node));

            return $node;
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * 列出制造商/品牌。
     *
     * @return array
     */
    public function fetch()
    {
        $ids = $this->getIds();

        return ManufacturerModel::findManyFromCache($ids);
    }

    /**
     * 制造商/品牌ID列表。
     *
     * @Cacheable(prefix="s_manufacturers", ttl=86400)
     * @return array
     */
    public function getIds()
    {
        $ids = ManufacturerModel::query()
            ->pluck('id')
            ->toArray();

        return $ids;
    }

    /**
     * 查看制造商/品牌。
     *
     * @param int $id 制造商/品牌ID
     * @return object
     */
    public function view(int $id)
    {
        $node = $this->load($id);

        if (!$node) {
            throw new \Exception('制造商/品牌不存在', 404);
        }

        return $node;
    }

    /**
     * 更新制造商/品牌。
     *
     * @param int $id 制造商/品牌ID
     * @param array $data 制造商/品牌
     * @return bool
     */
    public function update(int $id, array $data)
    {
        $node = $this->load($id);

        if (!$node) {
            throw new \Exception('制造商/品牌不存在', 404);
        }

        if (empty($data['name'])) {
            throw new \Exception('无效的制造商/品牌名称', 400);
        }

        if (mb_strlen($data['name']) > 64) {
            throw new \Exception('制造商/品牌名称太长', 400);
        }

        $attrs = $node->getAttributes();

        foreach ($data as $key => $value) {
            if ($key !== 'id' && $value !== null && array_key_exists($key, $attrs)) {
                $node->$key = $value;
            }
        }

        try {
            $result = $node->save();

            // Clear caches
            $this->eventDispatcher->dispatch(new ManufacturerSaved($node));

            return $result;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * 删除制造商/品牌。
     *
     * @param int $id 制造商/品牌ID
     * @return bool
     */
    public function delete(int $id)
    {
        $node = $this->load($id);

        if (!$node) {
            throw new \Exception('制造商/品牌不存在', 404);
        }

        try {
            $result = $node->delete();

            if ($result > 0) {
                // Clear caches
                $this->eventDispatcher->dispatch(new ManufacturerSaved($node));
            }

            return $result;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * 分页列出制造商/品牌。
     *
     * @param string $sort 排序字段
     * @param string $order 排序方向
     * @param int $page 页号
     * @param int $perPage 每页记录数
     * @return array
     */
    public function list(string $sort = '', string $order = '', int $page = 1, int $perPage = 20)
    {
        $sortData = ['name', 'sort_order'];
        $sortKey = array_search($sort, $sortData);
        $sort = $sortKey !== false ? $sortData[$sortKey] : false;
        $items = [];

        $paginator = ManufacturerModel::query()
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

}
