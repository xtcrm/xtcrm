<?php
declare (strict_types=1);

namespace app\common\model\store;

use cores\BaseModel;
use think\facade\Cache;
use app\common\library\helper;
use app\common\enum\{
    Client as ClientEnum,
    Setting as SettingEnum,
    file\Storage as StorageEnum,
    payment\Method as PaymentMethodEnum,
    order\DeliveryType as DeliveryTypeEnum,
    setting\sms\Scene as SettingSmsSceneEnum,
    store\page\category\Style as PageCategoryStyleEnum
};

/**
 * 系统设置模型
 * Class Setting
 * @package app\common\model
 */
class Setting extends BaseModel
{
    // 定义表名
    protected $name = 'store_setting';

    protected $createTime = false;

    /**
     * 获取器: 转义数组格式
     * @param $value
     * @return mixed
     */
    public function getValuesAttr($value)
    {
        return helper::jsonDecode($value);
    }

    /**
     * 修改器: 转义成json格式
     * @param $value
     * @return string
     */
    public function setValuesAttr($value): string
    {
        return helper::jsonEncode($value);
    }

    /**
     * 获取指定项设置
     * @param string $key
     * @param int|null $storeId
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getItem(string $key, int $storeId = null)
    {
        $data = self::getAll($storeId);
        return isset($data[$key]) ? $data[$key]['values'] : [];
    }

    /**
     * 获取设置项信息
     * @param string $key
     * @param int|null $storeId
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function detail(string $key, int $storeId = null)
    {
        $query = (new static)->getNewQuery();
        $storeId > 0 && $query->where('store_id', '=', $storeId);
        return $query->where('key', '=', $key)->find();
    }

    /**
     * 全局缓存: 系统设置
     * @param int|null $storeId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getAll(int $storeId = null): array
    {
        $model = new static;
        is_null($storeId) && $storeId = $model::$storeId;
        if (!$data = Cache::get("setting_{$storeId}")) {
            // 获取商城设置列表
            $setting = $model->getList($storeId);
            $data = $setting->isEmpty() ? [] : helper::arrayColumn2Key($setting->toArray(), 'key');
            // 写入缓存中
            Cache::tag('cache')->set("setting_{$storeId}", $data);
        }
        // 重组setting缓存数据 (多维)
        return static::reorganize($model->defaultData(), $data, $type = 'cache');
    }

    /**
     * 获取商城设置列表
     * @param int $storeId
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getList(int $storeId): \think\Collection
    {
        return $this->where('store_id', '=', $storeId)->select();
    }

    /**
     * 默认配置
     * @return array
     */
    public function defaultData(): array
    {
        return [
            // 配送设置
            SettingEnum::DELIVERY => [
                'key' => SettingEnum::DELIVERY,
                'describe' => '配送设置',
                'values' => [
                    // 配送方式
                    'delivery_type' => array_keys(DeliveryTypeEnum::data()),
                    // 物流查询API
                    'traces' => [
                        'enable' => 0,   // 是否启用物流查询服务
                        'default' => 'kd100',  // 默认的服务网关
                        'providerConfig' => [
                            'kd100' => ['customer' => '', 'key' => ''],
                            'aliyun' => ['appCode' => '']
                        ]
                    ]
                ],
            ],
            // 交易设置
            SettingEnum::TRADE => [
                'key' => SettingEnum::TRADE,
                'describe' => '交易设置',
                'values' => [
                    // 订单流程设置
                    'order' => [
                        'closeHours' => 3 * 24,    // 未支付订单自动关闭期限(单位:小时)
                        'receive_days' => '10', // 订单自动确认收货时间期限(单位:天)
                        'refund_days' => '7'    // 订单允许申请售后的时间期限(单位:天)
                    ],
                    // 运费组合策略
                    'freight_rule' => '10',
                ]
            ],
            // 上传设置
            SettingEnum::STORAGE => [
                'key' => SettingEnum::STORAGE,
                'describe' => '上传设置',
                'values' => [
                    'default' => StorageEnum::LOCAL,
                    'engine' => [
                        StorageEnum::LOCAL => null,
                        StorageEnum::QINIU => [
                            'bucket' => '',
                            'access_key' => '',
                            'secret_key' => '',
                            'domain' => 'http://'
                        ],
                        StorageEnum::ALIYUN => [
                            'bucket' => '',
                            'access_key_id' => '',
                            'access_key_secret' => '',
                            'domain' => 'http://'
                        ],
                        StorageEnum::QCLOUD => [
                            'bucket' => '',
                            'region' => '',
                            'secret_id' => '',
                            'secret_key' => '',
                            'domain' => 'http://'
                        ],
                    ]
                ],
            ],
            // 短信通知
            SettingEnum::SMS => [
                'key' => SettingEnum::SMS,
                'describe' => '短信通知',
                'values' => [
                    'default' => 'aliyun',
                    // 短信服务渠道
                    'engine' => [
                        // 阿里云
                        'aliyun' => [
                            'name' => '阿里云短信',
                            'website' => 'https://dysms.console.aliyun.com/dysms.htm',
                            'AccessKeyId' => '',
                            'AccessKeySecret' => '',
                            'sign' => '萤火商城'   // 短信签名
                        ],
                        // 腾讯云
                        'qcloud' => [
                            'name' => '腾讯云短信',
                            'website' => 'https://console.cloud.tencent.com/smsv2',
                            'SdkAppID' => '',
                            'AccessKeyId' => '',
                            'AccessKeySecret' => '',
                            'sign' => '萤火商城'   // 短信签名
                        ],
                        // 七牛云
                        'qiniu' => [
                            'name' => '七牛云短信',
                            'website' => 'https://portal.qiniu.com/sms/dashboard',
                            'AccessKey' => '',
                            'SecretKey' => '',
                        ],
                    ],
                    // 短信通知场景
                    'scene' => [
                        // 短信验证码
                        SettingSmsSceneEnum::CAPTCHA => [
                            'name' => '短信验证码 (通知用户)',    // 场景名称
                            'isEnable' => false,     // 是否开启
                            'templateCode' => '',    // 模板ID
                            'content' => '验证码%s，您正在进行身份验证，打死不要告诉别人哦！',
                            'variables' => [
                                'aliyun' => ['${code}'],
                                'qiniu' => ['${code}'],
                                'qcloud' => ['{1}'],
                            ]
                        ],
                        // 新付款订单
                        SettingSmsSceneEnum::ORDER_PAY => [
                            'name' => '新付款订单 (通知商家)',   // 场景名称
                            'isEnable' => false,    // 是否开启
                            'templateCode' => '',   // 模板ID
                            'acceptPhone' => '',    // 接收手机号
                            'content' => '您有一条新订单，订单号为：%s，请注意查看',
                            'variables' => [
                                'aliyun' => ['${order_no}'],
                                'qiniu' => ['${order_no}'],
                                'qcloud' => ['{1}'],
                            ]
                        ],
                    ]
                ],
            ],
            // 小票打印机设置
            SettingEnum::PRINTER => [
                'key' => SettingEnum::PRINTER,
                'describe' => '小票打印机设置',
                'values' => [
                    'is_open' => 0,    // 是否开启打印
                    'printer_id' => 0, // 打印机id
                    'order_status' => [20], // 订单类型 10下单打印 20付款打印 30确认收货打印
                ],
            ],
            // 满额包邮设置
            SettingEnum::FULL_FREE => [
                'key' => SettingEnum::FULL_FREE,
                'describe' => '满额包邮设置',
                'values' => [
                    'is_open' => 0,     // 是否开启满额包邮
                    'money' => '',      // 单笔订单额度
                    'excludedRegions' => [ // 不参与包邮的地区
                        'cityIds' => [],        // 城市ID集
                        'selectedText' => ''    // 选择的地区(文字)
                    ],
                    'excludedGoodsIds' => [],   // 不参与包邮的商品 (ID集)
                    'describe' => ''            // 满额包邮说明
                ],
            ],
            // 账户注册设置
            SettingEnum::REGISTER => [
                'key' => SettingEnum::REGISTER,
                'describe' => '账户注册设置',
                'values' => [
                    'registerMethod' => 10,   // 默认注册方式: 10=>手机号+短信验证码
                    'isManualBind' => 1,   // 个人中心页显示手动绑定手机号
                    'isOauthMpweixin' => 1,   // 是否开启微信小程序一键授权登录
                    'isForceBindMpweixin' => 1,   // 是否强制绑定手机号(微信小程序)
                    'isOauthWxofficial' => 0,   // 是否开启微信公众号一键授权登录
                    'isForceBindWxofficial' => 1,   // 是否强制绑定手机号(微信公众号)
                ],
            ],
            // 用户充值设置
            SettingEnum::RECHARGE => [
                'key' => SettingEnum::RECHARGE,
                'describe' => '用户充值设置',
                'values' => [
                    'is_entrance' => 1,     // 是否允许用户充值
                    'is_custom' => 1,       // 是否允许自定义金额
                    'lowest_money' => 10,   // 最低充值金额
                    'is_match_plan' => 1,   // 自定义金额是否自动匹配合适的套餐
                    'describe' => "1. 账户充值仅限微信在线方式支付，充值金额实时到账；\n" .
                        "2. 账户充值套餐赠送的金额即时到账；\n" .
                        "3. 账户余额有效期：自充值日起至用完即止；\n" .
                        "4. 若有其它疑问，可拨打客服电话400-000-1234",     // 充值说明
                ],
            ],
            // 积分设置
            SettingEnum::POINTS => [
                'key' => SettingEnum::POINTS,
                'describe' => SettingEnum::data()[SettingEnum::POINTS]['describe'],
                'values' => [
                    'points_name' => '积分',         // 积分名称自定义
                    'is_shopping_gift' => 0,      // 是否开启购物送积分
                    'gift_ratio' => '100',          // 积分赠送比例
                    'is_shopping_discount' => 0,  // 是否允许下单使用积分抵扣
                    'discount' => [     // 积分抵扣
                        'discount_ratio' => '0.01',           // 积分抵扣比例
                        'full_order_price' => '100.00',       // 订单满[?]元
                        'max_money_ratio' => '10',            // 最高可抵扣订单额百分比
                    ],
                    // 积分说明
                    'describe' => "a) 积分不可兑现、不可转让,仅可在本平台使用;\n" .
                        "b) 您在本平台参加特定活动也可使用积分,详细使用规则以具体活动时的规则为准;\n" .
                        "c) 积分的数值精确到个位(小数点后全部舍弃,不进行四舍五入)\n" .
                        "d) 买家在完成该笔交易(订单状态为“已签收”)后才能得到此笔交易的相应积分,如购买商品参加店铺其他优惠,则优惠的金额部分不享受积分获取;",
                ],
            ],
            // 店铺页面风格设置
            SettingEnum::APP_THEME => [
                'key' => SettingEnum::APP_THEME,
                'describe' => '店铺页面风格设置',
                'values' => [
                    'mode' => 10,   // 10系统推荐  20自定义
                    'themeTemplateIdx' => 0,
                    'data' => [
                        'gradualChange' => 1,       // 是否开启按钮渐变色
                        'mainBg' => '#fa2209',      // 主背景颜色
                        'mainBg2' => '#ff6335',     // 主背景颜色 (渐变值)
                        'mainText' => '#ffffff',    // 主文字颜色
                        'viceBg' => '#ffb100',      // 副背景颜色
                        'viceBg2' => '#ffb900',     // 副背景颜色 (渐变值)
                        'viceText' => '#ffffff',    // 副文字颜色
                    ]
                ]
            ],
            // 分类页模板
            SettingEnum::PAGE_CATEGORY_TEMPLATE => [
                'key' => SettingEnum::PAGE_CATEGORY_TEMPLATE,
                'describe' => '分类页模板设置',
                'values' => [
                    'style' => PageCategoryStyleEnum::COMMODITY,    // 分类页样式
                    'shareTitle' => '全部分类',                      // 分享标题
                    'showAddCart' => true,                          // 是否显示购物车按钮
                    'cartStyle' => 1,                               // 购物车按钮样式  1-3
                ]
            ],
            // 商品推荐设置
            SettingEnum::RECOMMENDED => [
                'key' => SettingEnum::RECOMMENDED,
                'describe' => '商品推荐设置',
                'values' => [
                    'enabled' => 1,                 // 是否显示商品推荐
                    'params' => [
                        'source' => 'auto',         // 数据来源 (choice手动选择 auto自动获取)
                        'auto' => [
                            'category' => 0,        // 商品分类 0为全部
                            'goodsSort' => 'all',   // 商品排序 (all默认 sales销量 price价格)
                            'showNum' => 15         // 显示数量
                        ],
                        'goodsIds' => []            // 手动选择的商品ID集
                    ],
                    'style' => [
                        'title' => '商品推荐',
                        'display' => 'list',        // 显示类型 (list列表平铺)
                        'column' => 2,              // 分列数量
                        'show' => [                 // 显示内容
                            'goodsName', 'goodsPrice', 'linePrice', 'sellingPoint', 'goodsSales'
                        ]
                    ],
                ]
            ],
//            // 商家打款设置
//            SettingEnum::MCH_PAY => [
//                'key' => SettingEnum::MCH_PAY,
//                'describe' => '分类页模板设置',
//                'values' => [
//                    // 微信打款设置
//                    PaymentMethodEnum::WECHAT => [
//                        // 微信公众号渠道: 支付模板ID
//                        // ClientEnum::H5_WEIXIN => ['templateId' => 0],
//                        // 微信小程序渠道: 支付模板ID
//                        ClientEnum::MP_WEIXIN => ['templateId' => 0],
//                    ],
//                    // 支付宝打款设置
//                    PaymentMethodEnum::ALIPAY => ['templateId' => 0],
//                ]
//            ],
            // 商城客服设置
            SettingEnum::CUSTOMER => [
                'key' => SettingEnum::CUSTOMER,
                'describe' => '商城客服设置',
                'values' => [
                    'enabled' => 1,         // 是否启用在线客服
                    'provider' => 'mpwxkf', // 当前使用的客服方式
                    'config' => [
                        // 微信小程序客服
                        'mpwxkf' => [],
                        // 企业微信客服
                        'wxqykf' => [
                            'url' => '',    // 客服链接
                            'corpId' => ''  // 企业ID
                        ]
                    ]
                ]
            ]
        ];
    }
}
