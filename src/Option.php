<?php

namespace Ftwcm\Shop;

use Hyperf\DbConnection\Db;
use Ftwcm\Shop\Model\Option as OptionModel;
use Ftwcm\Shop\Model\OptionValue;
use Ftwcm\Shop\Event\OptionSaved;

/**
 * 选项。
 */
class Option
{
    /**
     * 加载选项。
     *
     * @param int $id 选项ID
     * @return object
     */
    public function load(int $id)
    {
        return OptionModel::findFromCache($id);
    }

    /**
     * 创建选项。
     *
     * @param array $data 选项数据
     * @return mixed
     */
    public function create(array $data)
    {
        $node = new OptionModel;
        $node->name = $data['name'] ?? null;
        $node->type = $data['type'] ?? null;
        $node->sort_order = $data['sort_order'] ?? 0;

        if (!$node->name) {
            throw new \Exception('无效的选项名称', 400);
        }

        if (mb_strlen($node->name) > 64) {
            throw new \Exception('选项名称太长', 400);
        }

        if (!in_array($node->type, [
                'radio',
                'checkbox',
                'text',
                'select',
                'textarea',
                'file',
                'date',
                'time',
                'datetime'
            ])) {
            throw new \Exception('无效的类型', 400);
        }

        try {
            Db::beginTransaction();

            $node->save();

            foreach ($data['option_values'] ?? [] as $value) {
                $optionValue = new OptionValue;
                $optionValue->option_id = $node->id;
                $optionValue->name = $value['name'] ?? $node->name;
                $optionValue->image = $value['image'] ?? '';
                $optionValue->sort_order = $value['sort_order'] ?? 0;
                $optionValue->save();
            }

            Db::commit();

            // Clear caches
            $this->eventDispatcher->dispatch(new OptionSaved($node));

            return $node;
        } catch (\Throwable $e) {
            Db::rollBack();

            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * 列出选项。
     *
     * @return array
     */
    public function fetch()
    {
        $ids = $this->getIds();

        return OptionModel::findManyFromCache($ids);
    }

    /**
     * 选项ID列表。
     *
     * @Cacheable(prefix="s_options", ttl=86400)
     * @return array
     */
    public function getIds()
    {
        $ids = OptionModel::query()
            ->pluck('id')
            ->toArray();

        return $ids;
    }

    /**
     * 查看选项。
     *
     * @param int $id 选项ID
     * @return object
     */
    public function view(int $id)
    {
        $node = $this->load($id);

        if (!$node) {
            throw new \Exception('选项不存在', 404);
        }

        return $node;
    }

    /**
     * 更新选项。
     *
     * @param int $id 选项ID
     * @param array $data 选项
     * @return bool
     */
    public function update(int $id, array $data)
    {
        $node = $this->load($id);

        if (!$node) {
            throw new \Exception('选项不存在', 404);
        }

        if (empty($data['name'])) {
            throw new \Exception('无效的选项名称', 400);
        }

        if (mb_strlen($data['name']) > 64) {
            throw new \Exception('选项名称太长', 400);
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

            OptionValue::query()
                ->where('option_id', $node->id)
                ->delete();

            foreach ($data['option_values'] ?? [] as $value) {
                $optionValue = new OptionValue;
                $optionValue->option_id = $node->id;
                $optionValue->name = $value['name'] ?? $node->name;
                $optionValue->image = $value['image'] ?? '';
                $optionValue->sort_order = $value['sort_order'] ?? 0;
                $optionValue->save();
            }

            Db::commit();

            // Clear caches
            $this->eventDispatcher->dispatch(new OptionSaved($node));

            return $result;
        } catch (\Exception $e) {
            Db::rollBack();

            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * 删除选项。
     *
     * @param int $id 选项ID
     * @return bool
     */
    public function delete(int $id)
    {
        $node = $this->load($id);

        if (!$node) {
            throw new \Exception('选项不存在', 404);
        }

        print_r($node->products);

        try {
            Db::beginTransaction();

//            $result = $node->delete();
//
//            OptionValue::query()
//                ->where('option_id', $optionId)
//                ->delete();

            Db::commit();

            if ($result > 0) {
                // Clear caches
                $this->eventDispatcher->dispatch(new OptionSaved($node));
            }

            return $result;
        } catch (\Exception $e) {
            Db::rollBack();

            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * 分页列出选项。
     *
     * @param string $sort 排序字段
     * @param string $order 排序方向
     * @param int $page 页号
     * @param int $perPage 每页记录数
     * @return array
     */
    public function list(string $sort = '', string $order = '', int $page = 1, int $perPage = 20)
    {
        $sortData = ['name', 'type', 'sort_order'];
        $sortKey = array_search($sort, $sortData);
        $sort = $sortKey !== false ? $sortData[$sortKey] : false;
        $items = [];

        $paginator = OptionModel::query()
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
     * 选项的选项值列表。
     *
     * @param int $optionId 选项ID
     * @return array
     */
    public function getOptionValues(int $optionId)
    {
        $ids = $this->getOptionValueIds($optionId);

        return OptionValue::findManyFromCache($ids);
    }

    /**
     * 选项的选项值ID列表。
     *
     * @Cacheable(prefix="s_option_values", ttl=86400)
     * @param int $optionId 选项ID
     * @return array
     */
    public function getOptionValueIds(int $optionId)
    {
        $ids = OptionValue::query()
            ->where('option_id', $optionId)
            ->pluck('id')
            ->toArray();

        return $ids;
    }

}
