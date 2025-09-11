import FormEditorLastNameOfClient from '../components/Form_Editor_LastName_Of_Client.js';
import FormEditorFirstNameOfClient from '../components/Form_Editor_FirstName_Of_Client.js';
import FormEditorPatronymicOfClient from '../components/Form_Editor_Patronymic_Of_Client.js';

export default {
emits: ["update_client_data"],    

components: {
    FormEditorLastNameOfClient,
    FormEditorFirstNameOfClient,
    FormEditorPatronymicOfClient
},
data() {
    return {
        currentClientID: 0,
        detail_client_last_name: "Иванов",
        detail_client_first_name: "Иван",
        detail_client_patronymic: "Иванович",
        detail_client_emails: ["sale@mycompany.com","pupsil@yandeх.ru"],
        detail_client_phones: ["+79031111111","+79031111112","+79031111113"],
        detail_client_telegrams: ["ivanovII","SladkiyPupsik","LaskoviyMerzavets", "AlexeyPavlikhin"],
        detail_client_job: 'Кондитерская "Рога и копыта "',
        detail_client_comment: "Песня В лесу родилась елочка – шедевр новогоднего настроения, индикатор радости детворы.",
        detail_client_sold_produtcs: [
            {date: "01.01.2025", product_name: "ПАЗИС 1", staus: "НЕ ОПРЕДЕЛЁН", comment: "Очень понравился"},
            {date: "01.02.2025", product_name: "ПАЗИС 2", staus: "НЕ ОПРЕДЕЛЁН", comment: "Очень понравился"},
            {date: "01.03.2025", product_name: "ПАЗИС 3", staus: "НЕ ОПРЕДЕЛЁН", comment: "Очень понравился"}
        ]

    }
},
methods: {
    // When the user clicks on <span> (x), close the modal
    onClickCloseClientDetail(){
        document.getElementById("form_Detail_Info_Of_Client").style.display = "none";
        document.body.style.overflow = '';
    },

    async onClikClientDetail(clientID){
        this.currentClientID=clientID;

        //Получаем Фамилию
        try {
            const response = await axios.get('./queries/get_last_name_by_id.php?clientID='+clientID);
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
        }

        //Получаем Имя
        try {
            const response = await axios.get('./queries/get_first_name_by_id.php?clientID='+clientID);
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
        }
        
        //Получаем Отчество
        try {
            const response = await axios.get('./queries/get_patronymic_by_id.php?clientID='+clientID);
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
        }                
            
        //Получаем проданные клиенту продукты
            try {
            const response = await axios.get('./queries/get_sold_prodicts_by_id.php?clientID='+clientID);
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
        }                

        //Получаем Email
        try {
            const response = await axios.get('./queries/get_email_by_id.php?clientID='+clientID);
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
        }

        //Получаем номера телефонов
        try {
            const response = await axios.get('./queries/get_phone_by_id.php?clientID='+clientID);
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
        }
        
        //Получаем номера телеграм
        try {
            const response = await axios.get('./queries/get_telegram_by_id.php?clientID='+clientID);
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
        }
        
        //Получаем Мето работы
        try {
            const response = await axios.get('./queries/get_job_by_id.php?clientID='+clientID);
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
        }                  

        //Получаем комментарий по клиенту
        try {
            const response = await axios.get('./queries/get_comment_by_id.php?clientID='+clientID);
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
        }  
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
    onClikChangeLastName(){
        //отключить прокрутку страницы
        document.body.style.overflow = 'hidden';

        this.$refs.FormEditorLastNameOfClientRef.activate(this.currentClientID);
    },
    onClikChangeFirstName(){
        //отключить прокрутку страницы
        document.body.style.overflow = 'hidden';

        this.$refs.FormEditorFirstNameOfClientRef.activate(this.currentClientID);
    },    
    onClikChangePatronymic(){
        //отключить прокрутку страницы
        document.body.style.overflow = 'hidden';

        this.$refs.FormEditorPatronymicOfClientRef.activate(this.currentClientID);
    },     
    onChangeLastName(in_clientID, in_clientLastName){
        //alert('Event in Parent'+" "+in_clientID+" "+in_clientLastName);
        this.detail_client_last_name = in_clientLastName;
        this.$emit('update_client_data', in_clientID, in_clientLastName);
    },
    onChangeFirstName(in_clientID, in_clientFirstName){
        //alert('Event in Parent'+" "+in_clientID+" "+in_clientFirstName);
        this.detail_client_first_name = in_clientFirstName;
        this.$emit('update_client_data', in_clientID, in_clientFirstName);
    },
    onChangePatronymic(in_clientID, in_clientPatronymic){
        //alert('Event in Parent'+" "+in_clientID+" "+in_clientPatronymic);
        this.detail_client_patronymic = in_clientPatronymic;
        this.$emit('update_client_data', in_clientID, in_clientPatronymic);
    }

},
template: 
  `
<!-- Modal content -->
<div class="modal-content">
    <div class="modal-header">
        <span class="close" @click="onClickCloseClientDetail()">&times;</span>
        <h2>Детальная информация о клиенте</h2>
    </div>
    <div class="modal-body">
        <br>
        <table class='msll_table'>
            <tbody>

                <tr>
                    <td width='20%'>Фаимлия</td>
                    <td width='70%'>{{detail_client_last_name}}</td>
                    <td width='10%'><input type="button" value = "Изменить" @click='onClikChangeLastName'></td>
                </tr>
                <tr>
                    <td>Имя</td>
                    <td>{{detail_client_first_name}}</td>
                    <td><input type="button" value = "Изменить" @click='onClikChangeFirstName'></td>
                </tr>
                <tr>
                    <td>Отчество</td>
                    <td>{{detail_client_patronymic}}</td>
                    <td><input type="button" value = "Изменить" @click='onClikChangePatronymic'></td>
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
                                    <!--<td>{{item.subproduct_name}}</td>-->
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td><input type="button" value = "Изменить" @click='onClikDeleteItemOfConditions(condition.item_id)'></td>
                </tr>
                <tr>
                    <td>E-mail адреса</td>
                    <td>
                        <div v-for="detail_client_email in detail_client_emails">
                            <p>{{detail_client_email}}</p>
                        </div>
                    </td>
                    <td><input type="button" value = "Изменить" @click='onClikDeleteItemOfConditions(condition.item_id)'></td>
                </tr>
                <tr>
                    <td>Номера телефонов</td>
                    <td>
                        <div v-for="detail_client_phone in detail_client_phones">
                            <p>{{formate_phone(detail_client_phone)}}</p>
                        </div>
                    </td>
                    <td><input type="button" value = "Изменить" @click='onClikDeleteItemOfConditions(condition.item_id)'></td>
                </tr>
                <tr>
                    <td>Имя в Telegramm</td>
                    <td>
                        <div v-for="detail_client_telegram in detail_client_telegrams">
                            <a href="https://t.me/ detail_client_telegram">@{{detail_client_telegram}}</a>
                        </div>
                    </td>
                    <td><input type="button" value = "Изменить" @click='onClikDeleteItemOfConditions(condition.item_id)'></td>
                </tr>
                <tr>
                    <td>Место работы </td>
                    <td>{{detail_client_job}}</td>
                    <td><input type="button" value = "Изменить" @click='onClikDeleteItemOfConditions(condition.item_id)'></td>
                </tr>
                <tr>
                    <td>Комментарий</td>
                    <td>{{detail_client_comment}}</td>
                    <td><input type="button" value = "Изменить" @click='onClikDeleteItemOfConditions(condition.item_id)'></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="modal-footer">
    </div>

    <div id="Form_Editor_LastName_Of_Client" class="modal">
        <Form-Editor-Last-Name-Of-Client ref="FormEditorLastNameOfClientRef" @update_lastname="onChangeLastName"/>
     </div> 

     <div id="Form_Editor_FirstName_Of_Client" class="modal">
        <Form-Editor-First-Name-Of-Client ref="FormEditorFirstNameOfClientRef" @update_firstname="onChangeFirstName"/>
     </div> 
     
     <div id="Form_Editor_Patronymic_Of_Client" class="modal">
        <Form-Editor-Patronymic-Of-Client ref="FormEditorPatronymicOfClientRef" @update_patronymic="onChangePatronymic"/>
     </div> 


</div>      
  `
}
