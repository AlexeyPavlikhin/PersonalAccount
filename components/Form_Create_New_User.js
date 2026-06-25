export default {
    COURSE_PERMISSION_CODE: "courses",
    COURSE_PERMISSION_GROUP: "Доступ к учебным курсам",
    REPORT_PERMISSION_CODE: "reports",
    data() {
        return {
                    user_login: "",
                    user_username: "",
                    user_email: "",
                    user_user_group: "",
                    WarningMessage: "",
                    is_user_login_entered: false,
                    is_user_login_no_dublicate: false,
                    is_user_login_formate_correct: false,
                    is_user_username_ready: false,
                    is_user_email_entered: false,
                    is_user_email_formate_correct: false,
                    is_user_email_no_dublicate: false,
                    is_user_email_ready: false,
                    is_user_user_group_ready: false,

                    all_permissions: [],
                    assigned_permissions: [],
                    all_courses: [],
                    assigned_courses: [],
                    all_reports: [],
                    assigned_reports: [],
                    new_permission_id: "",
                    new_course_id: "",
                    new_report_id: "",
                    is_permissions_loading: false,
                    permissions_error_message: ""
        }
    },
    methods: {
                normalizeDateOnly(in_date){
                    if (!in_date || typeof in_date !== "string"){
                        return "";
                    }
                    return in_date.slice(0, 10);
                },

                getDefaultCourseDate() {
                    const dt = new Date();
                    dt.setDate(dt.getDate() + 30);
                    return dt.toISOString().slice(0, 10);
                },

                getCalculatedCourseAvailableUntil(in_course){
                    const period_in_days = parseInt(in_course && in_course.period_in_days ? in_course.period_in_days : 0);
                    if (period_in_days <= 0){
                        return this.getDefaultCourseDate();
                    }

                    const today = new Date();
                    today.setHours(0, 0, 0, 0);

                    let base_date = new Date(today.getTime());
                    const start_date_str = this.normalizeDateOnly(in_course && in_course.start_date ? in_course.start_date : "");
                    if (start_date_str.length > 0){
                        const start_date = new Date(start_date_str + "T00:00:00");
                        if (!Number.isNaN(start_date.getTime()) && start_date.getTime() > today.getTime()){
                            base_date = start_date;
                        }
                    }

                    base_date.setDate(base_date.getDate() + period_in_days);
                    return base_date.toISOString().slice(0, 10);
                },

                normalizePermissions(in_permissions){
                    const map_by_id = {};
                    for (const item of in_permissions || []) {
                        const permition_id = parseInt(item.permition_id);
                        if (!permition_id || map_by_id[permition_id]) {
                            continue;
                        }
                        map_by_id[permition_id] = {
                            permition_id: permition_id,
                            permition_name: item.permition_name || '',
                            menu_item_name: item.menu_item_name || item.permition_name || '',
                            permition_group: item.permition_group || '',
                            deadline: this.normalizeDateOnly(item.deadline || '2099-12-31')
                        };
                    }
                    return Object.values(map_by_id).sort((a, b) => a.permition_id - b.permition_id);
                },

                normalizeCourses(in_courses){
                    const map_by_id = {};
                    for (const item of in_courses || []) {
                        const course_id = parseInt(item.course_id);
                        if (!course_id) {
                            continue;
                        }
                        map_by_id[course_id] = {
                            course_id: course_id,
                            course_name: item.course_name || '',
                            period_in_days: parseInt(item.period_in_days || 0),
                            start_date: this.normalizeDateOnly(item.start_date || ''),
                            available_until: item.available_until || this.getDefaultCourseDate()
                        };
                    }
                    return Object.values(map_by_id).sort((a, b) => a.course_id - b.course_id);
                },

                normalizeReports(in_reports){
                    const map_by_id = {};
                    for (const item of in_reports || []) {
                        const report_id = parseInt(item.report_id);
                        if (!report_id) {
                            continue;
                        }
                        map_by_id[report_id] = {
                            report_id: report_id,
                            report_code: item.report_code || '',
                            report_name: item.report_name || ''
                        };
                    }
                    return Object.values(map_by_id).sort((a, b) => a.report_id - b.report_id);
                },

                getAvailableReports(){
                    const assigned_ids = new Set(this.normalizeReports(this.assigned_reports).map(item => item.report_id));
                    return this.normalizeReports(this.all_reports).filter(item => !assigned_ids.has(item.report_id));
                },

                getReportOptionLabel(in_report){
                    return in_report.report_name || in_report.report_code || '';
                },

                findReportByInput(in_input){
                    const input_value = (in_input || '').trim().toLowerCase();
                    if (input_value.length === 0){
                        return null;
                    }
                    const available_reports = this.getAvailableReports();
                    let source_item = available_reports.find(
                        (item) => String(item.report_id) === input_value
                    );
                    if (source_item){
                        return source_item;
                    }
                    return available_reports.find((item) => {
                        const option_label = this.getReportOptionLabel(item).toLowerCase();
                        return option_label.includes(input_value);
                    }) || null;
                },

                hasReportPermission(){
                    return (this.assigned_permissions || []).some(
                        (item) => item.permition_name === this.$options.REPORT_PERMISSION_CODE
                    );
                },

                clearReportTreeAccess(){
                    this.assigned_reports = [];
                },

                ensureReportsCatalogLoaded(){
                    if ((this.all_reports || []).length > 0 || !this.user_user_group) {
                        return Promise.resolve();
                    }
                    return axios.get('./queries/get_permissions_catalog_for_group.php', {
                        params: { user_group: this.user_user_group }
                    })
                    .then((response) => {
                        if (response.data && !response.data.error) {
                            this.all_reports = this.normalizeReports(response.data.all_reports || []);
                        }
                    })
                    .catch((error) => {
                        console.error(error);
                    });
                },

                getAvailablePermissions(){
                    const assigned_ids = new Set((this.assigned_permissions || []).map(item => parseInt(item && item.permition_id ? item.permition_id : 0)));
                    return this.normalizePermissions(this.all_permissions).filter((item) => {
                        if (assigned_ids.has(item.permition_id)) {
                            return false;
                        }
                        if (!this.hasCoursePermission() && this.isCourseChildPermission(item)) {
                            return false;
                        }
                        return true;
                    });
                },

                getPermissionOptionLabel(in_permission){
                    return (in_permission.permition_group || '') + ' / ' + (in_permission.menu_item_name || '');
                },

                findPermissionByInput(in_input_value){
                    const available_permissions = this.getAvailablePermissions();
                    const input_value = (in_input_value || '').trim().toLowerCase();
                    if (input_value.length === 0){
                        return null;
                    }

                    let source_item = available_permissions.find(
                        (item) => String(item.permition_id) === input_value
                    );
                    if (source_item){
                        return source_item;
                    }

                    source_item = available_permissions.find((item) => {
                        const option_label = this.getPermissionOptionLabel(item).toLowerCase();
                        const menu_name = (item.menu_item_name || '').toLowerCase();
                        const code_name = (item.permition_name || '').toLowerCase();
                        return option_label === input_value || menu_name === input_value || code_name === input_value;
                    });
                    if (source_item){
                        return source_item;
                    }

                    return available_permissions.find((item) => {
                        const option_label = this.getPermissionOptionLabel(item).toLowerCase();
                        const menu_name = (item.menu_item_name || '').toLowerCase();
                        const code_name = (item.permition_name || '').toLowerCase();
                        return option_label.includes(input_value) || menu_name.includes(input_value) || code_name.includes(input_value);
                    }) || null;
                },

                getAvailableCourses(){
                    const assigned_ids = new Set(this.normalizeCourses(this.assigned_courses).map(item => item.course_id));
                    return this.normalizeCourses(this.all_courses).filter(item => !assigned_ids.has(item.course_id));
                },

                getCourseOptionLabel(in_course){
                    return in_course.course_name || '';
                },

                findCourseByInput(in_input_value){
                    const available_courses = this.getAvailableCourses();
                    const input_value = (in_input_value || '').trim().toLowerCase();
                    if (input_value.length === 0){
                        return null;
                    }

                    let source_item = available_courses.find(
                        (item) => String(item.course_id) === input_value
                    );
                    if (source_item){
                        return source_item;
                    }

                    source_item = available_courses.find((item) => {
                        const option_label = this.getCourseOptionLabel(item).toLowerCase();
                        return option_label === input_value;
                    });
                    if (source_item){
                        return source_item;
                    }

                    return available_courses.find((item) => {
                        const option_label = this.getCourseOptionLabel(item).toLowerCase();
                        return option_label.includes(input_value);
                    }) || null;
                },

                hasCoursePermission(){
                    return (this.assigned_permissions || []).some(
                        (item) => item.permition_name === this.$options.COURSE_PERMISSION_CODE
                    );
                },

                isCourseChildPermission(in_permission){
                    if (!in_permission){
                        return false;
                    }
                    if (in_permission.permition_name === this.$options.COURSE_PERMISSION_CODE){
                        return false;
                    }
                    return (in_permission.permition_group || "") === this.$options.COURSE_PERMISSION_GROUP;
                },

                clearCourseTreeAccess(){
                    this.assigned_permissions = this.normalizePermissions(this.assigned_permissions).filter(
                        (item) => !this.isCourseChildPermission(item)
                    );
                    this.assigned_courses = [];
                },

                getChildCoursePermissionsForView(){
                    return (this.assigned_permissions || []).filter((item) => this.isCourseChildPermission(item));
                },

                getTopLevelPermissionGroupsForView(){
                    const grouped = {};
                    const items = (this.assigned_permissions || []).slice().sort((a, b) => {
                        const a_id = parseInt(a && a.permition_id ? a.permition_id : 0);
                        const b_id = parseInt(b && b.permition_id ? b.permition_id : 0);
                        return a_id - b_id;
                    });
                    for (const item of items) {
                        if (this.isCourseChildPermission(item)) {
                            continue;
                        }
                        const group_name = item.permition_group && item.permition_group.length > 0 ? item.permition_group : 'Прочее';
                        if (!grouped[group_name]) {
                            grouped[group_name] = [];
                        }
                        grouped[group_name].push(item);
                    }

                    const result = [];
                    Object.keys(grouped).sort().forEach((group_name) => {
                        result.push({
                            group_name: group_name,
                            items: grouped[group_name]
                        });
                    });
                    return result;
                },

                getTopLevelPermissionGroupsForViewWithFallback(){
                    const groups = this.getTopLevelPermissionGroupsForView();
                    if (groups.length > 0){
                        return groups;
                    }
                    return [{
                        group_name: 'Назначенные права',
                        items: []
                    }];
                },

                loadPermissionsCatalogForGroup(){
                    if (!this.user_user_group || this.user_user_group.length === 0){
                        this.all_permissions = [];
                        this.assigned_permissions = [];
                        this.all_courses = [];
                        this.assigned_courses = [];
                        this.all_reports = [];
                        this.assigned_reports = [];
                        this.new_permission_id = "";
                        this.new_course_id = "";
                        this.new_report_id = "";
                        this.permissions_error_message = "";
                        this.is_permissions_loading = false;
                        this.checkForReady();
                        return;
                    }

                    this.is_permissions_loading = true;
                    this.permissions_error_message = "";
                    axios.get('./queries/get_permissions_catalog_for_group.php', {
                        params: {
                            user_group: this.user_user_group
                        }
                    })
                    .then((response) => {
                        if (!response.data || response.data.error) {
                            this.permissions_error_message = response.data && response.data.error ? response.data.error : "Ошибка загрузки полномочий";
                            this.all_permissions = [];
                            this.all_courses = [];
                            return;
                        }

                        this.all_permissions = this.normalizePermissions(response.data.all_permissions || []);
                        this.all_courses = this.normalizeCourses(response.data.all_courses || []);
                        this.all_reports = this.normalizeReports(response.data.all_reports || []);
                    })
                    .catch((error) => {
                        this.permissions_error_message = "Не удалось загрузить полномочия";
                        console.log(error);
                    })
                    .finally(() => {
                        this.is_permissions_loading = false;
                        this.checkForReady();
                    });
                },

                saveUserPermissions(){
                    return axios.post("./queries/update_user_permissions.php", {
                        user_login: this.user_login,
                        assigned_permissions: this.normalizePermissions(this.assigned_permissions),
                        assigned_courses: this.normalizeCourses(this.assigned_courses),
                        assigned_reports: this.normalizeReports(this.assigned_reports)
                    })
                    .then((response) => {
                        if (!response.data || response.data.status !== "ok"){
                            throw new Error(response.data && response.data.message ? response.data.message : "Не удалось сохранить полномочия");
                        }
                        return response;
                    });
                },

                init(){
                    this.user_login = "";
                    this.user_username = "";
                    this.user_email = "";
                    this.user_user_group = "";
                    this.WarningMessage = "";
                    this.is_user_login_entered = false;
                    this.is_user_login_no_dublicate = false;
                    this.is_user_login_formate_correct = false;
                    this.is_user_username_ready = false;
                    this.is_user_email_entered = false;
                    this.is_user_email_formate_correct = false;
                    this.is_user_email_no_dublicate = false;
                    this.is_user_email_ready = false;
                    this.is_user_user_group_ready = false;

                    this.permissions_error_message = "";
                    this.new_permission_id = "";
                    this.new_course_id = "";
                    this.all_permissions = [];
                    this.assigned_permissions = [];
                    this.all_courses = [];
                    this.assigned_courses = [];
                    this.is_permissions_loading = false;

                },                

                CloseForm(){
                    document.getElementById("id_FormCreateNewUser").style.display = "none";
                    document.body.style.overflow = '';
                },

                CreateNewUser(){
                    let this2 = this;
                    //запускаем спиннер    
                    document.getElementById("id_spinner_panel").style.display = "block";                     

                    axios.post("./queries/create_new_user.php", {user_login: this.user_login, user_username: this.user_username, user_email: this.user_email, user_user_group: this.user_user_group})
                    .then(function (response) {
                        let d = response.data;
                        if (typeof d === "string") {
                            try {
                                d = JSON.parse(d);
                            } catch (e) {
                                document.getElementById("id_spinner_panel").style.display = "none";
                                this2.$root.$refs.ref_FormModalMessage.init(this, "Ошибка при создании пользователя. Некорректный ответ сервера.<br>" + String(response.data));
                                document.getElementById("id_FormModalMessage").style.display = "block";
                                this2.$root.$refs.mainContent.get_users();
                                this2.CloseForm();
                                return;
                            }
                        }

                        // Ответ — JSON-объект (раньше был только rowCount как строка "1"); приводим поля к числам.
                        const ok = d != null && Number(d.ok) === 1;
                        const writeOk = Number(d.write_status) === 1;
                        const mailOk = Number(d.send_status) === 1;

                        if (ok) {
                            return this2.saveUserPermissions()
                                .then(() => ({ ok: true, d }))
                                .catch((permError) => ({ ok: true, d, permError }));
                        } else {
                            return { ok: false, d };
                        }
                    })
                    .then(function (result) {
                        document.getElementById("id_spinner_panel").style.display = "none";

                        if (result && result.ok) {
                            const d = result.d;
                            const writeOk = Number(d.write_status) === 1;
                            const mailOk = Number(d.send_status) === 1;
                            const permError = result.permError || null;

                            if (!writeOk) {
                                this2.$root.$refs.ref_FormModalMessage.init(this,
                                    "Учётная запись создана, но не удалось установить пароль в базе данных.<br>" +
                                    (d.write_error ? "Подробности: " + d.write_error : ""));
                                document.getElementById("id_FormModalMessage").style.display = "block";
                            } else if (!mailOk) {
                                this2.$root.$refs.ref_FormModalMessage.init(this,
                                    "Учётная запись создана.<br>" +
                                    "Для входа на платформу (логин — email): " + this2.user_email + "<br>" +
                                    "Пароль: " + d.new_pass + "<br><br>" +
                                    "При отправке приветственного письма на адрес " + this2.user_email + " произошла ошибка, письмо не доставлено — передайте данные для входа пользователю другим способом.<br><br>" +
                                    (d.send_error ? "Информация об ошибке:<br>" + d.send_error : ""));
                                document.getElementById("id_FormModalMessage").style.display = "block";
                            } else {
                                this2.$root.$refs.ref_FormModalMessage.init(this,
                                    "Учётная запись создана. Письмо с данными для входа отправлено на адрес электронной почты: " + this2.user_email + ".");
                                document.getElementById("id_FormModalMessage").style.display = "block";
                            }

                            if (permError) {
                                this2.$root.$refs.ref_FormModalMessage.init(this,
                                    "Учётная запись создана, но не удалось сохранить полномочия (разделы/курсы).<br>" +
                                    String(permError));
                                document.getElementById("id_FormModalMessage").style.display = "block";
                            }

                            this2.$root.$refs.mainContent.get_users();
                            this2.CloseForm();
                            return;
                        } 

                        const d = result && result.d !== undefined ? result.d : result;
                        let detail = "";
                        if (d && typeof d === "object") {
                            detail = d.error !== undefined ? String(d.error) : JSON.stringify(d);
                        } else {
                            detail = String(d);
                        }
                        this2.$root.$refs.ref_FormModalMessage.init(this,
                            "Не удалось создать пользователя.<br>" + detail);
                        document.getElementById("id_FormModalMessage").style.display = "block";
                        console.error("Ошибка создания пользователя:", d);

                        this2.$root.$refs.mainContent.get_users();
                        this2.CloseForm();
                    })
                    .catch(function (error) {
                        //останавливаем спиннер    
                        document.getElementById("id_spinner_panel").style.display = "none";

                        this2.$root.$refs.ref_FormModalMessage.init(this, "Что-то пошло не так при создании пользователя. Пользователь не создан.<br>" + error);
                        //показываем сообщение    
                        document.getElementById("id_FormModalMessage").style.display = "block";                            
                        console.error(error);
                        
                        // обноляем родительскую форму
                        this2.$root.$refs.mainContent.get_users();

                        //закрываем модальное окно
                        this2.CloseForm();

                    });
                },

                checkForReady(){
                    
                    if (this.is_user_login_entered && 
                        this.is_user_login_formate_correct &&
                        this.is_user_login_no_dublicate &&
                        this.is_user_username_ready && 
                        this.is_user_email_entered && 
                        this.is_user_email_formate_correct &&
                        this.is_user_email_no_dublicate &&
                        this.is_user_user_group_ready &&
                        !this.is_permissions_loading
                       ){
                        document.getElementById("buttonCreateNewUser").disabled = false;
                        //console.log("disabled = false");
                    }else{
                        document.getElementById("buttonCreateNewUser").disabled = true;
                        //console.log("disabled = true");
                    }

                    this.WarningMessage = "";
                    
                    if(!this.is_user_login_formate_correct && this.user_login.length > 0){
                        this.WarningMessage = "Login содержит Недопустимые символы. "
                    }

                    if(!this.is_user_login_no_dublicate && this.user_login.length > 0){
                        this.WarningMessage = "Такой Login уже зарегистрирован в системе. "
                    }

                    if(!this.is_user_email_no_dublicate && this.user_email.length > 0){
                        this.WarningMessage = "Такой E-mail уже зарегистрирован в системе. "
                    }
                },

                checkForDublicateLogin(in_user_login){
                    try {
                        axios.get('./queries/get_count_users_by_login.php', {
                            params: {
                                user_login: in_user_login
                            }
                        })
                        .then((response1) => {
                            //console.log(response.data)
                            if (response1.data) {
                                if (response1.data[0].count == '0'){
                                    this.is_user_login_no_dublicate = true;
                                } else {
                                    this.is_user_login_no_dublicate = false;
                                }
                                this.checkForReady();
                            } else {
                                console.log('Ответ от сервера пустой (data undefined/null)');
                            }
                        });
                    } catch (error) {
                        // Обработка ошибки
                        console.error('Ошибка при запросе:', error);
                        if (error.response) {
                            console.error('Статус ошибки:', error.response.status);
                            console.error('Данные ошибки:', error.response.data);
                        }
                    }
                },

                checkForDublicatEmail(in_user_email){
                    try {
                        axios.get('./queries/get_count_users_by_email.php', {
                            params: {
                                user_email: in_user_email
                            }
                        })
                        .then((response2) => {
                            //console.log(response.data)
                            if (response2.data) {
                                if (response2.data[0].count == '0'){
                                    this.is_user_email_no_dublicate = true;
                                } else {
                                    this.is_user_email_no_dublicate = false;
                                }
                                this.checkForReady();
                            } else {
                                console.log('Ответ от сервера пустой (data undefined/null)');
                            }
                        });
                    } catch (error) {
                        // Обработка ошибки
                        console.error('Ошибка при запросе:', error);
                        if (error.response) {
                            console.error('Статус ошибки:', error.response.status);
                            console.error('Данные ошибки:', error.response.data);
                        }
                    }
                },

                onClickCloseForm(){
                    this.CloseForm();
                },

                onClickCreateNewUser(){
                    this.CreateNewUser();
                },

                onClickCancel(){
                    this.CloseForm();
                },

                onChangeUserLogin(in_user_login){
                                     
                    //console.log("start onChangeUserLogin")  
                    
                    if(in_user_login.length > 0){
                        this.is_user_login_entered = true;
                        
                        //проверяем на дубликаты
                        this.checkForDublicateLogin(in_user_login);

                    } else {
                        this.is_user_login_entered = false;
                    }   

                    //проверяем формат
                    //let re = /^[a-zA-Z0-9]+$/;
                    let re = /^.*$/;
                    //let re = /^[a-zA-Z0-9@\\._-\\+]+$/;
                    //let re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                    //let re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                    if (re.test(String(in_user_login).toLowerCase())){
                        this.is_user_login_formate_correct = true;
                    } else {
                        this.is_user_login_formate_correct = false;
                    }
                    
                    //финальная проверка всех параметров
                    this.checkForReady();

                },

                onChangeUserName(in_user_username){
                    
                    if(in_user_username.length > 0){
                        this.is_user_username_ready = true;
                    } else {
                        this.is_user_username_ready = false;
                    }
                    this.checkForReady();
                },

                onChangeUserEmail(in_user_email){
                    //console.log("start onChangeUserEmail")  

                    if(in_user_email.length > 0){
                        this.is_user_email_entered = true;

                        //проверяем на дубликаты
                        this.checkForDublicatEmail(in_user_email);

                    } else {
                        this.is_user_email_entered = false;
                    }

                    //проверяем формат
                    let re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                    if (re.test(String(in_user_email).toLowerCase())){
                        this.is_user_email_formate_correct = true;
                    } else {
                        this.is_user_email_formate_correct = false;
                    }

                    
                    //финальная проверка всех параметров
                    this.checkForReady();                 
                },

                onChangeUserGroup(in_user_user_group){

                    if(in_user_user_group.length > 0){
                        this.is_user_user_group_ready = true;
                    } else {
                        this.is_user_user_group_ready = false;
                    }

                    // при смене группы сбрасываем назначения и перезагружаем каталог
                    this.assigned_permissions = [];
                    this.assigned_courses = [];
                    this.assigned_reports = [];
                    this.new_permission_id = "";
                    this.new_course_id = "";
                    this.new_report_id = "";
                    this.permissions_error_message = "";
                    this.loadPermissionsCatalogForGroup();
                    this.checkForReady();                 
                },

                onClickDeletePermission(in_permition_id){
                    this.assigned_permissions = this.normalizePermissions(this.assigned_permissions).filter(item => item.permition_id != in_permition_id);
                    if (!this.hasCoursePermission()){
                        this.clearCourseTreeAccess();
                    }
                    if (!this.hasReportPermission()){
                        this.clearReportTreeAccess();
                    }
                    this.checkForReady();
                },

                onClickAddPermission(){
                    const source_item = this.findPermissionByInput(this.new_permission_id);
                    if (!source_item){
                        return;
                    }
                    if (!this.hasCoursePermission() && this.isCourseChildPermission(source_item)){
                        return;
                    }

                    const assigned_ids = new Set(this.normalizePermissions(this.assigned_permissions).map(item => item.permition_id));
                    if (!assigned_ids.has(source_item.permition_id)){
                        this.assigned_permissions.push({
                            permition_id: source_item.permition_id,
                            permition_name: source_item.permition_name,
                            menu_item_name: source_item.menu_item_name,
                            permition_group: source_item.permition_group,
                            deadline: source_item.deadline || '2099-12-31'
                        });
                    }

                    this.new_permission_id = "";
                    this.assigned_permissions = this.normalizePermissions(this.assigned_permissions);
                    if (source_item.permition_name === this.$options.REPORT_PERMISSION_CODE) {
                        this.ensureReportsCatalogLoaded();
                    }
                    this.checkForReady();
                },

                onClickDeleteCourse(in_course_id){
                    this.assigned_courses = this.normalizeCourses(this.assigned_courses).filter(item => item.course_id != in_course_id);
                    this.checkForReady();
                },

                onClickAddCourse(){
                    if (!this.hasCoursePermission()){
                        return;
                    }
                    const source_item = this.findCourseByInput(this.new_course_id);
                    if (!source_item){
                        return;
                    }

                    const assigned_ids = new Set(this.normalizeCourses(this.assigned_courses).map(item => item.course_id));
                    if (!assigned_ids.has(source_item.course_id)){
                        this.assigned_courses.push({
                            course_id: source_item.course_id,
                            course_name: source_item.course_name,
                            period_in_days: source_item.period_in_days,
                            start_date: source_item.start_date,
                            available_until: this.getCalculatedCourseAvailableUntil(source_item)
                        });
                    }

                    this.new_course_id = "";
                    this.assigned_courses = this.normalizeCourses(this.assigned_courses);
                    this.checkForReady();
                },

                onChangeCourseAvailableUntil(in_course){
                    if (!in_course.available_until || in_course.available_until.length === 0){
                        in_course.available_until = this.getDefaultCourseDate();
                    }
                    this.assigned_courses = this.normalizeCourses(this.assigned_courses);
                    this.checkForReady();
                },

                onClickDeleteReport(in_report_id){
                    this.assigned_reports = this.normalizeReports(this.assigned_reports).filter(item => item.report_id != in_report_id);
                    this.checkForReady();
                },

                onClickAddReport(){
                    if (!this.hasReportPermission()){
                        return;
                    }
                    const source_item = this.findReportByInput(this.new_report_id);
                    if (!source_item){
                        return;
                    }

                    const assigned_ids = new Set(this.normalizeReports(this.assigned_reports).map(item => item.report_id));
                    if (!assigned_ids.has(source_item.report_id)){
                        this.assigned_reports.push({
                            report_id: source_item.report_id,
                            report_code: source_item.report_code,
                            report_name: source_item.report_name
                        });
                    }

                    this.new_report_id = "";
                    this.assigned_reports = this.normalizeReports(this.assigned_reports);
                    this.checkForReady();
                },

                onChangePermissionDeadline(in_permission){
                    const normalized_date = this.normalizeDateOnly(in_permission.deadline);
                    in_permission.deadline = normalized_date.length > 0 ? normalized_date : "2099-12-31";
                    this.assigned_permissions = this.normalizePermissions(this.assigned_permissions);
                    this.checkForReady();
                },


    },
    template: 
    `
    <!-- Modal content -->
    <div class="modal-content-40">
        <div class="modal-header">
            <span class="close" @click="onClickCloseForm()">&times;</span>
            <h2>Создание нового пользователя</h2>
         
        </div>
        <div class="modal-body">

        
            <div class="form-element">
                <label>Login</label>
                <input class="msll_filter" type="text" v-model="user_login" placeholder="Введите login пользователя (буквы латинского алфавита и цифры без пробелов)" @input="onChangeUserLogin(user_login)"/>
            </div>
            <div class="form-element">
                <label>Имя пользователя</label>
                <input class="msll_filter" type="text" v-model="user_username" placeholder="Введите ФИО пользователя" @input="onChangeUserName(user_username)"/>
            </div>
            <div class="form-element">
                <label>E-mail</label>
                <input class="msll_filter" type="text" v-model="user_email" placeholder="Введите e-mail пользователя (на этот email будет отправлен пароль)" @input="onChangeUserEmail(user_email)"/>
            </div>
            <div class="form-element">
                <label>Группа</label>
                <select class="msll_filter" v-model="user_user_group" @change="onChangeUserGroup(user_user_group)">
                    <option disabled value="">Выберите группу</option>
                    <option value="client">client</option>
                    <option value="operator">operator</option>
                </select>

            </div>

            <div class="msll_text_align_left">
                <h3>Доступные разделы</h3>
                <div v-if="is_permissions_loading">Загрузка полномочий...</div>
                <div v-if="permissions_error_message.length > 0" class="error">{{permissions_error_message}}</div>

                <div v-if="!is_permissions_loading && user_user_group.length > 0">
                    <div v-for="(group, group_index) in getTopLevelPermissionGroupsForViewWithFallback()"> 
                        <table class="msll_permissions_table">
                            <thead>
                                <tr>
                                    <th>Разделы</th>
                                    <th>Доступ до</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="permission_item in group.items">
                                    <td>{{permission_item.menu_item_name}}</td>
                                    <td class="msll_permissions_date_cell">
                                        <input class="msll_filter msll_filter_date_compact" type="date" v-model="permission_item.deadline" @change="onChangePermissionDeadline(permission_item)">
                                    </td>
                                    <td class="msll_permissions_action_cell">
                                        <input class="msll_smoll_button" type="button" value = "x" @click="onClickDeletePermission(permission_item.permition_id)">
                                    </td>
                                </tr>
                                <tr v-if="group_index === getTopLevelPermissionGroupsForViewWithFallback().length - 1">
                                    <td colspan="3">
                                        <div class="container_inline">
                                            <input class="msll_filter" type="text" v-model="new_permission_id" list="permission_options" placeholder="Начните вводить полномочие...">
                                            <datalist id="permission_options">
                                                <option v-for="permission_item in getAvailablePermissions()" :value="getPermissionOptionLabel(permission_item)"></option>
                                            </datalist>
                                            <input class="msll_small_button" type="button" value = "Добавить" @click="onClickAddPermission()">
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div v-if="group.items.some((item) => item.permition_name === $options.COURSE_PERMISSION_CODE)" class="tree-child-block">
                            <h3 class="msll_heading_with_tooltip">
                                Доступные учебные материалы
                                <span class="msll_heading_tooltip">Только если есть доступ к разделу "Учебные курсы"</span>
                            </h3>
                            <table class="msll_permissions_table">
                                <tbody>
                                    <tr v-for="permission_item in getChildCoursePermissionsForView()">
                                        <td>{{permission_item.menu_item_name}}</td>
                                        <td class="msll_permissions_date_cell">
                                            <input class="msll_filter msll_filter_date_compact" type="date" v-model="permission_item.deadline" @change="onChangePermissionDeadline(permission_item)">
                                        </td>
                                        <td class="msll_permissions_action_cell">
                                            <input class="msll_smoll_button" type="button" value = "x" @click="onClickDeletePermission(permission_item.permition_id)">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <table class="msll_permissions_table">
                                <thead>
                                    <tr>
                                        <th>Учебные курсы</th>
                                        <th>Доступ до</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="course_item in assigned_courses">
                                        <td>{{course_item.course_name}}</td>
                                        <td class="msll_permissions_date_cell">
                                            <input class="msll_filter msll_filter_date_compact" type="date" v-model="course_item.available_until" @change="onChangeCourseAvailableUntil(course_item)">
                                        </td>
                                        <td class="msll_permissions_action_cell">
                                            <input class="msll_smoll_button" type="button" value = "x" @click="onClickDeleteCourse(course_item.course_id)">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3">
                                            <div class="container_inline">
                                                <input class="msll_filter" type="text" v-model="new_course_id" list="course_options" placeholder="Начните вводить курс...">
                                                <datalist id="course_options">
                                                    <option v-for="course_item in getAvailableCourses()" :value="getCourseOptionLabel(course_item)"></option>
                                                </datalist>
                                                <input class="msll_small_button" type="button" value = "Добавить" @click="onClickAddCourse()">
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-if="hasReportPermission()" class="tree-child-block reports_permissions_block">
                        <h3 class="msll_heading_with_tooltip">
                            Доступные отчёты
                            <span class="msll_heading_tooltip">Только если есть доступ к разделу «Отчетность»</span>
                        </h3>
                        <div v-if="all_reports.length === 0" class="permissions_hint_text">
                            Справочник отчётов пуст. Проверьте, что применена миграция database/migration_marketing_consents.sql.
                        </div>
                        <table v-if="all_reports.length > 0" class="msll_permissions_table">
                            <thead>
                                <tr>
                                    <th>Отчёты</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="report_item in assigned_reports">
                                    <td>{{report_item.report_name}}</td>
                                    <td class="msll_permissions_action_cell">
                                        <input class="msll_smoll_button" type="button" value = "x" @click="onClickDeleteReport(report_item.report_id)">
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <div class="container_inline">
                                            <input class="msll_filter" type="text" v-model="new_report_id" list="report_options" placeholder="Начните вводить отчёт...">
                                            <datalist id="report_options">
                                                <option v-for="report_item in getAvailableReports()" :value="getReportOptionLabel(report_item)"></option>
                                            </datalist>
                                            <input class="msll_small_button" type="button" value = "Добавить" @click="onClickAddReport()">
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="form-element">
                <div class="error" style="height: 50px"><h2>{{WarningMessage}}</h2></div>
            </div>
            
            <input class="msll_middle_button" type="button" value = "Создать" @click="onClickCreateNewUser()" id="buttonCreateNewUser" disabled>
            <input class="msll_middle_button" type="button" value = "Отменить" @click="onClickCancel()">

        </div>
        <div class="modal-footer">
        </div>
    </div>      
    `
    }
