<template>
  <a-card :bordered="false">
    <div style="display:flex;justify-content:space-between;margin-bottom:16px"><a-input-search v-model="keyword" placeholder="搜索方案" @search="loadList" style="width:300px" /><a-button type="primary" icon="plus" @click="openAdd">新增方案</a-button></div>
    <a-table :dataSource="list" :loading="loading" :pagination="pagination" @change="onPageChange" rowKey="id" size="small">
      <a-table-column title="方案名称" dataIndex="title" /><a-table-column title="适用行业" dataIndex="target_industry" width="120" /><a-table-column title="排序" dataIndex="sort_order" width="60" />
      <a-table-column title="操作" width="120"><template slot-scope="text,record"><a @click="openEdit(record)">编辑</a><a-divider type="vertical" /><a-popconfirm title="确认删除?" @confirm="handleDelete(record.id)"><a>删除</a></a-popconfirm></template></a-table-column>
    </a-table>
    <a-modal :visible="modalVisible" :title="editingId?'编辑方案':'新增方案'" @ok="handleSave" @cancel="modalVisible=false" :confirmLoading="saving" width="640" wrapClassName="solution-modal">
      <a-form :form="editForm" :labelCol="{span:5}" :wrapperCol="{span:19}">
        <a-form-item label="方案名称"><a-input v-decorator="['title',{rules:[{required:true}]}]" /></a-form-item>
        <a-form-item label="适用行业"><a-input v-decorator="['target_industry']" placeholder="如：PCB制造" /></a-form-item>
        <a-form-item label="客户痛点"><a-textarea v-decorator="['customer_pain_points']" :rows="3" /></a-form-item>
        <a-form-item label="技术优势"><a-textarea v-decorator="['advantages']" :rows="3" /></a-form-item>
        <a-form-item label="对应产品">
          <div v-for="(p,idx) in products" :key="idx" style="display:flex;gap:6px;margin-bottom:6px"><a-input v-model="p.name" placeholder="产品名称" style="flex:1" /><a-button type="danger" icon="minus" size="small" @click="removeProduct(idx)" :disabled="products.length<=1" /></div>
          <a-button type="dashed" icon="plus" size="small" @click="addProduct">添加产品</a-button>
        </a-form-item>
        <a-form-item label="客户案例">
          <div v-for="(c,idx) in solutionCases" :key="idx" style="display:flex;gap:6px;margin-bottom:6px"><a-input v-model="c.name" placeholder="客户名" style="width:160px" /><a-input v-model="c.result" placeholder="成果" style="flex:1" /><a-button type="danger" icon="minus" size="small" @click="removeCase(idx)" :disabled="solutionCases.length<=1" /></div>
          <a-button type="dashed" icon="plus" size="small" @click="addCase">添加案例</a-button>
        </a-form-item>
        <a-form-item label="排序"><a-input-number v-decorator="['sort_order',{initialValue:0}]" :min="0" :max="999" /></a-form-item>
      </a-form>
    </a-modal>
  </a-card>
</template>
<script>
import { getSolutions, getSolutionDetail, addSolution, editSolution, deleteSolution } from '@skills/contentengine/views/content'
export default { name:'ContentSolutions', data(){ return { loading:false,saving:false,keyword:'',modalVisible:false,editingId:0,list:[],pagination:{current:1,pageSize:20,total:0},editForm:this.$form.createForm(this,{name:'solutionForm'}),products:[{name:''}],solutionCases:[{name:'',result:''}] } }, created(){this.loadList()}, methods:{ loadList(page=1){ this.loading=true;const params={page,list_rows:this.pagination.pageSize};if(this.keyword)params.keyword=this.keyword;getSolutions(params).then(res=>{ const d=res.data?.list||{};this.list=d.data||[];this.pagination.total=d.total||0;this.pagination.current=d.current_page||page }).finally(()=>{this.loading=false}) }, onPageChange(p){this.loadList(p.current)}, openAdd(){this.editingId=0;this.products=[{name:''}];this.solutionCases=[{name:'',result:''}];this.modalVisible=true;this.$nextTick(()=>{this.editForm.resetFields()})}, openEdit(record){ this.editingId=record.id;this.modalVisible=true;getSolutionDetail(record.id).then(res=>{ const d=res.data?.detail||{};try{if(d.products){const p=JSON.parse(d.products);if(Array.isArray(p)&&p.length)this.products=p}}catch(e){};try{if(d.customer_cases){const c=JSON.parse(d.customer_cases);if(Array.isArray(c)&&c.length)this.solutionCases=c}}catch(e){};this.$nextTick(()=>{this.editForm.setFieldsValue(d)}) }) }, addProduct(){this.products.push({name:''})}, removeProduct(idx){if(this.products.length>1)this.products.splice(idx,1)}, addCase(){this.solutionCases.push({name:'',result:''})}, removeCase(idx){if(this.solutionCases.length>1)this.solutionCases.splice(idx,1)}, handleSave(){ this.editForm.validateFields((err,values)=>{ if(err)return;const vp=this.products.filter(p=>p.name.trim());const vc=this.solutionCases.filter(c=>c.name.trim());values.products=vp.length?JSON.stringify(vp):'';values.customer_cases=vc.length?JSON.stringify(vc):'';this.saving=true;const fn=this.editingId?editSolution:addSolution;if(this.editingId)values.id=this.editingId;fn(values).then(()=>{this.$message.success('保存成功');this.modalVisible=false;this.loadList()}).finally(()=>{this.saving=false}) }) }, handleDelete(id){deleteSolution(id).then(()=>{this.$message.success('已删除');this.loadList()})} } }
</script>
<style>.solution-modal .ant-modal { width: 640px !important; }</style>
