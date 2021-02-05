<?php

namespace Ftwcm\Shop;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Hyperf\Cache\Annotation\Cacheable;
use Ftwcm\Shop\Model\Attribute as AttributeModel;
use Ftwcm\Shop\Event\AttributeSaved;

/**
 * 属性。
 */
class Attribute
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
     * 加载属性。
     *
     * @param int $id 属性ID
     * @return object
     */
    public function load(int $id)
    {
        return AttributeModel::findFromCache($id);
    }

    /**
     * 创建属性。
     *
     * @param array $data 属性数据
     * @return mixed
     */
    public function create(array $data)
    {
        $node = new AttributeModel;
        $node->name = $data['name'] ?? null;
        $node->group_id = $data['group_id'] ?? 0;
        $node->sort_order = $data['sort_order'] ?? 0;

        if (!$node->name) {
            throw new \Exception('无效的属性名称', 400);
        }

        if (mb_strlen($node->name) > 64) {
            throw new \Exception('属性名称太长', 400);
        }

        if ($node->group_id <= 0) {
            throw new \Exception('无效的属性分组', 400);
        }

        try {
            $node->save();

            // Clear caches
            $this->eventDispatcher->dispatch(new AttributeSaved($node));

            return $node;
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * 列出属性。
     *
     * @return array
     */
    public function fetch()
    {
        $ids = $this->getIds();

        return AttributeModel::findManyFromCache($ids);
    }

    /**
     * 属性ID列表。
     *
     * @Cacheable(prefix="s_attributes", ttl=86400)
     * @return array
     */
    public function getIds()
    {
        $ids = AttributeModel::query()
            ->pluck('id')
            ->toArray();

        return $ids;
    }

    /**
     * 查看属性。
     *
     * @param int $id 属性ID
     * @return object
     */
    public function view(int $id)
    {
        $node = $this->load($id);

        if (!$node) {
            throw new \Exception('属性不存在', 404);
        }

        return $node;
    }

    /**
     * 更新属性。
     *
     * @param int $id 属性ID
     * @param array $data 属性
     * @return bool
     */
    public function update(int $id, array $data)
    {
        $node = $this->load($id);

        if (!$node) {
            throw new \Exception('属性不存在', 404);
        }

        if (empty($data['name'])) {
            throw new \Exception('无效的属性名称', 400);
        }

        if (mb_strlen($data['name']) > 64) {
            throw new \Exception('属性名称太长', 400);
        }

        if (($data['group_id'] ?? 0) <= 0) {
            throw new \Exception('无效的属性分组', 400);
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
            $this->eventDispatcher->dispatch(new AttributeSaved($node));

            return $result;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * 删除属性。
     *
     * @param int $id 属性ID
     * @return bool
     */
    public function delete(int $id)
    {
        $node = $this->load($id);

        if (!$node) {
            throw new \Exception('属性不存在', 404);
        }

        try {
            $result = $node->delete();

            if ($result > 0) {
                // Clear caches
                $this->eventDispatcher->dispatch(new AttributeSaved($node));
            }

            return $result;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * 分页列出属性。
     *
     * @param string $sort 排序字段
     * @param string $order 排序方向
     * @param int $page 页号
     * @param int $perPage 每页记录数
     * @return array
     */
    public function list(string $sort = '', string $order = '', int $page = 1, int $perPage = 20)
    {
        $sortData = ['name', 'group_id', 'sort_order'];
        $sortKey = array_search($sort, $sortData);
        $sort = $sortKey !== false ? $sortData[$sortKey] : false;
        $items = [];

        $paginator = AttributeModel::query()
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
