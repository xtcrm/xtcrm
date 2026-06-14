<?php
namespace skills\invoice\backend;

use app\platform\backend\BaseController;
use think\facade\Db;

class Config extends BaseController
{
    protected $methodRules = [
        'smsTemplates'     => 'GET',
        'saveSmsTemplates' => 'POST',
        'smsConfig'        => 'GET',
        'saveSmsConfig'    => 'POST',
        'ocrConfig'        => 'GET',
        'saveOcrConfig'    => 'POST',
        'testOcr'          => 'POST',
        'memberList'       => 'GET',
        'searchMember'     => 'GET',
    ];

    public function smsTemplates()
    {
        $rows = Db::name('config')
            ->where('store_id', $this->storeId)
            ->where('config_type', 'invoice_sms')
            ->select()->toArray();

        $templates = [
            'completed' => '【雄韬财税】您的发票已开具。发票号码：{invoice_number}，金额：{total_amount}元。请登录小程序查看详情。',
            'rejected'  => '【雄韬财税】您的开票申请未通过审核。原因：{audit_remark}。请修改后重新提交。',
        ];
        foreach ($rows as $r) {
            $templates[$r['config_name']] = $r['config_value'];
        }
        return $this->renderSuccess($templates);
    }

    public function saveSmsTemplates()
    {
        $data = $this->request->param();
        foreach (['completed', 'rejected'] as $key) {
            if (!isset($data[$key])) continue;
            $exist = Db::name('config')
                ->where('store_id', $this->storeId)
                ->where('config_type', 'invoice_sms')
                ->where('config_name', $key)->find();
            if ($exist) {
                Db::name('config')->where('id', $exist['id'])->update([
                    'config_value' => $data[$key], 'update_time' => time(),
                ]);
            } else {
                Db::name('config')->insert([
                    'store_id' => $this->storeId, 'config_type' => 'invoice_sms',
                    'config_name' => $key, 'config_value' => $data[$key],
                    'create_time' => time(), 'update_time' => time(),
                ]);
            }
        }
        return $this->renderSuccess([], '保存成功');
    }

    public function smsConfig()
    {
        $rows = Db::name('config')
            ->where('store_id', $this->storeId)
            ->where('config_type', 'sms_setting')
            ->select()->toArray();
        $cfg = ['access_key_id' => '', 'access_key_secret' => '', 'sign_name' => '', 'template_code' => ''];
        foreach ($rows as $r) {
            switch ($r['config_name']) {
                case 'sms_ak': $cfg['access_key_id'] = $r['config_value']; break;
                case 'sms_sk': $cfg['access_key_secret'] = $r['config_value']; break;
                case 'sms_sign': $cfg['sign_name'] = $r['config_value']; break;
                case 'sms_template': $cfg['template_code'] = $r['config_value']; break;
            }
        }
        return $this->renderSuccess($cfg);
    }

    public function saveSmsConfig()
    {
        $map = ['access_key_id' => 'sms_ak', 'access_key_secret' => 'sms_sk', 'sign_name' => 'sms_sign', 'template_code' => 'sms_template'];
        foreach ($map as $key => $cname) {
            $val = $this->request->param($key, '');
            $exist = Db::name('config')
                ->where('store_id', $this->storeId)
                ->where('config_type', 'sms_setting')
                ->where('config_name', $cname)->find();
            if ($exist) {
                Db::name('config')->where('id', $exist['id'])->update(['config_value' => $val, 'update_time' => time()]);
            } else {
                Db::name('config')->insert([
                    'store_id' => $this->storeId, 'config_type' => 'sms_setting',
                    'config_name' => $cname, 'config_value' => $val,
                    'create_time' => time(), 'update_time' => time(),
                ]);
            }
        }
        return $this->renderSuccess([], '保存成功');
    }

    public function ocrConfig()
    {
        $cfg = \app\platform\ai\OcrConfig::get($this->storeId);
        return $this->renderSuccess($cfg);
    }

