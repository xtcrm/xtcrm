<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use cores\DataEngine;

/**
 * 通用数据引擎 CRUD API
 * AI 生成的代码通过此 API 存取数据，无需手写后端
 */
class AppEngine extends BaseController
{
    protected $methodRules = [
        'dataList'   => 'GET',
        'dataCreate' => 'POST',
        'dataUpdate' => 'POST',
        'dataDelete' => 'POST',
        'entitySchema' => 'GET',
    ];

    /** 读取实体的字段 Schema（供前端 EntityForm 渲染） */
    public function entitySchema()
    {
        $appId = $this->request->param('app_id');
        $entityKey = $this->request->param('entity_key');
        if (empty($appId) || empty($entityKey)) return $this->renderError('参数缺失');
        $schema = DataEngine::getSchema($appId, $entityKey);
        if (!$schema) return $this->renderError('实体不存在');
        return $this->renderSuccess($schema);
    }

    /** 确保实体存在（不存在则自动建表） */
    private function ensureEntity(string $appId, string $entityKey): ?string
    {
        $schema = DataEngine::getSchema($appId, $entityKey);
        if ($schema) return $schema['table_name'];

        // 自动建表（通用字段 + CRUD API 必需的列）
        $tableName = DataEngine::createTable($appId, $entityKey, [
            'fields' => [
                ['key'=>'title','label'=>'标题','type'=>'string','index'=>true],
                ['key'=>'content','label'=>'内容','type'=>'text'],
                ['key'=>'status','label'=>'状态','type'=>'string'],
                ['key'=>'priority','label'=>'优先级','type'=>'string'],
                ['key'=>'assignee','label'=>'处理人','type'=>'string'],
                ['key'=>'due_date','label'=>'截止日期','type'=>'date'],
                ['key'=>'customer_id','label'=>'关联客户','type'=>'relation','relation'=>'crm_customer'],
                ['key'=>'remark','label'=>'备注','type'=>'text'],
            ]
        ]);

        \think\facade\Db::name('app_entity')->insert([
            'app_id' => $appId, 'entity_key' => $entityKey,
            'entity_name' => $entityKey, 'table_name' => $tableName,
            'entity_schema' => json_encode(['fields'=>[]], JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
        ]);
        return $tableName;
    }

    public function dataList()
    {
        $appId = $this->request->param('app_id');
        $entityKey = $this->request->param('entity_key');
        if (empty($appId) || empty($entityKey)) return $this->renderError('参数缺失');
        $tableName = $this->ensureEntity($appId, $entityKey);
        $raw = $this->request->param('filter', '');
        // 调试日志
        if (!empty($raw)) {
            error_log('[AppEngine] filter raw: ' . json_encode($raw));
        }
        $result = DataEngine::list($tableName, $this->storeId, $this->request->param());
        return $this->renderSuccess($result);
    }

    public function dataCreate()
    {
        $appId = $this->request->post('app_id');
        $entityKey = $this->request->post('entity_key');
        if (empty($appId) || empty($entityKey)) return $this->renderError('参数缺失');
        $tableName = $this->ensureEntity($appId, $entityKey);
        $data = $this->request->post();
        unset($data['app_id'], $data['entity_key']);

        // Auto-calculate total = quantity * price
        $data = $this->autoCalcTotal($data);

        $id = DataEngine::create($tableName, $this->storeId, $data, $this->getUserId());

        // After stock_in/stock_out create, recalc product stock
        $data['id'] = $id;
        $this->afterStockChange($appId, $entityKey, $data);

        return $this->renderSuccess(['id' => $id], '创建成功');
    }

    public function dataUpdate()
    {
        $appId = $this->request->post('app_id');
        $entityKey = $this->request->post('entity_key');
        $id = intval($this->request->post('id'));
        if (empty($appId) || empty($entityKey) || $id <= 0) return $this->renderError('参数缺失');
        $tableName = $this->ensureEntity($appId, $entityKey);
        $data = $this->request->post();
        unset($data['app_id'], $data['entity_key'], $data['id']);

        // Auto-calculate total = quantity * price
        $data = $this->autoCalcTotal($data);

        DataEngine::update($tableName, $this->storeId, $id, $data);

        // After stock_in/stock_out update, recalc product stock
        $data['id'] = $id;
        $this->afterStockChange($appId, $entityKey, $data);

        return $this->renderSuccess([], '更新成功');
    }

    public function dataDelete()
    {
        $appId = $this->request->post('app_id');
        $entityKey = $this->request->post('entity_key');
        $id = intval($this->request->post('id'));
        if (empty($appId) || empty($entityKey) || $id <= 0) return $this->renderError('参数缺失');
        $tableName = $this->ensureEntity($appId, $entityKey);

        // Read product_id before delete (needed for stock recalculation)
        $productId = null;
        if (in_array($entityKey, ['stock_in', 'stock_out'])) {
            $record = DataEngine::get($tableName, $this->storeId, $id);
            $productId = $record['product_id'] ?? null;
        }

        DataEngine::delete($tableName, $this->storeId, $id);

        // After delete, recalc product stock
        if ($productId) {
            $this->recalcProductStock($appId, (int)$productId);
        }

        return $this->renderSuccess([], '删除成功');
    }

    /**
     * Auto-calculate total = quantity × price for stock entities
     */
    private function autoCalcTotal(array $data): array
    {
        $quantity = floatval($data['quantity'] ?? 0);
        $price = floatval($data['price'] ?? 0);
        if ($quantity > 0 && $price > 0 && empty($data['total'])) {
            $data['total'] = round($quantity * $price, 2);
        }
        return $data;
    }

    /**
     * After stock_in/stock_out create/update, recalculate product stock
     */
    private function afterStockChange(string $appId, string $entityKey, array $data): void
    {
        if (!in_array($entityKey, ['stock_in', 'stock_out'])) return;

        $productId = !empty($data['product_id']) ? intval($data['product_id']) : null;
        if ($productId) {
            $this->recalcProductStock($appId, $productId);
        }
    }

    /**
     * Recalculate product.stock = SUM(stock_in.quantity) - SUM(stock_out.quantity)
     */
    private function recalcProductStock(string $appId, int $productId): void
    {
        try {
            $inTable = DataEngine::tableName($appId, 'stock_in');
            $outTable = DataEngine::tableName($appId, 'stock_out');
            $productTable = DataEngine::tableName($appId, 'product');

            $inTotal = \think\facade\Db::table($inTable)
                ->where('product_id', $productId)
                ->where('store_id', $this->storeId)
                ->where('is_delete', 0)
                ->sum('quantity');

            $outTotal = \think\facade\Db::table($outTable)
                ->where('product_id', $productId)
                ->where('store_id', $this->storeId)
                ->where('is_delete', 0)
                ->sum('quantity');

            $stock = floatval($inTotal ?? 0) - floatval($outTotal ?? 0);

            \think\facade\Db::table($productTable)
                ->where('id', $productId)
                ->where('store_id', $this->storeId)
                ->update(['stock' => $stock, 'update_time' => time()]);
        } catch (\Throwable $e) {
            // Silent fail — stock is derived data, primary write already succeeded
            error_log('[AppEngine] recalcProductStock failed: ' . $e->getMessage());
        }
    }
}
