<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use skills\workorder\service\TicketService;
use app\platform\permission\PermissionChecker;

/**
 * 服务目录（CRM 视角）
 *
 * 数据仍存 workorder_type（与工单类型同一份），避免两套字典。
 * 权限走 CRM 菜单，不依赖工单 skill 的权限项。
 */
class Catalog extends BaseController
{
    protected $methodRules = [
        'lists' => 'GET',
        'add' => 'POST',
        'edit' => 'POST',
        'delete' => 'POST',
        'setStatus' => 'POST',
    ];

    public function lists()
    {
        // 读服务目录：有目录权限，或能建项目/需求的人都应该能选品类
        $allowed = PermissionChecker::can('/crm/catalog/lists')
            || PermissionChecker::can('/crm/intent/add')
            || PermissionChecker::can('/crm/project/add');
        if (!$allowed) return $this->renderError('无权限查看服务目录');

        $result = (new TicketService())->forStore((int)$this->storeId)->getTypeManageList();
        return $this->renderSuccess($result['data'] ?? []);
    }

    public function add()
    {
        if (!$this->checkAction('/crm/catalog/add', '新增服务品类')) return;
        $result = (new TicketService())->forStore((int)$this->storeId)->addType($this->postData());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'], '已添加');
    }

    public function edit()
    {
        if (!$this->checkAction('/crm/catalog/edit', '编辑服务品类')) return;
        $result = (new TicketService())->forStore((int)$this->storeId)->editType($this->postData());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'], '已保存');
    }

    public function delete()
    {
        if (!$this->checkAction('/crm/catalog/delete', '删除服务品类')) return;
        $id = (int)$this->request->param('id', 0);
        $result = (new TicketService())->forStore((int)$this->storeId)->deleteType($id);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '已删除');
    }

    public function setStatus()
    {
        if (!$this->checkAction('/crm/catalog/setStatus', '启停服务品类')) return;
        $id = (int)$this->request->param('id', 0);
        $status = (int)$this->request->param('status', 1);
        $result = (new TicketService())->forStore((int)$this->storeId)->setTypeStatus($id, $status);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '已更新');
    }
}
