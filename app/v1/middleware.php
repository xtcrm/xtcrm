<?php
// /v1 网关入口 — 不检查 Session，鉴权由各 Controller 自行处理
return [
    \cores\middleware\AllowCrossDomain::class,
];
