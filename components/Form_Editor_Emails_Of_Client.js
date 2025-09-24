export default {
    emits: ["update_client_data"],
    data() {
        return {
                    detail_client_emails: "",
                    detail_client_emails_saved: "",
                    detail_client_id: "",
                    WarningMessage: ""
        }
    },
    methods: {
                // When the user clicks on <span> (x), close the modal
                onClickCloseFormEditorEmailsOfClient(){
                    document.getElementById("Form_Editor_Emails_Of_Client").style.display = "none";
                },
                async activate(clientID){
                    this.WarningMessage = "";

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
                                    //this.is_resp_success = true;
                                    //is_resp_success1 = true;
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
                                    //this.is_resp_success = true;
                                    //is_resp_success1 = true;
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
/*                            
                            if (is_resp_success) {
                                //alert("отправили событие");
                                this.activate(this.detail_client_id)
                                this.$emit('update_client_emails', in_email_id);
                            }                    
*/                            
                        }
                    });
                    
                                       
                    //alert( this.detail_client_emails[1].email );
                    
                    this.$emit('update_client_data', 'Emails');
                    this.onClickCloseFormEditorEmailsOfClient();

                    
                },
                onClikDeleteDeleteEmail(in_email_id){
                    this.WarningMessage = "ЕСТЬ НЕСОХРАНЁННЫЕ ИЗМЕНЕНИЯ";
                    this.detail_client_emails.splice(this.detail_client_emails.findIndex((item) => item.email_id === in_email_id), 1); 



                },
                onClickAddEmail(){
                    this.WarningMessage = "ЕСТЬ НЕСОХРАНЁННЫЕ ИЗМЕНЕНИЯ";
                    this.detail_client_emails.push({ email: "", email_id: Date.now()}); 
                }
                
    },
    template: 
    `
    <!-- Modal content -->
    <div class="modal-content-editor">
        <div class="modal-header">
            <span class="close" @click="onClickCloseFormEditorEmailsOfClient()">&times;</span>
            <h2>Изменение списка e-mail адресов клиента</h2>
        </div>
        <div class="modal-body">
            <div class="ERROR">{{WarningMessage}}<br/></div>
            <button class="msll_middle_button" type="button" @click="onClickAddEmail()">Добавить E-Mail</button>
            <div class="container_inline" v-for="detail_client_email in detail_client_emails">
                <input class="msll_filter" type="input" v-model="detail_client_email.email"/>
                <input type="button" value = "&times;" @click='onClikDeleteDeleteEmail(detail_client_email.email_id)'>
            </div>

            <button class="msll_middle_button" type="button" @click="onClickApplyFormEditorEmailsOfClient()">Применить</button>
            <button class="msll_middle_button" type="button" @click="onClickCloseFormEditorEmailsOfClient()">Отменить</button>

        </div>
        <div class="modal-footer">
        </div>
    </div>      
    `
    }
