import FormEditorLastNameOfClient from '../components/Form_Editor_LastName_Of_Client.js';
import FormEditorFirstNameOfClient from '../components/Form_Editor_FirstName_Of_Client.js';
import FormEditorPatronymicOfClient from '../components/Form_Editor_Patronymic_Of_Client.js';
import FormEditorJobOfClient from '../components/Form_Editor_Job_Of_Client.js';
import FormEditorCommentOfClient from '../components/Form_Editor_Comment_Of_Client.js';
import FormEditorEmailsOfClient from '../components/Form_Editor_Emails_Of_Client.js';
import FormEditorPhonesOfClient from '../components/Form_Editor_Phones_Of_Client.js';
import FormEditorTelegramsOfClient from '../components/Form_Editor_Telegrams_Of_Client.js';
import FormEditorProductsOfClient from '../components/Form_Editor_Products_Of_Client.js';

export default {
emits: ["update_client_data"],    

    components: {
        FormEditorLastNameOfClient,
        FormEditorFirstNameOfClient,
        FormEditorPatronymicOfClient,
        FormEditorJobOfClient,
        FormEditorCommentOfClient,
        FormEditorEmailsOfClient,
        FormEditorPhonesOfClient,
        FormEditorTelegramsOfClient,
        FormEditorProductsOfClient
    },
    data() {
        return {
            currentClientID: 0,
            detail_client_last_name: "",
            detail_client_first_name: "",
            detail_client_patronymic: "",
            detail_client_emails: "",
            detail_client_phones: "",
            detail_client_telegrams: "",
            detail_client_job: "",
            detail_client_comment: "",
            detail_client_sold_produtcs: "",

            v_step_cont_all: 9,
            v_current_step_count: 0

        }
    },
    methods: {
        // When the user clicks on <span> (x), close the modal
        onClickCloseClientDetail(){
            document.getElementById("form_Detail_Info_Of_Client").style.display = "none";
            document.body.style.overflow = '';
        },

        async getClientLastName(){
            //Получаем Фамилию
            try {
                const response = await axios.get('./queries/get_last_name_by_id.php?clientID=' + this.currentClientID);
                if (response.data) {
                    //console.log(response.data);
                    this.detail_client_last_name=response.data;

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
            } finally {
                this.v_current_step_count ++;
                if (this.v_current_step_count == this.v_step_cont_all){
                    document.getElementById("spinner_panel").style.display = "none";
                }
            }
    
        },
        async getClientFirstName(){
            //Получаем Имя
            try {
                const response = await axios.get('./queries/get_first_name_by_id.php?clientID=' + this.currentClientID);
                if (response.data) {
                    //console.log(response.data);
                    this.detail_client_first_name=response.data;

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
            } finally {
                this.v_current_step_count ++;
                if (this.v_current_step_count == this.v_step_cont_all){
                    document.getElementById("spinner_panel").style.display = "none";
                }
            }
        },
        async getClientPatronymic(){
            //Получаем Отчество
            try {
                const response = await axios.get('./queries/get_patronymic_by_id.php?clientID=' + this.currentClientID);
                if (response.data) {
                    //console.log(response.data);
                    this.detail_client_patronymic=response.data;

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
            } finally {
                this.v_current_step_count ++;
                if (this.v_current_step_count == this.v_step_cont_all){
                    document.getElementById("spinner_panel").style.display = "none";
                }
            }                
        },
        async getClientSoldProducts(){
            //Получаем проданные клиенту продукты
                try {
                const response = await axios.get('./queries/get_sold_products_by_id.php?clientID=' + this.currentClientID);
                if (response.data) {
                    //console.log(response.data);
                    //console.log(this.detail_client_sold_produtcs);
                    this.detail_client_sold_produtcs=response.data;
                    //this.detail_client_sold_produtcs= JSON.parse("[{\"date\": \"1977-02-04\", \"product_name\": \"ПАЗИС 1\", \"subproduct_name\": \"Полный доступ\"}]");
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
            } finally {
                this.v_current_step_count ++;
                if (this.v_current_step_count == this.v_step_cont_all){
                    document.getElementById("spinner_panel").style.display = "none";
                }
            }                
        },
        async getClientEmails(){
            //Получаем Email
            try {
                const response = await axios.get('./queries/get_emails_by_id.php?clientID=' + this.currentClientID);
                if (response.data) {
                    //console.log(response.data);
                    this.detail_client_emails=response.data;

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
            } finally {
                this.v_current_step_count ++;
                if (this.v_current_step_count == this.v_step_cont_all){
                    document.getElementById("spinner_panel").style.display = "none";
                }
            }
        },
        async getClientPhones(){
            //Получаем номера телефонов
            try {
                const response = await axios.get('./queries/get_phones_by_id.php?clientID=' + this.currentClientID);
                if (response.data) {
                    //console.log(response.data);
                    this.detail_client_phones=response.data;

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
            } finally {
                this.v_current_step_count ++;
                if (this.v_current_step_count == this.v_step_cont_all){
                    document.getElementById("spinner_panel").style.display = "none";
                }
            }
        },
        async getClientTelegrams(){
            //Получаем номера телеграм
            try {
                const response = await axios.get('./queries/get_telegrams_by_id.php?clientID=' + this.currentClientID);
                if (response.data) {
                    //console.log(response.data);
                    this.detail_client_telegrams=response.data;

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
            } finally {
                this.v_current_step_count ++;
                if (this.v_current_step_count == this.v_step_cont_all){
                    document.getElementById("spinner_panel").style.display = "none";
                }
            }
        },
        async getClientJob(){
            //Получаем Мето работы
            try {
                const response = await axios.get('./queries/get_job_by_id.php?clientID=' + this.currentClientID);
                if (response.data) {
                    //console.log(response.data);
                    this.detail_client_job=response.data;

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
            } finally {
                this.v_current_step_count ++;
                if (this.v_current_step_count == this.v_step_cont_all){
                    document.getElementById("spinner_panel").style.display = "none";
                }
            }                  
        },
        async getClientComment(){
            //Получаем комментарий по клиенту
            try {
                const response = await axios.get('./queries/get_comment_by_id.php?clientID=' + this.currentClientID);
                if (response.data) {
                    //console.log(response.data);
                    this.detail_client_comment=response.data;

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
            } finally {
                this.v_current_step_count ++;
                if (this.v_current_step_count == this.v_step_cont_all){
                    document.getElementById("spinner_panel").style.display = "none";
                }
            }  
        },
        async onClikClientDetail(clientID){


            //сделать спиннер модальным     
            document.getElementById("spinner_panel").style.display = "block";
            
            // инициируем свойство currentClientID
            this.currentClientID=clientID;
            
            // обнуляем свояйства (оставшиеся заполненнымии от предыдущих показов)

            this.detail_client_last_name = "";
            this.detail_client_first_name = "";
            this.detail_client_patronymic = "";
            this.detail_client_emails = "";
            this.detail_client_phones = "";
            this.detail_client_telegrams = "";
            this.detail_client_job = "";
            this.detail_client_comment = "";
            this.detail_client_sold_produtcs = "";
            
            this.v_current_step_count = 0;
            
            //Получаем Фамилию
            this.getClientLastName();

            //Получаем Имя
            this.getClientFirstName();

            //Получаем Отчество
            this.getClientPatronymic();

            //Получаем проданные клиенту продукты
            this.getClientSoldProducts();

            //Получаем Email
            this.getClientEmails();

            //Получаем номера телефонов        
            this.getClientPhones();
                
            //Получаем номера телеграм
            this.getClientTelegrams();

            //Получаем Мето работы
            this.getClientJob();

            //Получаем комментарий по клиенту        
            this.getClientComment();

        },
        formate_phone(in_phone){
            let ret;
            let in_phone2;
            in_phone2 = in_phone;

            ret="-"+in_phone.slice(-2);
            in_phone=in_phone.slice(0,-2);

            ret="-"+in_phone.slice(-2)+ret;
            in_phone=in_phone.slice(0,-2);

            ret=") "+in_phone.slice(-3)+ret;
            in_phone=in_phone.slice(0,-3);

            ret=" ("+in_phone.slice(-3)+ret;
            in_phone=in_phone.slice(0,-3);                

            ret="+"+in_phone+ret;
            
            return ret;
        },

        onClikChangeData(DataType){
            //отключить прокрутку страницы
            document.body.style.overflow = 'hidden';

            switch (DataType) {
            case "LastNane":
                this.$refs.FormEditorLastNameOfClientRef.activate(this.currentClientID);
                break;
            case "FirstName":
                this.$refs.FormEditorFirstNameOfClientRef.activate(this.currentClientID);
                break;
            case "Patronymic":
                this.$refs.FormEditorPatronymicOfClientRef.activate(this.currentClientID);
                break;
            case "Products":
                this.$refs.FormEditorProductsOfClientRef.activate(this.currentClientID);
                break;        
            case "Emails":
                this.$refs.FormEditorEmailsOfClientRef.activate(this.currentClientID);
                break;
            case "Phones":
                this.$refs.FormEditorPhonesOfClientRef.activate(this.currentClientID);
                break;
            case "Telegram":
                this.$refs.FormEditorTelegramsOfClientRef.activate(this.currentClientID);
                break;
            case "Job":
                this.$refs.FormEditorJobOfClientRef.activate(this.currentClientID);
                break;
            case "Comment":
                this.$refs.FormEditorCommentOfClientRef.activate(this.currentClientID);
                break;
            }    

        },
        onChangeClientData(DataType){
            this.$emit('update_client_data');
            switch (DataType) {
            case "LastName":
                this.getClientLastName();
                break;            
            case "FirstName":
                this.getClientFirstName();
                break;            
            case "Patronymic":
                this.getClientPatronymic();
                break;
            case "Products":
                this.getClientSoldProducts();
                break;            
            case "Emails":
                this.getClientEmails();
                break;            
            case "Phones":
                this.getClientPhones();
                break;
            case "Telegrams":
                this.getClientTelegrams();
                break;               
            case "Job":
                this.getClientJob();
                break;
            case "Comment":
                this.getClientComment();
                break;            
            }
        },
        createTGLink(inLink){
            return "https://t.me/"+inLink.replaceAll("@", "");
        },
        createMTLink(inLink){
            return "mailto:"+inLink;
        },
        createTelLink(inLink){
            return "tel:+"+inLink;
        }     
    },
    template: 
    `
    <!-- Modal content -->
    <div class="modal-content-50">
        <div class="modal-header">
            <span class="close" @click="onClickCloseClientDetail()">&times;</span>
            <h2>Детальная информация о клиенте</h2>
        </div>
        <div class="modal-body">
            <br>
            <table class='msll_table'>
                <tbody class = "td_no_padding">

                    <tr class = "td_no_padding">
                        <td width ='20%'>Фаимлия</td>
                        <td width ='70%'>{{detail_client_last_name}}</td>
                        <td width ='10%' class = "td_no_padding"><input class="msll_small_button" type="button" value = "Изменить" @click="onClikChangeData('LastNane')"></td>
                    </tr>
                    <tr>
                        <td>Имя</td>
                        <td>{{detail_client_first_name}}</td>
                        <td><input class="msll_small_button" type="button" value = "Изменить" @click="onClikChangeData('FirstName')"></td>
                    </tr>
                    <tr>
                        <td>Отчество</td>
                        <td>{{detail_client_patronymic}}</td>
                        <td><input class="msll_small_button" type="button" value = "Изменить" @click="onClikChangeData('Patronymic')"></td>
                    </tr>
                    <tr>
                        <td>Преобретённые продукты</td>
                        <td>
                            <table class='msll_table2'>
                                <tbody>
                                    <tr>
                                        <th width='30%'>Дата покупки</th>
                                        <th width='70%'>Название продукта</th>
                                        <th width='70%'>Статус</th>
                                        <th width='70%'>Комментарий</th>
                                        <!--<th width='70%'>Название подпродукта</th>-->
                                    </tr>
                                    <tr v-for="item in detail_client_sold_produtcs">
                                        <td>{{item.date}}</td>
                                        <td>{{item.product_name}}</td>
                                        <td>{{item.status}}</td>
                                        <td>{{item.comment}}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                        <td><input class="msll_small_button" type="button" value = "Изменить" @click="onClikChangeData('Products')"></td>
                    </tr>
                    <tr>
                        <td>E-mail адреса</td>
                        <td>
                            <div v-for="detail_client_email in detail_client_emails">
                                <!--<p>{{detail_client_email.email}}</p>-->
                                <a :href=createMTLink(detail_client_email.email) target="_blank">{{detail_client_email.email}}</a>
                            </div>
                        </td>
                        <td><input class="msll_small_button" type="button" value = "Изменить" @click="onClikChangeData('Emails')"></td>
                    </tr>
                    <tr>
                        <td>Номера телефонов</td>
                        <td>
                            <div v-for="detail_client_phone in detail_client_phones">
                                <!--<p>{{formate_phone(detail_client_phone.phone)}}</p>-->
                                <a :href=createTelLink(detail_client_phone.phone) target="_blank">{{formate_phone(detail_client_phone.phone)}}</a>
                            </div>
                        </td>
                        <td><input class="msll_small_button" type="button" value = "Изменить" @click="onClikChangeData('Phones')"></td>
                    </tr>
                    <tr>
                        <td>Имя в Telegramm</td>
                        <td>
                            <div v-for="detail_client_telegram in detail_client_telegrams">
                                <a :href=createTGLink(detail_client_telegram.telegram) target="_blank">{{detail_client_telegram.telegram}}</a>
                            </div>
                        </td>
                        <td><input class="msll_small_button" type="button" value = "Изменить" @click="onClikChangeData('Telegram')"></td>
                    </tr>
                    <tr>
                        <td>Место работы </td>
                        <td>{{detail_client_job}}</td>
                        <td><input class="msll_small_button" type="button" value = "Изменить" @click="onClikChangeData('Job')"></td>
                    </tr>
                    <tr>
                        <td>Комментарий</td>
                        <td>{{detail_client_comment}}</td>
                        <td><input class="msll_small_button" type="button" value = "Изменить" @click="onClikChangeData('Comment')"></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
        </div>

        <div id="Form_Editor_LastName_Of_Client" class="modal">
            <Form-Editor-Last-Name-Of-Client ref="FormEditorLastNameOfClientRef" @update_client_data="onChangeClientData"/>
        </div> 

        <div id="Form_Editor_FirstName_Of_Client" class="modal">
            <Form-Editor-First-Name-Of-Client ref="FormEditorFirstNameOfClientRef" @update_client_data="onChangeClientData"/>
        </div> 
        
        <div id="Form_Editor_Patronymic_Of_Client" class="modal">
            <Form-Editor-Patronymic-Of-Client ref="FormEditorPatronymicOfClientRef" @update_client_data="onChangeClientData"/>
        </div> 

        <div id="Form_Editor_Products_Of_Client" class="modal">
            <Form-Editor-Products-Of-Client ref="FormEditorProductsOfClientRef" @update_client_data="onChangeClientData"/>
        </div>     

        <div id="Form_Editor_Emails_Of_Client" class="modal">
            <Form-Editor-Emails-Of-Client ref="FormEditorEmailsOfClientRef" @update_client_data="onChangeClientData"/>
        </div> 

        <div id="Form_Editor_Phones_Of_Client" class="modal">
            <Form-Editor-Phones-Of-Client ref="FormEditorPhonesOfClientRef" @update_client_data="onChangeClientData"/>
        </div> 

        <div id="Form_Editor_Telegrams_Of_Client" class="modal">
            <Form-Editor-Telegrams-Of-Client ref="FormEditorTelegramsOfClientRef" @update_client_data="onChangeClientData"/>
        </div> 


        <div id="Form_Editor_Job_Of_Client" class="modal">
            <Form-Editor-Job-Of-Client ref="FormEditorJobOfClientRef" @update_client_data="onChangeClientData"/>
        </div> 

        <div id="Form_Editor_Comment_Of_Client" class="modal">
            <Form-Editor-Comment-Of-Client ref="FormEditorCommentOfClientRef" @update_client_data="onChangeClientData"/>
        </div> 

        <div id="spinner_panel" class="modal">
            <!-- Modal content -->
            <div class="modal-content-40">
                <div class="modal-header">
                </div>
                <div class="modal-body">
                    <p>Загрузка...</p>
                    <img src="./pictures/Hourglass.gif"
                </div>
                <div class="modal-footer">
                </div>
            </div>
        </div> 

    </div>      
    `
}