    public function saveOcrConfig()
    {
        $data = $this->request->param();
        $fields = ['endpoint' => 'ocr_endpoint', 'access_key_id' => 'ocr_access_key_id', 'access_key_secret' => 'ocr_access_key_secret'];
        foreach ($fields as $key => $configName) {
            if (!isset($data[$key])) continue;
            $exist = Db::name('config')
                ->where('store_id', $this->storeId)
                ->where('config_type', 'platform_setting')
                ->where('config_name', $configName)->find();
            if ($exist) {
                Db::name('config')->where('id', $exist['id'])->update([
                    'config_value' => $data[$key], 'update_time' => time(),
                ]);
            } else {
                Db::name('config')->insert([
                    'store_id' => $this->storeId, 'config_type' => 'platform_setting',
                    'config_name' => $configName, 'config_value' => $data[$key],
                    'create_time' => time(), 'update_time' => time(),
                ]);
            }
        }
        \app\platform\ai\OcrConfig::clearCache($this->storeId);
        return $this->renderSuccess([], '保存成功');
    }

    public function memberList()
    {
        $keyword = $this->request->param('keyword', '');
        $page = (int) $this->request->param('page', 1);
        $pageSize = (int) $this->request->param('pageSize', 20);

        $query = \think\facade\Db::name('user')
            ->where('store_id', $this->storeId);

        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('user_id', $keyword)
                  ->whereOr('mobile', 'like', "%{$keyword}%")
                  ->whereOr('nick_name', 'like', "%{$keyword}%");
            });
        }

        $total = $query->count();
        $list = $query->field('user_id, nick_name as name, mobile, avatar_id, create_time')
            ->order('user_id desc')
            ->page($page, $pageSize)
            ->select()->toArray();

        foreach ($list as &$m) {
            if ($m['avatar_id']) {
                $m['avatar_url'] = '/uploads/avatar/' . $m['avatar_id'];
            }
        }

        return $this->renderSuccess(compact('list', 'total'));
    }

    public function searchMember()
    {
        $keyword = $this->request->param('keyword', '');
        if (empty($keyword)) return $this->renderError('请输入会员ID或手机号');

        $member = \think\facade\Db::name('user')
            ->where('store_id', $this->storeId)
            ->where(function ($q) use ($keyword) {
                $q->where('user_id', $keyword)
                  ->whereOr('mobile', 'like', "%{$keyword}%");
            })
            ->field('user_id, nick_name as name, mobile, avatar_id, create_time')
            ->find();

        if (!$member) return $this->renderSuccess(null);
        if ($member['avatar_id']) {
            $member['avatar_url'] = '/uploads/avatar/' . $member['avatar_id'];
        }
        return $this->renderSuccess($member);
    }

    public function testOcr()
    {
        $cfg = \app\platform\ai\OcrConfig::get($this->storeId);
        if (empty($cfg['access_key_id'])) {
            return $this->renderError('请先配置 AccessKey');
        }
        try {
            // 用 GD 生成一个有效的小 PNG
            $im = imagecreatetruecolor(20, 20);
            $bg = imagecolorallocate($im, 255, 255, 255);
            $fg = imagecolorallocate($im, 0, 0, 0);
            imagefill($im, 0, 0, $bg);
            imagestring($im, 2, 2, 2, 'OK', $fg);
            ob_start(); imagepng($im); $pngData = ob_get_clean(); imagedestroy($im);
            $tinyPng = base64_encode($pngData);

            $result = \app\platform\ai\OcrGateway::recognize($tinyPng);
            if ($result->success) {
                return $this->renderSuccess([], '连接成功（识别文字: ' . ($result->content ?: '无') . '）');
            }
            return $this->renderError($result->error ?: '连接失败（无详情）');
        } catch (\Throwable $e) {
            return $this->renderError($e->getMessage());
        }
    }
}
