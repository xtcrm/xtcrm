<?php

declare (strict_types=1);

namespace cores;

use Exception;
use think\App;
use think\response\Json;
use think\Validate;
use cores\exception\BaseException;
use think\exception\ValidateException;

/**
 * 控制器基础类(改)
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var Request
     */
    protected $request;

    /**
     * 应用实例
     * @var App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    // 当前是否初始化了 [如果为false 那么基类里的方法不允许被调用]
    private $isInitialized = false;

    /**
     * 构造方法
     * BaseController constructor.
     * @param App $app 应用对象
     * @throws BaseException
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;

        // 标记为已初始化（必须在 initialize() 之前，因为子类的 initialize()
        // 可能需要调用 renderJson() 处理鉴权失败等场景）
        $this->isInitialized = true;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
    }

    /**
     * 验证当前是否已经初始化过
     * @throws Exception
     */
    private function checkInitialized()
    {
        if (!$this->isInitialized) {
            throw new Exception('Currently not initialized');
        }
    }

    /**
     * 验证数据
     * @access protected
     * @param array $data 数据
     * @param string|array $validate 验证器名或者验证规则数组
     * @param array $message 提示信息
     * @param bool $batch 是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * 返回封装后的 API 数据到客户端
     * @param int|null $status
     * @param string $message
     * @param array $data
     * @return Json
     */
    protected final function renderJson(int $status = null, string $message = '', array $data = []): Json
    {
        $this->checkInitialized();
        return json(compact('status', 'message', 'data'));
    }

    /**
     * 返回操作成功json
     * @param array|string $data
     * @param string $message
     * @return Json
     */
    protected final function renderSuccess($data = [], string $message = 'success'): Json
    {
        if (is_string($data)) {
            $message = $data;
            $data = [];
        }
        return $this->renderJson(config('status.success'), $message, $data);
    }


    /**
     * 返回操作失败json
     * @param string $message
     * @param array $data
     * @return Json
     */
    protected final function renderError(string $message = 'error', array $data = []): Json
    {
        return $this->renderJson(config('status.error'), $message, $data);
    }

    /**
     * 获取post数据 (数组)
     * @param $key
     * @return mixed
     */
    protected final function postData($key = null)
    {
        $this->checkInitialized();
        return $this->request->post(empty($key) ? '' : "{$key}/a");
    }

    /**
     * 获取post数据 (数组)
     * @param string $key
     * @return mixed
     */
    protected final function postForm(string $key = 'form')
    {
        return $this->postData($key);
    }

}