<?php

namespace Ftwcm\Shop\Controller;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Ftwcm\Shop\Attribute;

/**
 * 属性控制器。
 */
class AttrController extends AbstractController
{
    /**
     * @Inject
     * @var Attribute
     */
    private $service;

    /**
     * 属性。
     *
     * RequestMapping(path="attribute", methods="get,post")
     */
    public function attribute()
    {
        $id = (int) $this->request->route('id', 0);
        $method = $this->request->getMethod();

        try {
            switch ($method) {
                case 'GET':
                default:
                    if ($id > 0) {
                        $data = $this->service->view($id);
                    } else {
                        $page = (int) $this->request->route('page', 1);
                        $data = $this->service->list($page);
                        $items = [];
                        foreach ($data['items'] as $itemId) {
                            $items[] = $this->service->load($itemId);
                        }
                        $data['items'] = $items;
                    }
                    break;
                case 'POST':
                    $data = $this->service->create([
                        'name' => $this->request->post('name'),
                        'group_id' => $this->request->post('group_id'),
                        'sort_order' => $this->request->post('sort_order', 0),
                    ]);
                    break;
                case 'PUT':
                    $data = $this->service->update($id, [
                        'name' => $this->request->post('name'),
                        'group_id' => $this->request->post('group_id'),
                        'sort_order' => $this->request->post('sort_order', 0),
                    ]);
                    break;
                case 'DELETE':
                    $data = $this->service->delete($id);
                    break;
            }
        } catch (\Exception $e) {
            return $this->response->json([
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'data' => null
            ]);
        }

        return $this->response->json([
                'code' => 200,
                'message' => 'Success',
                'data' => $data
        ]);
    }

}
