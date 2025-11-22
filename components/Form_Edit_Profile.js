export default {
    data() {
        return {
                    user_login: "",
                    user_username: "",
                    user_email: "",
                    user_user_group: "",
                                        
                    user_login_save: "",
                    user_username_save: "",
                    user_email_save: "",
                    user_user_group_save: "",
                    user_password: "",
                    user_repassword: "",

                    ref_to_parent: "",
                    WarningMessage: [],
/*                    
                    is_user_login_entered: false,
                    is_user_login_no_dublicate: false,
                    is_user_login_formate_correct: false,
*/                    
                    is_user_username_ready: false,
                    is_user_email_entered: false,
                    is_user_email_formate_correct: false,
                    is_user_email_ready: false,
                    is_user_user_group_ready: false,

                    is_enter_password: false,

                    t_type: "password"
                    


        }
    },
    methods: {
                async init(in_ref_to_parent){
                    //получаем инфо о текущем пользователе
                    
                    try {
                        const response = await axios.get("./queries/get_current_user_info.php");
                        if (response.data) {
                            //console.log(response.data);
                            this.user_login = response.data.login;
                            this.user_username = response.data.username;
                            this.user_email = response.data.email;
                            this.user_user_group = response.data.user_group;
                            

                        } else {
                            console.log('Ответ от сервера пустой (data undefined/null)');
                        }
                    } catch (error) {
                        // Обработка ошибки
                        console.error('Ошибка при запросе:', error);
                        if (error.response) {
                            console.error('Статус ошибки:', error.response.status);
                            console.error('Данные ошибки:', error.response.data);
                        }
                    } 

                    
                    this.ref_to_parent = in_ref_to_parent;
                    
                    // создаём копии переменных (для последующего отслеживания изменений)
                    this.user_username_save = this.user_username.repeat(1);
                    this.user_email_save = this.user_email.repeat(1);
                    this.user_user_group_save = this.user_user_group.repeat(1);

                    this.WarningMessage = [];
                  
                    this.is_user_username_ready = false;
                    this.is_user_email_entered = false;
                    this.is_user_email_formate_correct = false;
                    this.is_user_email_ready = false;
                    this.is_user_user_group_ready = false;

                    this.is_enter_password = false

                    this.onChangeUserName(this.user_username);
                    this.onChangeUserEmail(this.user_email, this.user_login);
                    this.onChangeUserGroup(this.user_user_group);

                    this.user_password = "";
                    this.user_repassword = "";
                    document.getElementById("input_passwords").hidden = true;
                    

                },                

                CloseForm(){
                    document.getElementById("id_FormEditProfile").style.display = "none";
                    document.body.style.overflow = '';
                },

                UpdateUser(){
                    let this2 = this;
                    
                    //запускаем спиннер    
                    document.getElementById("id_spinner_panel").style.display = "block"; 

                    axios.post("./queries/update_user_profile.php", {user_login: this.user_login, user_username: this.user_username, user_email: this.user_email, password: this.user_password})
                    .then(function (response) {
                        //console.log(response.data);
                        //console.log(this2.is_ValueNotEqual());
                        if (response.data == "1"){
                            //останавливаем спиннер    
                            document.getElementById("id_spinner_panel").style.display = "none";

                            // обноляем родительскую форму
                            //this2.ref_to_parent.refresh();
                            //this2.$root.refresh();
                            this2.$parent.refresh();
                            this2.$root.get_users();

                            //закрываем модальное окно
                            this2.CloseForm();
                        } else {
                            //останавливаем спиннер    
                            document.getElementById("id_spinner_panel").style.display = "none";

                            this2.ref_to_parent.$refs.ref_FormModalMessage.init(this, "Ошибка: Ожидалось, что будет создана 1 запись, но что-то пошло не так. <br>Ответ: <br>" + response.data);
                            //показываем сообщение    
                            document.getElementById("id_FormModalMessage").style.display = "block";                            

                            console.error("Ошибка: Ожидалось, что будет создана 1 запись, но что-то пошло не так. Ответ: " + response.data);
                            
                            // обноляем родительскую форму
                            this2.ref_to_parent.refresh();

                            //закрываем модальное окно
                            this2.CloseForm();
                        }
                    })
                    .catch(function (error) {
                        //останавливаем спиннер    
                        document.getElementById("id_spinner_panel").style.display = "none";

                        this2.ref_to_parent.$refs.ref_FormModalMessage.init(this, "Что-то пошло не так при обновлении пользователя. Пользователь не обновлён.<br>" + error);
                        //показываем сообщение    
                        document.getElementById("id_FormModalMessage").style.display = "block";                            


                        console.error(error);

                        // обноляем родительскую форму
                        this2.ref_to_parent.refresh();

                        //закрываем модальное окно
                        this2.CloseForm();

                    });
                },

                is_ValueNotEqual(){
                    if (
                        (this.user_username_save == this.user_username) && 
                        (this.user_email_save == this.user_email) &&
                        (this.user_user_group_save == this.user_user_group)){
                        return false;
                    } else {
                        return true;
                    }

                },

                checkForReady(){
/*                                        
                    console.log("=================================");
                    console.log("is_user_username_ready: " + this.is_user_username_ready);
                    console.log("is_user_email_entered: " + this.is_user_email_entered ); 
                    console.log("is_user_email_formate_correct: " + this.is_user_email_formate_correct );
                    console.log("is_user_email_no_dublicate: " + this.is_user_email_no_dublicate );
                    //console.log("is_user_user_group_ready: " + this.is_user_user_group_ready);
                    console.log("is_ValueNotEqual: " + this.is_ValueNotEqual());
                    console.log("is_enter_password: " + this.is_enter_password);
                    console.log("isValidPassword: " + this.isValidPassword(this.user_password) );
                    console.log("Password length: " +this.user_password.length)
                                        
                    if (this.user_password == this.user_repassword){
                        console.log("is equel password: true");
                    } else {
                        console.log("is equel password: false");
                    }
*/                    


                    if ((    
                            this.is_user_username_ready && 
                            this.is_user_email_entered && 
                            this.is_user_email_formate_correct &&
                            this.is_user_email_no_dublicate &&                       
                            this.is_ValueNotEqual() &&
                            this.is_enter_password==false
                        ) || (
                            this.is_user_username_ready && 
                            this.is_user_email_entered && 
                            this.is_user_email_formate_correct &&
                            this.is_user_email_no_dublicate && 
                            this.is_enter_password &&
                            this.isValidPassword(this.user_password) &&
                            this.user_password == this.user_repassword
                        )){

                       /*)&&((||(this.is_enter_password && this.user_password == this.user_repassword)))*/
                        document.getElementById("buttonUpdateProfile").disabled = false;
                        //console.log("disabled = false");
                    }else{
                        document.getElementById("buttonUpdateProfile").disabled = true;
                        //console.log("disabled = true");
                    }

                    //Формируем сообщения для пользователя
                    this.WarningMessage = [];
                    
                    if(!this.is_user_email_no_dublicate && this.user_email.length > 0){
                        this.WarningMessage.push("Такой E-mail уже зарегистрирован в системе.");
                    }

                    if (this.is_enter_password){

                        if (!this.isValidPassword(this.user_password)){
                            this.WarningMessage.push("Пароль должен состоять минимум из 8 символов.");
                        }
                        
                        if (this.user_password != this.user_repassword){
                            this.WarningMessage.push("Введённые пароли не совпадают.");
                        }
                    }

                },
/*
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
*/
                checkForDublicatEmail(in_user_email, in_user_login){
                    try {
                        axios.get('./queries/get_count_other_users_by_email.php', {
                            params: {
                                user_login: in_user_login,
                                user_email: in_user_email
                            }
                        })
                        .then((response2) => {
                            //console.log(response2.data)
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

                onClickUpdateUser(){
                    this.UpdateUser();
                },

                onClickCancel(){
                    this.CloseForm();
                },

                onChangeUserName(in_user_username){
                    
                    if(in_user_username.length > 0){
                        this.is_user_username_ready = true;
                    } else {
                        this.is_user_username_ready = false;
                    }
                    this.checkForReady();
                },

                onChangeUserEmail(in_user_email, in_user_login){
                    //console.log("start onChangeUserEmail")  

                    if(in_user_email.length > 0){
                        this.is_user_email_entered = true;

                        //проверяем на дубликаты
                        this.checkForDublicatEmail(in_user_email, in_user_login);

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
                    this.checkForReady();                 
                },

                onChangeSignEnterPassword(){ 

                    //console.log(this.is_enter_password);
                    //document.getElementById("input_passwords").hidden = false;
                    if (this.is_enter_password){
                        document.getElementById("input_passwords").hidden = false;
                        //this.t_type="password";
                    } else {
                        document.getElementById("input_passwords").hidden = true;
                        //this.t_type="text";
                    }
                    this.user_password = "";
                    this.user_repassword = "";
                    this.checkForReady();
                },

                onChangePassword(){
                    this.checkForReady()
                },
                
                onChangeRePassword(){
                    this.checkForReady()
                },

                isValidPassword(in_password){
                    if ((in_password)&&(in_password.length > 7)){
                        return true;
                    } else {
                        return false;
                    }
                }

    },
    template: 
    `
    <!-- Modal content -->
    <div class="modal-content-40">
        <div class="modal-header">
            <span class="close" @click="onClickCloseForm()">&times;</span>
            <h2>Редактирование данных пользователя</h2>
         
        </div>
        <div class="modal-body">
            <div class="form-element">
                <label>Login</label>
                <input class="msll_filter" type="text" v-model="user_login" placeholder="Введите login пользователя (буквы латинского алфавита и цифры без пробелов)" @input="onChangeUserLogin(user_login)" disabled/>
            </div>
            <div class="form-element">
                <label>Имя пользователя</label>
                <input class="msll_filter" type="text" v-model="user_username" placeholder="Введите ФИО пользователя" @input="onChangeUserName(user_username)"/>
            </div>
            <div class="form-element1">
                <label class="form-element1">E-mail</label>
                <input class="msll_filter" type="text" v-model="user_email" placeholder="Введите e-mail пользователя (на этот email будет отправлен пароль)" @input="onChangeUserEmail(user_email, user_login)"/>
            </div>
            <!--div class="form-element">
                <label>Группа</label>
                <select class="msll_filter" v-model="user_user_group" @change="onChangeUserGroup(user_user_group)" disabled>
                    <option disabled value="">Выберите группу</option>
                    <option value="client">client</option>
                    <option value="operator">operator</option>
                </select>
            </div-->
            <div class="form-element">
                <div class="container_inline2">
                    <input class="checkbox-height25" type="checkbox" v-model="is_enter_password" @change="onChangeSignEnterPassword"/>
                    <label class="label-align-left">Сменить пароль</label>
                </div>

            </div>
            <div class="form-element" id="input_passwords" hidden>
                <input class="msll_filter" :type="t_type" v-model="user_password" placeholder="Введите новый пароль." @input="onChangePassword()" />
                <input class="msll_filter" :type="t_type" v-model="user_repassword" placeholder="Повторите новый пароль." @input="onChangeRePassword()"/>
            </div>
            <div class="container_inline_center">
                <input class="msll_middle_button" type="button" value = "Обновить" @click="onClickUpdateUser()" id="buttonUpdateProfile" disabled>
                <input class="msll_middle_button" type="button" value = "Отменить" @click="onClickCancel()">
            </div>
            <div class="container_left" v-for="item in WarningMessage">
                <div class="error">{{item}}</div>
            </div>

        </div>
        <div class="modal-footer">
        </div>
    </div>      
    `
    }
