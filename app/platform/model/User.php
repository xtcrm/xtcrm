<?php

declare (strict_types=1);

namespace app\platform\model;

use cores\BaseModel;
use think\facade\Db;

/**
 * 平台用户模型（store_user 表）
 */
class User extends BaseModel
{
    protected $name = 'store_user';
    protected $pk   = 'store_user_id';
    protected $append = ['department_name', 'login_type'];

    public function getLoginTypeAttr($value, $data): string
    {
        $types = [1 => '账号密码', 2 => '企业微信'];
        return $types[$value] ?? '未知';
    }

    public function getDepartmentNameAttr($value, $data): string
    {
        if (empty($data['department_id'])) return '';
        $dept = Db::name('store_department')->where('id', $data['department_id'])->where('store_id', $data['store_id'] ?? 0)->find();
        return $dept ? $dept['department_name'] : '';
    }

    public function getWxworkAvatarAttr($value, $data): string    { return $value ?: ''; }
    public function getWxworkDepartmentAttr($value, $data): string { return $value ?: ''; }
    public function getWxworkUseridAttr($value, $data): string     { return $value ?: ''; }

    /**
     * 标准化角色数据：前端可能传来 [{label, value}] 对象数组或 [id] 标量数组
     */
    private static function normalizeRoles(array $roles): array
    {
        if (empty($roles)) return [];
        $first = reset($roles);
        if (is_array($first) && isset($first['value'])) {
            return array_column($roles, 'value');
        }
        return $roles;
    }

    /**
     * 登录验证（供移动端 API 使用）
     * @return array|false 成功返回用户数组，失败返回 false，错误通过 $this->error 获取
     */
    public function login(array $data)
    {
        $userInfo = static::withoutGlobalScope()
            ->where('user_name', trim($data['username'] ?? ''))
            ->where('is_delete', 0)
            ->find();

        if (!$userInfo) {
            $this->error = '用户名或密码错误';
            return false;
        }

        if (!password_verify($data['password'] ?? '', $userInfo['password'])) {
            $this->error = '用户名或密码错误';
            return false;
        }

        // 校验租户状态
        $store = \think\facade\Db::name('store')
            ->where('store_id', $userInfo['store_id'])
            ->find();

        if (!$store || !empty($store['is_delete'])) {
            $this->error = '登录失败，未找到当前租户信息';
            return false;
        }
        if (!empty($store['is_recycle'])) {
            $this->error = '登录失败，当前租户已删除';
            return false;
        }

        // 生成 token 并缓存
        $this->token = \app\platform\auth\TokenService::login($userInfo->toArray());
        return $userInfo->toArray();
    }

    /** @var string|null 临时存储生成的 token */
    protected $token;

    /** 返回最近一次登录生成的 token */
    public function getToken(): ?string
    {
        return $this->token;
    }

    public function role()
    {
        return $this->belongsToMany(Role::class, UserRole::class);
    }

    public static function detail($where, array $with = [])
    {
        return static::get($where, $with);
    }

    public static function checkExist(string $userName): bool
    {
        return (bool)static::withoutGlobalScope()
            ->where('user_name', $userName)
            ->where('is_delete', 0)
            ->value('store_user_id');
    }

    public function getList(array $param = [])
    {
        $params = $this->setQueryDefaultValue($param, [
            'search'     => '',
            'isSupplier' => -1,
        ]);

        $query = $this->getNewQuery();

        if (!empty($params['search'])) {
            $query->where('user_name|real_name', 'like', "%{$params['search']}%");
        }
        if ($params['isSupplier'] > -1) {
            $query->where('is_supplier', (int)$params['isSupplier']);
        }

        $list = $query->with(['role'])
            ->where('is_delete', 0)
            ->order(['sort' => 'asc', 'create_time' => 'desc'])
            ->paginate(15);

        // 附加 roleIds 给前端编辑时回显
        foreach ($list as &$item) {
            $roles = $item['role'] ?? [];
            if (is_array($roles)) {
                $item['roleIds'] = array_column($roles, 'role_id');
            } elseif (method_exists($roles, 'toArray')) {
                $item['roleIds'] = array_column($roles->toArray(), 'role_id');
            } else {
                $item['roleIds'] = [];
            }
        }
        return $list;
    }

    public function add(array $data): bool
    {
        $data['user_name'] = strtolower($data['user_name']);
        if (self::checkExist($data['user_name'])) {
            $this->error = '用户名已存在';
            return false;
        }
        if ($data['password'] !== $data['password_confirm']) {
            $this->error = '确认密码不正确';
            return false;
        }
        if (empty($data['roles'])) {
            $this->error = '请选择所属角色';
            return false;
        }

        $data['password'] = encryption_hash($data['password']);
        $data['store_id']  = self::$storeId;
        $data['is_super']  = 0;

        $roleIds = self::normalizeRoles($data['roles'] ?? []);

        $this->transaction(function () use ($data, $roleIds) {
            unset($data['roles']);
            $this->save($data);
            UserRole::increased((int)$this['store_user_id'], $roleIds);
        });
        return true;
    }

    public function edit(array $data): bool
    {
        $data['user_name'] = strtolower($data['user_name']);
        if ($this['user_name'] !== $data['user_name'] && self::checkExist($data['user_name'])) {
            $this->error = '用户名已存在';
            return false;
        }
        if (!empty($data['password']) && $data['password'] !== $data['password_confirm']) {
            $this->error = '确认密码不正确';
            return false;
        }
        if (empty($data['roles']) && !$this['is_super']) {
            $this->error = '请选择所属角色';
            return false;
        }

        if (!empty($data['password'])) {
            $data['password'] = encryption_hash($data['password']);
        } else {
            unset($data['password']);
        }

        $roleIds = self::normalizeRoles($data['roles'] ?? []);

        $this->transaction(function () use ($data, $roleIds) {
            unset($data['roles']);
            $this->save($data);
            if (!$this['is_super']) {
                UserRole::updates((int)$this['store_user_id'], $roleIds);
            }
        });
        return true;
    }

    public function setDelete(): bool
    {
        if ($this['is_super']) {
            $this->error = '超级管理员不允许删除';
            return false;
        }
        return $this->transaction(function () {
            UserRole::deleteAll([['store_user_id', '=', (int)$this['store_user_id']]]);
            return $this->save(['is_delete' => 1]);
        }) !== false;
    }

    public function renew(array $data): bool
    {
        if (!empty($data['password']) && $data['password'] !== $data['password_confirm']) {
            $this->error = '确认密码不正确';
            return false;
        }
        if ($this['user_name'] !== $data['user_name'] && self::checkExist($data['user_name'])) {
            $this->error = '用户名已存在';
            return false;
        }
        if (!empty($data['password'])) {
            $data['password'] = encryption_hash($data['password']);
        }
        return $this->save($data) !== false;
    }
}
