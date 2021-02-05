<?php

namespace Ftwcm\Shop;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\DbConnection\Db;
use Ftwcm\Shop\Model\Store as StoreModel;
use Ftwcm\Shop\Event\StoreSaved;

/**
 * 商店。
 */
class Store
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
     * 加载商店。
     *
     * @param int $id 商店ID
     * @return object
     */
    public function load(int $id)
    {
        return StoreModel::findFromCache($id);
    }

    /**
     * 创建商店。
     *
     * @param array $data 商店数据
     * @return mixed
     */
    public function create(array $data)
    {
        $node = new StoreModel;
        $node->name = $data['name'] ?? null;

        if (!$node->name) {
            throw new \Exception('无效的商店名称', 400);
        }

        if (mb_strlen($node->name) > 64) {
            throw new \Exception('商店名称太长', 400);
        }

        try {
            $node->save();

            // Clear caches
            $this->eventDispatcher->dispatch(new StoreSaved($node));

            return $node;
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * 列出商店。
     *
     * @return array
     */
    public function fetch()
    {
        $ids = $this->getIds();

        return StoreModel::findManyFromCache($ids);
    }

    /**
     * 商店ID列表。
     *
     * @Cacheable(prefix="s_stores", ttl=86400)
     * @return array
     */
    public function getIds()
    {
        $ids = StoreModel::query()
            ->pluck('id')
            ->toArray();

        return $ids;
    }

    /**
     * 查看商店。
     *
     * @param int $id 商店ID
     * @return object
     */
    public function view(int $id)
    {
        $node = $this->load($id);

        if (!$node) {
            throw new \Exception('商店不存在', 404);
        }

        return $node;
    }

    /**
     * 更新商店。
     *
     * @param int $id 商店ID
     * @param array $data 商店
     * @return bool
     */
    public function update(int $id, array $data)
    {
        $node = $this->load($id);

        if (!$node) {
            throw new \Exception('商店不存在', 404);
        }

        if (empty($data['name'])) {
            throw new \Exception('无效的商店名称', 400);
        }

        if (mb_strlen($data['name']) > 64) {
            throw new \Exception('商店名称太长', 400);
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
            $this->eventDispatcher->dispatch(new StoreSaved($node));

            return $result;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * 删除商店。
     *
     * @param int $id 商店ID
     * @return bool
     */
    public function delete(int $id)
    {
        $node = $this->load($id);

        if (!$node) {
            throw new \Exception('商店不存在', 404);
        }

        try {
            $result = $node->delete();

            if ($result > 0) {
                // Clear caches
                $this->eventDispatcher->dispatch(new StoreSaved($node));
            }

            return $result;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

}
