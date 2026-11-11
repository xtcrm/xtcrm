<?php

declare (strict_types=1);

namespace app\platform\model\ai;

use cores\BaseModel;

/**
 * OpenClaw 节点模型
 */
class OpenClawNode extends BaseModel
{
    protected $name = 'openclaw_node';
    protected $pk   = 'id';
    protected $autoWriteTimestamp = true;

    /**
     * 生成节点认证 Token（ocn_ 前缀，52 字符）
     */
    public static function generateAuthToken(): string
    {
        return 'ocn_' . bin2hex(random_bytes(24));
    }

    /**
     * 生成注册 Token（一次性，24h 过期）
     */
    public static function generateRegToken(): array
    {
        return [
            'token'  => bin2hex(random_bytes(24)),
            'expire' => time() + 86400,
        ];
    }

    // ── 查询 ──

    public function getList(array $params = []): \think\Paginator
    {
        $query = $this->db();
        if (!empty($params['keyword'])) {
            $query->where('name', 'like', "%{$params['keyword']}%");
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', (int)$params['status']);
        }
        return $query->order(['create_time' => 'desc'])->paginate(15);
    }

    public function getOnlineList(): array
    {
        return $this->db()
            ->where('status', 1)
            ->field(['id', 'name', 'gateway_url', 'agent_id'])
            ->order(['create_time' => 'desc'])
            ->select()
            ->toArray();
    }

    public static function getById(int $id): ?array
    {
        $row = (new static)->db()->where('id', $id)->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 通过注册 Token 查找（绕过租户 scope）
     */
    public static function findByRegToken(string $regToken): ?array
    {
        $model = new static;
        $model->isGlobalScopeStoreId = false;
        $row = $model->where('reg_token', $regToken)
            ->where('reg_token_expire', '>', time())
            ->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 通过认证 Token 查找（绕过租户 scope，供心跳/激活）
     */
    public static function findByAuthToken(string $authToken): ?array
    {
        $model = new static;
        $model->isGlobalScopeStoreId = false;
        $row = $model->where('auth_token', $authToken)->find();
        return $row ? $row->toArray() : null;
    }

    // ── CRUD ──

    public function add(array $data): bool
    {
        $regToken = self::generateRegToken();
        $data['auth_token']        = self::generateAuthToken();
        $data['reg_token']         = $regToken['token'];
        $data['reg_token_expire']  = $regToken['expire'];
        $data['store_id']          = static::$storeId;
        $data['status']            = 0;
        $data['create_time']       = time();
        $data['update_time']       = time();
        return $this->save($data) !== false;
    }

    public function edit(array $data): bool
    {
        $data['update_time'] = time();
        unset($data['auth_token'], $data['reg_token'], $data['reg_token_expire']);
        return $this->where('id', $this->id)->update($data) !== false;
    }

    public function setDelete(): bool
    {
        return $this->where('id', $this->id)->delete() !== false;
    }

    // ── 节点生命周期 ──

    public function activate(string $gatewayUrl): bool
    {
        return $this->where('id', $this->id)->update([
            'status'           => 1,
            'gateway_url'      => $gatewayUrl,
            'reg_token'        => '',
            'reg_token_expire' => 0,
            'last_heartbeat'   => time(),
            'update_time'      => time(),
        ]) !== false;
    }

    public function heartbeat(): bool
    {
        return $this->where('id', $this->id)->update([
            'status'         => 1,
            'last_heartbeat' => time(),
            'update_time'    => time(),
        ]) !== false;
    }

    public function markOffline(): bool
    {
        return $this->where('id', $this->id)->update([
            'status'      => 2,
            'update_time' => time(),
        ]) !== false;
    }
}
