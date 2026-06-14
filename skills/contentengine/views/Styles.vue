<template>
  <a-card :bordered="false">
    <a-tabs defaultActiveKey="presets">
      <a-tab-pane key="presets" tab="系统预设">
        <a-row :gutter="16">
          <a-col :md="8" v-for="s in presets" :key="s.preset_key">
            <a-card size="small" :title="s.name" style="margin-bottom:12px">
              <p style="color:#999;font-size:12px;min-height:36px">{{ s.role_desc }}</p>
              <p><a-tag>{{ s.tone }}</a-tag></p>
              <a-button type="primary" size="small" @click="copyPreset(s.preset_key)" :loading="copying===s.preset_key">复制到我的风格</a-button>
            </a-card>
          </a-col>
        </a-row>
      </a-tab-pane>
      <a-tab-pane key="custom" tab="我的风格">
        <a-button type="primary" icon="plus" @click="openAdd" style="margin-bottom:12px">新建风格</a-button>
        <a-table :dataSource="custom" :loading="loading" rowKey="id" size="small" :pagination="false">
          <a-table-column title="名称" dataIndex="name" /><a-table-column title="语气" dataIndex="tone" width="80" />
          <a-table-column title="操作" width="120">
            <template slot-scope="text,record"><a @click="openEdit(record)">编辑</a><a-divider type="vertical" /><a-popconfirm title="确认删除?" @confirm="handleDelete(record.id)"><a>删除</a></a-popconfirm></template>
          </a-table-column>
        </a-table>
      </a-tab-pane>
    </a-tabs>

    <a-modal :visible="modalVisible" :title="editingId?'编辑风格':'新建风格'" @ok="handleSave" @cancel="modalVisible=false" :confirmLoading="saving" width="700">
      <a-form :form="editForm" :labelCol="{span:5}" :wrapperCol="{span:19}">
        <a-form-item label="名称"><a-input v-decorator="['name',{rules:[{required:true}]}]" /></a-form-item>
        <a-form-item label="人设"><a-input v-decorator="['role_desc']" placeholder="如：行业技术顾问" /></a-form-item>
        <a-form-item label="语气"><a-select v-decorator="['tone',{initialValue:'专业'}]"><a-select-option value="专业">专业</a-select-option><a-select-option value="亲切">亲切</a-select-option><a-select-option value="犀利">犀利</a-select-option><a-select-option value="温和">温和</a-select-option></a-select></a-form-item>
        <a-form-item label="System Prompt"><a-textarea v-decorator="['system_prompt']" :rows="6" placeholder="支持{变量}占位符" /></a-form-item>
        <a-form-item label="禁用词"><a-textarea v-decorator="['forbidden_words']" :rows="2" placeholder="逗号分隔" /></a-form-item>
      </a-form>
    </a-modal>
  </a-card>
</template>
<script>
import { getStyles, copyStylePreset, addStyle, editStyle, deleteStyle } from '@skills/contentengine/views/content'
export default { name:'ContentStyles', data(){ return { loading:false,saving:false,copying:'',presets:[],custom:[],modalVisible:false,editingId:0,editForm:this.$form.createForm(this,{name:'styleForm'}) } }, created(){this.loadData()}, methods:{ loadData(){ this.loading=true;getStyles().then(res=>{ const d=res.data||{};this.presets=d.presets||[];this.custom=d.custom||[] }).finally(()=>{this.loading=false}) }, copyPreset(key){ this.copying=key;copyStylePreset(key).then(()=>{this.$message.success('复制成功');this.loadData();this.copying=''}).catch(()=>{this.copying=''}) }, openAdd(){ this.editingId=0;this.modalVisible=true;this.$nextTick(()=>this.editForm.resetFields()) }, openEdit(record){ this.editingId=record.id;this.modalVisible=true;this.$nextTick(()=>this.editForm.setFieldsValue(record)) }, handleSave(){ this.editForm.validateFields((err,values)=>{ if(err)return;this.saving=true;const fn=this.editingId?editStyle:addStyle;if(this.editingId)values.id=this.editingId;fn(values).then(()=>{this.$message.success('保存成功');this.modalVisible=false;this.loadData()}).finally(()=>{this.saving=false}) }) }, handleDelete(id){ deleteStyle(id).then(()=>{this.$message.success('已删除');this.loadData()}) } } }
</script>
