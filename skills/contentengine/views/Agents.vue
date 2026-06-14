<template>
  <a-card :bordered="false">
    <a-tabs defaultActiveKey="presets">
      <a-tab-pane key="presets" tab="系统预设">
        <a-row :gutter="16">
          <a-col :md="8" v-for="a in presets" :key="a.preset_key">
            <a-card size="small" :title="a.name" style="margin-bottom:12px">
              <p><a-tag>{{ typeLabel[a.type] || a.type }}</a-tag> <a-tag color="blue">{{ a.preset_style }}</a-tag></p>
              <p style="color:#999;font-size:12px;min-height:24px">变量：{{ (a.allowed_vars||[]).join(', ') }}</p>
              <a-button type="primary" size="small" @click="copyPreset(a.preset_key)" :loading="copying===a.preset_key">复制到我的智能体</a-button>
            </a-card>
          </a-col>
        </a-row>
      </a-tab-pane>
      <a-tab-pane key="custom" tab="我的智能体">
        <a-button type="primary" icon="plus" @click="openAdd" style="margin-bottom:12px">新建智能体</a-button>
        <a-table :dataSource="custom" :loading="loading" rowKey="id" size="small" :pagination="false">
          <a-table-column title="名称" dataIndex="name" /><a-table-column title="类型" dataIndex="agent_type" width="80"><template slot-scope="t">{{ typeLabel[t]||t }}</template></a-table-column><a-table-column title="风格" dataIndex="preset_style" width="120" />
          <a-table-column title="操作" width="120">
            <template slot-scope="text,record"><a @click="openEdit(record)">编辑</a><a-divider type="vertical" /><a-popconfirm title="确认删除?" @confirm="handleDelete(record.id)"><a>删除</a></a-popconfirm></template>
          </a-table-column>
        </a-table>
      </a-tab-pane>
    </a-tabs>

    <a-modal :visible="modalVisible" :title="editingId?'编辑智能体':'新建智能体'" @ok="handleSave" @cancel="modalVisible=false" :confirmLoading="saving" width="700" wrapClassName="agent-modal">
      <a-form :form="editForm" :labelCol="{span:5}" :wrapperCol="{span:19}">
        <a-form-item label="名称"><a-input v-decorator="['name',{rules:[{required:true}]}]" /></a-form-item>
        <a-form-item label="类型"><a-select v-decorator="['agent_type',{initialValue:'content_writer'}]"><a-select-option value="topic_scanner">选题顾问</a-select-option><a-select-option value="content_writer">长文作者</a-select-option><a-select-option value="video_writer">视频脚本</a-select-option><a-select-option value="social_writer">朋友圈</a-select-option><a-select-option value="seo_writer">SEO专家</a-select-option></a-select></a-form-item>
        <a-form-item label="引用风格"><a-select v-decorator="['preset_style']" placeholder="选择风格" allowClear><a-select-option v-for="s in styleList" :key="s.preset_key||s.id" :value="s.preset_key||String(s.id)">{{ s.name }}<span style="color:#999;font-size:12px;margin-left:8px">{{ s.tone||'' }}</span></a-select-option></a-select></a-form-item>
        <a-form-item label="可用变量">
          <a-checkbox-group v-decorator="['allowed_vars']">
            <a-checkbox value="company_intro">公司简介</a-checkbox><a-checkbox value="core_strengths">核心优势</a-checkbox>
            <a-checkbox value="keywords">关键词库</a-checkbox><a-checkbox value="solutions">方案库</a-checkbox>
            <a-checkbox value="products">产品列表</a-checkbox><a-checkbox value="topic_title">选题标题</a-checkbox>
            <a-checkbox value="topic_angle">切入角度</a-checkbox>
          </a-checkbox-group>
        </a-form-item>
        <a-form-item label="附加指令"><a-textarea v-decorator="['instructions']" :rows="3" placeholder="选填，会追加到system prompt末尾" /></a-form-item>
      </a-form>
    </a-modal>
  </a-card>
</template>
<script>
import { getAgents, copyAgentPreset, addAgent, editAgent, deleteAgent, getStyles } from '@skills/contentengine/views/content'
export default { name:'ContentAgents', data(){ return { loading:false,saving:false,copying:'',presets:[],custom:[],styleList:[],modalVisible:false,editingId:0,editForm:this.$form.createForm(this,{name:'agentForm'}),typeLabel:{topic_scanner:'选题',content_writer:'长文',video_writer:'视频',social_writer:'朋友圈',seo_writer:'SEO'} } }, created(){this.loadData();this.loadStyles()}, methods:{ loadStyles(){ getStyles().then(res=>{ const d=res.data||{};this.styleList=[...(d.presets||[]),...(d.custom||[])] }) }, loadData(){ this.loading=true;getAgents().then(res=>{ const d=res.data||{};this.presets=d.presets||[];this.custom=d.custom||[] }).finally(()=>{this.loading=false}) }, copyPreset(key){ this.copying=key;copyAgentPreset(key).then(()=>{this.$message.success('复制成功');this.loadData();this.copying=''}).catch(()=>{this.copying=''}) }, openAdd(){ this.editingId=0;this.modalVisible=true;this.$nextTick(()=>this.editForm.resetFields()) }, openEdit(record){ this.editingId=record.id;this.modalVisible=true;this.$nextTick(()=>{this.editForm.setFieldsValue({...record,allowed_vars:Array.isArray(record.allowed_vars)?record.allowed_vars:[]})}) }, handleSave(){ this.editForm.validateFields((err,values)=>{ if(err)return;this.saving=true;const fn=this.editingId?editAgent:addAgent;if(this.editingId)values.id=this.editingId;fn(values).then(()=>{this.$message.success('保存成功');this.modalVisible=false;this.loadData()}).finally(()=>{this.saving=false}) }) }, handleDelete(id){ deleteAgent(id).then(()=>{this.$message.success('已删除');this.loadData()}) } } }
</script>
<style>.agent-modal .ant-modal{width:700px!important}</style>
