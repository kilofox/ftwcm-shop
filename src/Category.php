<?php

namespace Ftwcm\Shop;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\DbConnection\Db;
use Ftwcm\Shop\Model\Category as CategoryModel;
use Ftwcm\Shop\Model\CategoryPath;
use Ftwcm\Shop\Event\CategorySaved;

/**
 * 分类。
 */
class Category
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
     * 加载分类。
     *
     * @param int $id 分类ID
     * @return object
     */
    public function load(int $id)
    {
        return CategoryModel::findFromCache($id);
    }

    /**
     * 创建分类。
     *
     * @param array $data 分类数据
     * @return mixed
     */
    public function create(array $data)
    {
        $node = new CategoryModel;
        $node->name = $data['name'] ?? null;
        $node->parent_id = $data['parent_id'] ?? 0;
        $node->top = $data['top'] ?? 0;
        $node->column = $data['column'] ?? 0;
        $node->sort_order = $data['sort_order'] ?? 0;
        $node->status = $data['status'] ?? 0;
        $node->description = $data['description'] ?? '';
        $node->meta_title = $data['meta_title'] ?? '';
        $node->meta_description = $data['meta_description'] ?? '';
        $node->meta_keyword = $data['meta_keyword'] ?? '';

        if (!$node->name) {
            throw new \Exception('无效的分类名称', 400);
        }

        if (mb_strlen($node->name) > 64) {
            throw new \Exception('分类名称太长', 400);
        }

        if ($node->parent_id < 0) {
            throw new \Exception('无效的上级分类', 400);
        }

        if (mb_strlen($node->meta_title) > 255) {
            throw new \Exception('Meta Tag 标题太长', 400);
        }

        if (mb_strlen($node->meta_description) > 255) {
            throw new \Exception('Meta Tag 描述太长', 400);
        }

        if (mb_strlen($node->meta_keyword) > 255) {
            throw new \Exception('Meta Tag 关键词太长', 400);
        }

        try {
            $node->save();

            $level = 0;
            $paths = CategoryPath::query()
                ->where('category_id', $node->parent_id)
                ->orderBy('level', 'asc')
                ->get();

            foreach ($paths as $cp) {
                $path = new CategoryPath;
                $path->category_id = $node->id;
                $path->path_id = $cp->path_id;
                $path->level = $level;
                $path->save();

                $level++;
            }

            $path = new CategoryPath;
            $path->category_id = $node->id;
            $path->path_id = $node->id;
            $path->level = $level;
            $path->save();

            // Clear caches
            $this->eventDispatcher->dispatch(new CategorySaved($node));

            return $node;
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * 列出分类。
     *
     * @return array
     */
    public function fetch()
    {
        $ids = $this->getIds();

        return CategoryModel::findManyFromCache($ids);
    }

    /**
     * 分类ID列表。
     *
     * @Cacheable(prefix="s_categories", ttl=86400)
     * @return array
     */
    public function getIds()
    {
        $ids = CategoryModel::query()
            ->pluck('id')
            ->toArray();

        return $ids;
    }

    /**
     * 查看分类。
     *
     * @param int $id 分类ID
     * @return object
     */
    public function view(int $id)
    {
        $node = $this->load($id);

        if (!$node) {
            throw new \Exception('分类不存在', 404);
        }

        return $node;
    }

    /**
     * 更新分类。
     *
     * @param int $id 分类ID
     * @param array $data 分类
     * @return bool
     */
    public function update(int $id, array $data)
    {
        $node = $this->load($id);

        if (!$node) {
            throw new \Exception('分类不存在', 404);
        }

        if (empty($data['name'])) {
            throw new \Exception('无效的分类名称', 400);
        }

        if (mb_strlen($data['name']) > 255) {
            throw new \Exception('分类名称太长', 400);
        }

        if (($data['parent_id'] ?? 0) < 0) {
            throw new \Exception('无效的上级分类', 400);
        }

        if (mb_strlen($data['meta_title'] ?? '') > 255) {
            throw new \Exception('Meta Tag 标题太长', 400);
        }

        if (mb_strlen($data['meta_description'] ?? '') > 255) {
            throw new \Exception('Meta Tag 描述太长', 400);
        }

        if (mb_strlen($data['meta_keyword'] ?? '') > 255) {
            throw new \Exception('Meta Tag 关键词太长', 400);
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

            $paths = CategoryPath::query()
                ->where('path_id', $id)
                ->orderBy('level', 'asc')
                ->get();

            if ($paths) {
                foreach ($paths as $cp) {
                    // Delete the path below the current one
                    CategoryPath::query()
                        ->where([
                            ['category_id', '=', $cp->category_id],
                            ['level', '<', $cp->level],
                        ])
                        ->delete();

                    $pathIds = [];

                    // Get the node's new parents
                    $parentPaths = CategoryPath::query()
                        ->where('category_id', $node->parent_id)
                        ->orderBy('level', 'asc')
                        ->get();

                    foreach ($parentPaths as $pcp) {
                        $pathIds[] = $pcp->path_id;
                    }

                    // Get whats left of the node's current path
                    $currentPaths = CategoryPath::query()
                        ->where('category_id', $cp->category_id)
                        ->orderBy('level', 'asc')
                        ->get();

                    foreach ($currentPaths as $ccp) {
                        $pathIds[] = $ccp->path_id;
                    }

                    // Combine the paths with a new level
                    $level = 0;

                    foreach ($pathIds as $pid) {
                        $path = new CategoryPath;
                        $path->category_id = $cp->category_id;
                        $path->path_id = $pid;
                        $path->level = $level;
                        $path->save();

                        $level++;
                    }
                }
            } else {
                // Delete the path below the current one
                CategoryPath::query()
                    ->where('category_id', $id)
                    ->delete();

                // Fix for records with no paths
                $level = 0;

                $parentPaths = CategoryPath::query()
                    ->where('category_id', $node->parent_id)
                    ->orderBy('level', 'asc')
                    ->get();

                foreach ($parentPaths as $pcp) {
                    $path = new CategoryPath;
                    $path->category_id = $id;
                    $path->path_id = $pcp->path_id;
                    $path->level = $level;
                    $path->save();

                    $level++;
                }

                $path = new CategoryPath;
                $path->category_id = $id;
                $path->path_id = $id;
                $path->level = $level;
                $path->save();
            }

            Db::commit();

            // Clear caches
            $this->eventDispatcher->dispatch(new CategorySaved($node));

            return $result;
        } catch (\Exception $e) {
            Db::rollBack();

            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * 删除分类。
     *
     * @param int $id 分类ID
     * @return bool
     */
    public function delete(int $id)
    {
        $node = $this->load($id);

        if (!$node) {
            throw new \Exception('分类不存在', 404);
        }

        try {
            $result = $node->delete();

            if ($result > 0) {
                // Clear caches
                $this->eventDispatcher->dispatch(new CategorySaved($node));
            }

            return $result;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * 分页列出分类。
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

        $paginator = CategoryPath::query()
            ->select([
                Db::raw('GROUP_CONCAT(c1.name ORDER BY category_path.level SEPARATOR \'&nbsp;&nbsp;&gt;&nbsp;&nbsp;\') AS name'),
                'c1.parent_id',
                'c1.sort_order'
            ])
            ->leftJoin(Db::raw('`categories` AS `c1`'), 'category_path.category_id', '=', 'c1.id')
            ->leftJoin(Db::raw('`categories` AS `c2`'), 'category_path.path_id', '=', 'c2.id')
            ->groupBy('category_path.category_id')
            ->when($sort, function ($query, $sort) use ($order) {
                return $query->orderBy($sort, strtolower($order) === 'desc' ? 'desc' : 'asc');
            })
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->items(),
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
