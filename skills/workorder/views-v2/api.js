// 开源版占位：工单 skill 未开源
//
// CRM 客户详情 / 项目详情会引用这里的少量接口。开源发行版不包含工单业务，
// 这里只保证导出符号存在、调用不会把页面打挂：
//   - 列表类返回空数组
//   - 写操作 resolve 空对象，由工单弹窗（TicketForm 占位组件）提示「未包含在开源版」
//
// 完整能力请使用闭源 skill：skills/workorder。

const empty = () => Promise.resolve({})
const emptyList = () => Promise.resolve({ data: { list: { data: [], total: 0 } } })

export const getList = emptyList
export const getDetail = empty
export const add = empty
export const edit = empty
export const deleteById = empty
export const assign = empty
export const changeStatus = empty
export const addItem = empty
export const removeItem = empty
export const reviewItem = empty
export const reply = empty
export const deleteFile = empty
export const uploadFile = empty
export const attachFile = empty
export const getTypeList = emptyList
export const addType = empty
export const editType = empty
export const deleteType = empty
export const setTypeStatus = empty
export const suggestTypeMaterials = empty
export const getTypePresets = empty
export const applyTypePreset = empty
export const getMaterialList = emptyList
export const addMaterial = empty
export const editMaterial = empty
export const deleteMaterial = empty
export const setMaterialStatus = empty
