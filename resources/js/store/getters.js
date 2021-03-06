const getters = {
    language: state => state.app.language,
    userId: state => state.user.profile.id,
    name: state => state.user.profile.name,
    token: state => state.user.access_token,
    profile: state => state.user.profile,
    roles: state => state.user.profile.roles,
    permissions: state => state.user.profile.permissions,
    permissionRoutes: state => state.permissions.routes,
    addRoutes: state => state.permissions.addRoutes,
    expToken: state => state.user.profile.expToken,
    department_id: state => state.department.department_id,
    department_name: state => state.department.department_name,
    listDepartment: state => state.department.listDepartment,
    current_year: state => state.time.current_year,
    current_year_month: state => state.time.current_year_month,
    listYear: state => state.time.listYear,
    listYearMonth: state => state.time.listYearMonth,
    curPageAccessoryList: state => state.accessory.cur_page,
    perPageAccessoryList: state => state.accessory.per_page,
    curPageUserList: state => state.userManagement.cur_page,
    perPageUserList: state => state.userManagement.per_page,
    curPageMaintenanceCostList: state => state.maintenanceCost.cur_page,
    perPageMaintenanceCostList: state => state.maintenanceCost.per_page,
    isScheduleAndResultTable: state => state.maintenanceScheduleResults.isScheduleAndResultTable,
    noNumberPlateFilter: state => state.maintenanceCost.no_number_plate_filter,
    maintenanceScheduleDateFilter: state => state.maintenanceCost.maintenance_schedule_date_filter,
    maintenanceDateFilter: state => state.maintenanceCost.maintenance_date_filter,
    statusFilter: state => state.maintenanceCost.status_filter,
    maintenanceTypeFilter: state => state.maintenanceCost.maintenance_type_filter,
    garageFilter: state => state.maintenanceCost.garage_filter,
};

export default getters;
