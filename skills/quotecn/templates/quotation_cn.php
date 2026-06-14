<style>
  .quote-cn-wrapper { font-family: "SimSun","宋体","Microsoft YaHei","微软雅黑",serif; font-size:14px; color:#333; padding:0; max-width:720px; }
  .quote-cn-wrapper * { box-sizing: border-box; }
  .quote-cn-header { text-align:center; margin-bottom:16px; }
  .quote-cn-header h1 { font-size:21px; font-weight:bold; margin:0 0 4px 0; letter-spacing:3px; }
  .quote-cn-header .sub { font-size:13px; color:#666; }
  .quote-cn-divider { border:none; border-top:2px solid #333; margin:10px 0; }
  .quote-cn-divider-thin { border:none; border-top:1px solid #999; margin:6px 0; }
  .quote-cn-title { text-align:center; font-size:20px; font-weight:bold; margin:15px 0; letter-spacing:10px; }
  .quote-cn-info { width:100%; border-collapse:collapse; margin-bottom:12px; }
  .quote-cn-info td { padding:4px 6px; font-size:13px; vertical-align:top; }
  .quote-cn-info .lbl { color:#666; white-space:nowrap; }
  .quote-cn-info .val { font-weight:bold; }
  .quote-cn-items { width:100%; border-collapse:collapse; margin-bottom:12px; }
  .quote-cn-items th { background:#f5f5f5; border:1px solid #999; padding:7px 4px; font-size:12px; font-weight:bold; text-align:center; }
  .quote-cn-items td { border:1px solid #999; padding:6px 4px; font-size:12px; text-align:center; }
  .quote-cn-items .tl { text-align:left; }
  .quote-cn-items .tr { text-align:right; }
  .quote-cn-summary { margin-bottom:12px; text-align:right; }
  .quote-cn-summary table { margin-left:auto; }
  .quote-cn-summary td { padding:4px 8px; font-size:13px; }
  .quote-cn-summary .lbl { color:#666; text-align:right; }
  .quote-cn-summary .val { font-weight:bold; min-width:100px; text-align:right; }
  .quote-cn-summary .total td { font-size:15px; font-weight:bold; padding-top:6px; }
  .quote-cn-cn { border:1px solid #999; padding:8px 12px; margin-bottom:12px; font-size:14px; font-weight:bold; }
  .quote-cn-cn .lbl { color:#666; font-weight:normal; }
  .quote-cn-terms { margin-bottom:12px; }
  .quote-cn-terms h4 { font-size:14px; margin:0 0 5px 0; }
  .quote-cn-terms pre { font-family:"SimSun","宋体",serif; font-size:12px; line-height:1.7; white-space:pre-wrap; color:#555; padding:0; margin:0; background:none; border:none; }
  .quote-cn-stamp { margin-top:25px; }
  .quote-cn-stamp table { width:100%; }
  .quote-cn-stamp td { width:50%; padding:8px 15px; font-size:12px; text-align:center; vertical-align:top; }
  .quote-cn-stamp .box { border:1px dashed #999; height:55px; margin-bottom:4px; }
  .quote-cn-footer { text-align:center; font-size:11px; color:#999; margin-top:18px; border-top:1px solid #eee; padding-top:8px; }

  @media print {
    .quote-cn-wrapper { max-width:none; }
    @page { size:A4; margin:12mm; }
  }
</style>

<div class="quote-cn-wrapper">

<div class="quote-cn-header">
  <?php if (!empty($company['company_logo'])): ?>
    <div style="margin-bottom:10px">
      <img src="<?= htmlspecialchars($logo_url ?? '') ?>" alt="logo" style="max-height:50px;max-width:180px">
    </div>
  <?php endif; ?>
  <?php if (!empty($company['company_name'])): ?>
    <h1><?= htmlspecialchars($company['company_name']) ?></h1>
    <div class="sub">
      <?= htmlspecialchars($company['company_address'] ?? '') ?>
      <?php if (!empty($company['company_phone'])): ?>&nbsp;电话：<?= htmlspecialchars($company['company_phone']) ?><?php endif; ?>
    </div>
  <?php else: ?>
    <h1>公司名称</h1>
    <div class="sub">（请在模板设置中填写公司信息）</div>
  <?php endif; ?>
</div>

<hr class="quote-cn-divider">
<div class="quote-cn-title">报 价 单</div>
<hr class="quote-cn-divider-thin">

<table class="quote-cn-info">
  <tr>
    <td class="lbl" width="12%">客户名称：</td>
    <td class="val" width="38%"><?= htmlspecialchars($customer['customer_name'] ?? '') ?></td>
    <td class="lbl" width="12%">报价单号：</td>
    <td class="val" width="38%"><?= htmlspecialchars($quotation['quotation_no'] ?? '') ?></td>
  </tr>
  <tr>
    <td class="lbl">联系人：</td>
    <td class="val"><?= htmlspecialchars($customer['contact_name'] ?? '') ?></td>
    <td class="lbl">报价日期：</td>
    <td class="val"><?php $ts = $quotation['quotation_date'] ?? 0; echo $ts ? date('Y-m-d', $ts) : ''; ?></td>
  </tr>
  <tr>
    <td class="lbl">电话：</td>
    <td class="val"><?= htmlspecialchars($customer['contact_phone'] ?? $customer['phone'] ?? '') ?></td>
    <td class="lbl">有效期：</td>
    <td class="val"><?php $vd = (int)($quotation['valid_days'] ?? 30); echo $vd.'天'; if($ts) echo '（至'.date('Y-m-d', $ts+$vd*86400).'）'; ?></td>
  </tr>
  <tr>
    <td class="lbl">币种：</td>
    <td class="val"><?= htmlspecialchars($quotation['currency'] ?? 'CNY') ?></td>
    <td class="lbl">付款方式：</td>
    <td class="val">按合同约定</td>
  </tr>
</table>

<hr class="quote-cn-divider-thin">

<table class="quote-cn-items">
  <thead>
    <tr>
      <th width="5%">序号</th>
      <th width="25%">产品名称</th>
      <th width="14%">规格型号</th>
      <th width="7%">单位</th>
      <th width="9%">数量</th>
      <th width="14%">单价(<?= htmlspecialchars($currency_symbol) ?>)</th>
      <th width="14%">金额(<?= htmlspecialchars($currency_symbol) ?>)</th>
      <th width="12%">备注</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($items)): ?>
      <tr><td colspan="8" style="color:#999;padding:30px">暂无明细</td></tr>
    <?php else: $i = 0; foreach ($items as $item): $i++; ?>
      <tr>
        <td><?= $i ?></td>
        <td class="tl"><?= htmlspecialchars($item['product_name'] ?? '') ?></td>
        <td><?= htmlspecialchars($item['specification'] ?? '') ?></td>
        <td><?= htmlspecialchars($item['unit'] ?? '') ?></td>
        <td class="tr"><?= number_format((float)($item['quantity'] ?? 0), 2) ?></td>
        <td class="tr"><?= number_format((float)($item['unit_price'] ?? 0), 2) ?></td>
        <td class="tr"><?= number_format((float)($item['amount'] ?? 0), 2) ?></td>
        <td><?= htmlspecialchars($item['remark'] ?? '') ?></td>
      </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table>

<div class="quote-cn-summary">
  <table>
    <tr>
      <td class="lbl">合计金额（<?= htmlspecialchars($currency_symbol) ?>）：</td>
      <td class="val"><?= number_format((float)($quotation['total_amount'] ?? 0), 2) ?></td>
    </tr>
    <?php if ((float)($quotation['discount_amount'] ?? 0) > 0): ?>
    <tr>
      <td class="lbl">折扣金额（<?= htmlspecialchars($currency_symbol) ?>）：</td>
      <td class="val">-<?= number_format((float)($quotation['discount_amount'] ?? 0), 2) ?></td>
    </tr>
    <?php endif; ?>
    <?php if ((float)($quotation['tax_amount'] ?? 0) > 0): ?>
    <tr>
      <td class="lbl">增值税（<?= (float)($quotation['tax_rate'] ?? 0) ?>%）：</td>
      <td class="val"><?= number_format((float)($quotation['tax_amount'] ?? 0), 2) ?></td>
    </tr>
    <?php endif; ?>
    <tr class="total">
      <td class="lbl">总计（<?= htmlspecialchars($currency_symbol) ?>）：</td>
      <td class="val"><?= number_format((float)($quotation['final_amount'] ?? $quotation['total_amount'] ?? 0), 2) ?></td>
    </tr>
  </table>
</div>

<div class="quote-cn-cn">
  <span class="lbl">大写金额：</span><?= htmlspecialchars($cn_amount) ?>
</div>

<?php if (!empty($company['terms_text'])): ?>
<div class="quote-cn-terms">
  <h4>条款与条件：</h4>
  <pre><?= htmlspecialchars($company['terms_text']) ?></pre>
</div>
<?php endif; ?>

<?php if (!empty($company['bank_name']) || !empty($company['bank_account'])): ?>
<div class="quote-cn-terms" style="margin-top:8px">
  <h4>收款账户：</h4>
  <pre>开户行：<?= htmlspecialchars($company['bank_name'] ?? '') ?>  账号：<?= htmlspecialchars($company['bank_account'] ?? '') ?></pre>
</div>
<?php endif; ?>

<div class="quote-cn-stamp">
  <table>
    <tr>
      <td><strong>供方（盖章）</strong><div class="box"></div>日期：________年____月____日</td>
      <td><strong>需方（盖章）</strong><div class="box"></div>日期：________年____月____日</td>
    </tr>
  </table>
</div>

<?php if (!empty($company['footer_text'])): ?>
<div class="quote-cn-footer"><?= htmlspecialchars($company['footer_text']) ?></div>
<?php endif; ?>

</div><!-- .quote-cn-wrapper -->
