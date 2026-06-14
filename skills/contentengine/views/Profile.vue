<template>
  <a-card :bordered="false"><a-page-header title="公司档案" sub-title="AI内容生成的人设基础" /><a-divider /><a-spin :spinning="loading">
    <a-form :form="form" @submit="handleSubmit">
      <a-form-item label="公司简介" :labelCol="{span:3}" :wrapperCol="{span:18}"><a-textarea v-decorator="['company_intro']" :rows="3" placeholder="200-500字" /></a-form-item>
      <a-form-item label="核心优势" :labelCol="{span:3}" :wrapperCol="{span:18}">
        <div v-for="(s,idx) in strengths" :key="idx" style="display:flex;gap:8px;margin-bottom:8px">
          <a-input v-model="s.value" :placeholder="'第'+(idx+1)+'条优势'" style="flex:1" />
          <a-button type="danger" icon="minus" size="small" @click="removeStrength(idx)" :disabled="strengths.length<=1" />
        </div><a-button type="dashed" icon="plus" size="small" @click="addStrength">添加优势</a-button>
      </a-form-item>
      <a-form-item label="技术实力" :labelCol="{span:3}" :wrapperCol="{span:18}"><a-textarea v-decorator="['tech_capability']" :rows="3" placeholder="技术资质、研发团队、专利等" /></a-form-item>
      <a-form-item label="服务承诺" :labelCol="{span:3}" :wrapperCol="{span:18}"><a-textarea v-decorator="['service_commitment']" :rows="2" placeholder="售后保障、交付周期等" /></a-form-item>
      <a-form-item label="联系信息" :labelCol="{span:3}" :wrapperCol="{span:18}"><a-textarea v-decorator="['contact_info']" :rows="2" placeholder="电话、微信、地址" /></a-form-item>
      <a-form-item label="联系二维码" :labelCol="{span:3}" :wrapperCol="{span:18}" extra="公众号/微信二维码"><SelectImage :multiple="false" :defaultList="qrcodeDefaultList" @change="onQrcodeChange" :width="80" /></a-form-item>
      <a-form-item label="品牌调性" :labelCol="{span:3}" :wrapperCol="{span:18}"><a-radio-group v-decorator="['brand_voice',{initialValue:'专业'}]"><a-radio value="专业">专业</a-radio><a-radio value="亲切">亲切</a-radio><a-radio value="技术流">技术流</a-radio><a-radio value="营销型">营销型</a-radio></a-radio-group></a-form-item>
      <a-form-item label="典型案例" :labelCol="{span:3}" :wrapperCol="{span:18}">
        <div v-for="(item,idx) in cases" :key="idx" style="display:flex;gap:8px;margin-bottom:8px">
          <a-input v-model="item.name" placeholder="客户名" style="width:200px" /><a-input v-model="item.result" placeholder="成果描述" style="flex:1" />
          <a-button type="danger" icon="minus" size="small" @click="removeCase(idx)" :disabled="cases.length<=1" />
        </div><a-button type="dashed" icon="plus" size="small" @click="addCase">添加案例</a-button>
      </a-form-item>
      <a-form-item :wrapperCol="{span:18,offset:3}"><a-button type="primary" html-type="submit" :loading="saving">保存档案</a-button></a-form-item>
    </a-form>
  </a-spin></a-card>
</template>
<script>
import { getProfile, saveProfile } from '@skills/contentengine/views/content'
import { SelectImage } from '@/components'
export default { name:'ContentProfile', components:{SelectImage}, data(){ return { loading:false,saving:false,form:this.$form.createForm(this,{name:'profile'}),strengths:[{value:''}],cases:[{name:'',result:''}],qrcodeDefaultList:[] } }, created(){this.loadProfile()}, methods:{ loadProfile(){ this.loading=true;getProfile().then(res=>{ const d=res.data?.detail||{}; if(d.core_strengths){ const lines=d.core_strengths.split('\n').filter(l=>l.trim()); if(lines.length) this.strengths=lines.map(l=>({value:l})) } if(d.case_stories){ try{ const p=JSON.parse(d.case_stories); if(Array.isArray(p)&&p.length) this.cases=p }catch(e){} } if(d.contact_qrcode){ const base=(window.publicConfig?.BASE_API||'').replace(/\/index\.php.*$/,'')+'/uploads/'; this.qrcodeDefaultList=[{file_id:0,file_path:d.contact_qrcode,preview_url:base+d.contact_qrcode}] } this.$nextTick(()=>{this.form.setFieldsValue(d)}) }).finally(()=>{this.loading=false}) }, addStrength(){this.strengths.push({value:''})}, removeStrength(idx){if(this.strengths.length>1)this.strengths.splice(idx,1)}, addCase(){this.cases.push({name:'',result:''})}, removeCase(idx){if(this.cases.length>1)this.cases.splice(idx,1)}, onQrcodeChange(fileId,selectedItems){ const fp=(selectedItems&&selectedItems.length)?(selectedItems[0].file_path||''):''; this.qrcodeDefaultList=fp?[{file_id:fileId,file_path:fp,preview_url:''}]:[]; const vals=this.form.getFieldsValue();vals.contact_qrcode=fp;saveProfile(vals).then(()=>this.$message.success('二维码已更新')) }, handleSubmit(e){ e.preventDefault();this.form.validateFields((err,values)=>{ if(err)return; const vs=this.strengths.filter(s=>s.value.trim());values.core_strengths=vs.map(s=>s.value).join('\n'); const vc=this.cases.filter(c=>c.name.trim());values.case_stories=vc.length?JSON.stringify(vc):''; this.saving=true;saveProfile(values).then(()=>{this.$message.success('保存成功')}).finally(()=>{this.saving=false}) }) } } }
</script>
