export default {
    emits: ["update_client_data"],
    data() {
        return {
                    detail_client_job: "",
                    detail_client_job_saved: "",
                    detail_client_id: ""
        }
    },
    methods: {
                // When the user clicks on <span> (x), close the modal
                onClickCloseFormEditorJobOfClient(){
                    document.getElementById("Form_Editor_Job_Of_Client").style.display = "none";
                },
                async activate(clientID){
                    //Получаем Фамилию
                    try {
                        this.detail_client_id=clientID;
                        
                        const response = await axios.get('./queries/get_job_by_id.php?clientID='+clientID);
                        if (response.data) {
                            //console.log(response.data);
                            this.detail_client_job = response.data; 
                            this.detail_client_job_saved = response.data;
                            
                            //сделать элемент модальным     
                            document.getElementById("Form_Editor_Job_Of_Client").style.display = "block";

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
                onClickApplyFormEditorJobOfClient(){
                    let is_resp_success = false;
                    //Делаем update Фамилии
                    //axios.post("./queries/update_client_job_by_id.php", {'data1': 'AAAAAAAAA'})
                    if (this.detail_client_job!=this.detail_client_job_saved){
                        
                        //alert('разные '+this.detail_client_job+' '+this.detail_client_job_saved+' ');

                        is_resp_success= axios.post("./queries/update_client_job_by_id.php", {clientID: this.detail_client_id, clientJob: this.detail_client_job})
                        .then(function (response) {
                            //console.log(response.data);
                            if (response.data=="1"){
                                //this.is_resp_success = true;
                                //is_resp_success1 = true;
                                return true;
                            } else {
                                alert("Обновлено "+response.data+" записей");
                            }
                        })
                        .catch(function (error) {
                            alert(error);
                            console.log(error);
                        });

                        if (is_resp_success) {
                            //alert("success update");
                            this.$emit('update_client_data', 'Job');
                        }
                    }
                    
                    this.onClickCloseFormEditorJobOfClient();

                    
                },
    },
    template: 
    `
    <!-- Modal content -->
    <div class="modal-content-editor">
        <div class="modal-header">
            <span class="close" @click="onClickCloseFormEditorJobOfClient()">&times;</span>
            <h2>Изменение информации о месте работы клиента</h2>
        </div>
        <div class="modal-body">
            <br>
            <input class="msll_filter" type="input" id="item_for_search" v-model="detail_client_job" />
            <button class="msll_middle_button" type="button" @click="onClickApplyFormEditorJobOfClient()">Применить</button>
            <button class="msll_middle_button" type="button" @click="onClickCloseFormEditorJobOfClient()">Отменить</button>

        </div>
        <div class="modal-footer">
        </div>
    </div>      
    `
    }
