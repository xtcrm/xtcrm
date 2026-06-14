<template>
  <a-card :bordered="false">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <a-radio-group v-model="filterType" @change="loadList" buttonStyle="solid" size="small">
        <a-radio-button :value="0">全部</a-radio-button><a-radio-button :value="1">核心词</a-radio-button><a-radio-button :value="2">长尾词</a-radio-button><a-radio-button :value="3">行业词</a-radio-button><a-radio-button :value="4">地域词</a-radio-button>
      </a-radio-group>
      <div><a-button type="primary" icon="plus" @click="showAdd=true" style="margin-right:8px">添加关键词</a-button><a-button icon="import" @click="showImport=true">批量导入</a-button></div>
    </div>
    <a-table :dataSource="list" :loading="loading" :pagination="pagination" @change="onPageChange" rowKey="id" size="small">
      <a-table-column title="关键词" dataIndex="keyword" /><a-table-column title="类型" dataIndex="type" width="100"><template slot-scope="text">{{ ['','核心词','长尾词','行业词','地域词'][text] }}</template></a-table-column>
      <a-table-column title="搜索量" dataIndex="search_volume" width="80" /><a-table-column title="难度" dataIndex="difficulty" width="60" /><a-table-column title="分组" dataIndex="group_tag" width="100" />
      <a-table-column title="操作" width="80"><template slot-scope="text,record"><a-popconfirm title="确认删除?" @confirm="handleDelete(record.id)"><a>删除</a></a-popconfirm></template></a-table-column>
    </a-table>
    <a-modal :visible="showAdd" title="添加关键词" @ok="handleAdd" @cancel="showAdd=false" :confirmLoading="saving">
      <a-form :form="addForm"><a-form-item label="关键词"><a-input v-decorator="['keyword',{rules:[{required:true,message:'请输入'}]}]" /></a-form-item>
        <a-form-item label="类型"><a-select v-decorator="['type',{initialValue:2}]"><a-select-option :value="1">核心词</a-select-option><a-select-option :value="2">长尾词</a-select-option><a-select-option :value="3">行业词</a-select-option><a-select-option :value="4">地域词</a-select-option></a-select></a-form-item>
        <a-form-item label="分组"><a-input v-decorator="['group_tag']" placeholder="如：UV油墨" /></a-form-item></a-form></a-modal>
    <a-modal :visible="showImport" title="批量导入关键词" @ok="handleImport" @cancel="showImport=false" :confirmLoading="saving">
      <a-form-item label="类型"><a-select v-model="importType" style="width:120px"><a-select-option :value="2">长尾词</a-select-option><a-select-option :value="1">核心词</a-select-option><a-select-option :value="3">行业词</a-select-option><a-select-option :value="4">地域词</a-select-option></a-select></a-form-item>
      <a-textarea v-model="importText" :rows="10" placeholder="每行一个关键词" /></a-modal>
  </a-card>
</template>
<script>
import { getKeywords, addKeyword, deleteKeyword, batchImportKeywords } from '@skills/contentengine/views/content'
export default { name:'ContentKeywords', data(){ return { loading:false,saving:false,filterType:0,importType:2,importText:'',list:[],pagination:{current:1,pageSize:20,total:0},showAdd:false,showImport:false,addForm:this.$form.createForm(this,{name:'keywordAdd'}) } }, created(){this.loadList()}, methods:{ loadList(page=1){ this.loading=true;const params={page,list_rows:this.pagination.pageSize};if(this.filterType>0)params.type=this.filterType;getKeywords(params).then(res=>{ const d=res.data?.list||{};this.list=d.data||[];this.pagination.total=d.total||0;this.pagination.current=d.current_page||page }).finally(()=>{this.loading=false}) }, onPageChange(p){this.loadList(p.current)}, handleAdd(){this.addForm.validateFields((err,values)=>{ if(err)return;this.saving=true;addKeyword(values).then(()=>{this.$message.success('添加成功');this.showAdd=false;this.addForm.resetFields();this.loadList()}).finally(()=>{this.saving=false}) }) }, handleDelete(id){deleteKeyword(id).then(()=>{this.$message.success('已删除');this.loadList()})}, handleImport(){ if(!this.importText.trim())return this.$message.warning('请输入关键词');this.saving=true;batchImportKeywords(this.importText,this.importType).then(res=>{this.$message.success(res.message||'导入完成');this.showImport=false;this.importText='';this.loadList()}).finally(()=>{this.saving=false}) } } }
</script>
