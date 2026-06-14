<?php

declare (strict_types=1);

namespace app\common\library\storage;

/**
 * 文件上传验证类
 * Class FileValidate
 * @package app\common\library\storage
 */
class FileValidate extends \think\Validate
{
    // 验证规则
    protected $rule = [
        // 图片文件: jpg,jpeg,png,bmp,gif
        // 文件大小: 2MB = (1024 * 1024 * 2) = 2097152 字节
        'image' => 'filesize:2097152|fileExt:jpg,jpeg,png,bmp,gif',

        // 视频文件: mp4
        // 文件大小: 20MB = (1024 * 1024 * 20) = 20971520 字节
        'video' => 'filesize:10485760|fileExt:mp4',

        // 附件: 常用文档格式
        // 文件大小: 10MB
        'file' => 'filesize:10485760|fileExt:doc,docx,xls,xlsx,pdf,txt,zip,rar,pptx,ppt',
    ];

    // 错误提示信息
    protected $message = [
        'image.filesize' => '图片文件大小不能超出2MB',
        'image.fileExt' => '图片文件扩展名有误',
        'video.filesize' => '视频文件大小不能超出10MB',
        'video.fileExt' => '视频文件扩展名有误',
        'file.filesize' => '附件大小不能超出10MB',
        'file.fileExt' => '附件格式不支持',
    ];

    // 验证场景
    protected $scene = [
        'image' => ['image'],
        'video' => ['video'],
        'file'  => ['file'],
    ];
}
