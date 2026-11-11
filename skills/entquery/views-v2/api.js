// 开源版占位：entquery skill 未开源
const empty = () => Promise.resolve({})
const emptyList = () => Promise.resolve({ data: { list: [], total: 0 } })

// 客户详情用 on === true 判断是否展示工商档案，这里固定 false
export const getStatus = () => Promise.resolve(false)
export const clearStatusCache = () => {}
export const searchCompany = emptyList
export const getCompanyDetail = empty
export const fillCompany = empty
export const verifyCompany = empty
export const getLogs = emptyList
export const getSetting = empty
export const saveSetting = empty
export const getWatchList = emptyList
export const saveWatch = empty
export const cancelWatch = empty
export const toggleWatch = empty
export const checkWatchNow = empty
export const batchSubscribeWatch = empty
export const getAlertList = emptyList
export const getAlertUnread = () => Promise.resolve(0)
export const markAlertRead = empty
export const markAllAlertsRead = empty
