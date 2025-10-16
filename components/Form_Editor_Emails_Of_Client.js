export default {
    emits: ["update_client_data"],
    data() {
        return {
                    detail_client_emails: [],
                    detail_client_emails_saved: [],
                    detail_client_id: "",
                    WarningMessage: "",
                    dublicate_emails: [],
                    is_dublicate: false

        }
    },
    methods: {
                // When the user clicks on <span> (x), close the modal
                onClickCloseFormEditorEmailsOfClient(){
                    document.getElementById("Form_Editor_Emails_Of_Client").style.display = "none";
                },
                async activate(clientID){
                    this.detail_client_emails = [];
                    this.detail_client_emails_saved = [];                    
                    this.detail_client_id = "";
                    this.WarningMessage = "";
                    this.dublicate_emails = [];
                    this.is_dublicate = false;



                    //Получаем актуальные E-mail
                    try {
                        this.detail_client_id=clientID;
                        
                        const response = await axios.get('./queries/get_emails_by_id.php?clientID=' + clientID);
                        if (response.data) {
                            //console.log(response.data);
                            this.detail_client_emails = response.data; 
                            this.detail_client_emails_saved = JSON.parse(JSON.stringify(response.data));
                            
                            //сделать элемент модальным     
                            document.getElementById("Form_Editor_Emails_Of_Client").style.display = "block";

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
                    
                },
                onClickApplyFormEditorEmailsOfClient(){
                    
                    let var_client_emails_saved = this.detail_client_emails_saved;
                    let var_client_emails = this.detail_client_emails;
                    let var_client_id = this.detail_client_id;

                    //добавляем или обнолвяем адреса
                    var_client_emails.forEach(function(item) { 
                        
                        //проверяем не новый ли это адрес
                        if (var_client_emails_saved.findIndex((item_saved) => item_saved.email_id === item.email_id) == -1){
                            //alert("Это новый адрес id: " + item.email_id + " email: " + item.email);

                            let is_resp_success = false;                                        
                            is_resp_success= axios.post("./queries/add_client_email.php", {email: item.email, client_id: var_client_id})
                            .then(function (response) {
                                //console.log(response.data);
                                if (response.data=="1"){
                                    return true;
                                } else {
                                    alert(response.data);
                                }
                            })
                            .catch(function (error) {
                                alert(error);
                                console.log(error);
                            });
                            

                        } else if (item.email != var_client_emails_saved[var_client_emails_saved.findIndex((item_saved) => item_saved.email_id === item.email_id)].email){ 
                            //значения email текущий и сохранённый не равны
                            //alert("надо обновить адрес id: " + item.email_id + " email: " + item.email);
                            let is_resp_success = false;                                        
                            is_resp_success= axios.post("./queries/update_client_email_by_id.php", {email: item.email, email_id: item.email_id})
                            .then(function (response) {
                                //console.log(response.data);
                                if (response.data=="1"){
                                    return true;
                                } else {
                                    alert("Удалено "+response.data+" записей");
                                }
                            })
                            .catch(function (error) {
                                alert(error);
                                console.log(error);
                            });
                        }
                    });

                    //удаляем адреса
                    var_client_emails_saved.forEach(function(item_saved) {
                        if (var_client_emails.findIndex((item) => item.email_id === item_saved.email_id) == -1){
                            //alert("удалить адрес id: " + item_saved.email_id + " email: " + item_saved.email);
                            let is_resp_success = false;                                        
                            is_resp_success= axios.post("./queries/delete_client_email_by_id.php", {email_id: item_saved.email_id})
                            .then(function (response) {
                                //console.log(response.data);
                                if (response.data=="1"){
                                    return true;
                                } else {
                                    alert("Удалено "+response.data+" записей");
                                }
                            })
                            .catch(function (error) {
                                alert(error);
                                console.log(error);
                            });
                        }
                    });
                                                           
                    //alert( this.detail_client_emails[1].email );
                    
                    this.$emit('update_client_data', 'Emails');
                    this.onClickCloseFormEditorEmailsOfClient();
                    
                },
                onClikDeleteDeleteEmail(in_email_id){
                    //this.WarningMessage = "ЕСТЬ НЕСОХРАНЁННЫЕ ИЗМЕНЕНИЯ";
                    this.detail_client_emails.splice(this.detail_client_emails.findIndex((item) => item.email_id === in_email_id), 1); 
                    this.find_dublicate();
                    this.check_for_change();
                },

                onClickAddEmail(){
                    //this.WarningMessage = "ЕСТЬ НЕСОХРАНЁННЫЕ ИЗМЕНЕНИЯ";
                    this.detail_client_emails.push({ email: "", email_id: Date.now()}); 
                    this.check_for_change();
                },

                onChangeEmail(in_email_id){
                    this.find_dublicate();
                    this.check_for_change();
                },

                async find_dublicate(){
                    this.dublicate_emails = [];
                    this.is_dublicate = false;

                    for (const entered_email of this.detail_client_emails) {
                        let sql = "";
                        try {
                            sql = './queries/get_dublicate_of_email.php?email='+entered_email.email+'&client_id='+this.detail_client_id;
                            const response = await axios.get(sql);
                            // Обработка успешного ответа
                            if (response.data) {
                            // Далее работаем с данными
                                if(response.data!="") {
                                    for (const row of response.data) {
                                        this.dublicate_emails.push({email: row.email, fio: row.fio});
                                        this.is_dublicate = true;
                                        this.check_for_change();
                                    }
                                }
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
                    }
                },
                check_for_change(){
                    this.WarningMessage = "";
                    
                    //ищем телефоны из сохраненного массива в актуальном массиве
                    for (const email_saved of this.detail_client_emails_saved) {
                        //тестируем каждый элемент сохраненного массива
                        if (this.detail_client_emails.findIndex((item_actual) => item_actual.email === email_saved.email) == -1){
                            this.WarningMessage = "ЕСТЬ НЕСОХРАНЁННЫЕ ИЗМЕНЕНИЯ";
                        }
                    }

                    //ищем телефоны актуального  массива в сохраненном массиве 
                    for (const email_actual of this.detail_client_emails) {
                        //тестируем каждый элемент сохраненного массива
                        if (this.detail_client_emails_saved.findIndex((item_saved) => item_saved.email === email_actual.email) == -1){
                            this.WarningMessage = "ЕСТЬ НЕСОХРАНЁННЫЕ ИЗМЕНЕНИЯ";
                            
                        }
                    }

                    if (this.WarningMessage == ""){
                        document.getElementById("apply_form_editor_emails_button").disabled = true;
                    } else {
                        document.getElementById("apply_form_editor_emails_button").disabled = false;
                    }

                    for (const email_actual of this.detail_client_emails) {
                        
                        if (email_actual.email){
                            if (!this.validateEmail(email_actual.email)){
                                document.getElementById("apply_form_editor_emails_button").disabled = true;
                            }
                        } else {
                            document.getElementById("apply_form_editor_emails_button").disabled = true;
                        }
                    }

                    //console.log(this.is_dublicate);
                    if (this.is_dublicate){
                        document.getElementById("apply_form_editor_emails_button").disabled = true;
                        //console.log("есть дубликаты");
                    }
                },

                validateEmail(email) {
                    var re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                    return re.test(String(email).toLowerCase());
                }                

                
    },
    template: 
    `
    <!-- Modal content -->
    <div class="modal-content-40">
        <div class="modal-header">
            <span class="close" @click="onClickCloseFormEditorEmailsOfClient()">&times;</span>
            <h2>Изменение списка e-mail адресов клиента</h2>
        </div>
        <div class="modal-body">
            <div class="ERROR">{{WarningMessage}}<br/></div>
            <div class="container_inline" v-for="detail_client_email in detail_client_emails">
                <input class="msll_filter" type="input" v-model="detail_client_email.email" @input="onChangeEmail(detail_client_email.email_id)"/>
                <input type="button" value = "&times;" @click='onClikDeleteDeleteEmail(detail_client_email.email_id)'>
            </div>
            <button class="msll_middle_button" type="button" @click="onClickAddEmail()">Добавить E-Mail</button>
            <button class="msll_middle_button" type="button" @click="onClickApplyFormEditorEmailsOfClient()" id="apply_form_editor_emails_button" disabled>Применить</button>
            <button class="msll_middle_button" type="button" @click="onClickCloseFormEditorEmailsOfClient()">Отменить</button>

            <table class='msll_table'>
                <tbody>
                    <tr v-for="dublicate_email in dublicate_emails">
                        <td> {{dublicate_email.email}}</td>
                        <td class="ERROR">принадлежит клиенту</td>
                        <td> {{dublicate_email.fio}}</td>
                    </tr>
                </tbody>
            </table>    


        </div>
        <div class="modal-footer">
        </div>
    </div>      
    `
    }
