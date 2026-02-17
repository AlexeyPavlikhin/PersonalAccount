export default {

  data() {
    return {
      current_route_name: "courses",
      users_permited_courses: [],
      link_to_selected_course: "",
      v_key: "3",
      v_course_items_save:  [ 
                            {course_item_type: 'TEXT', course_item_video_link: '', course_item_text: '<center>ВНИМАНИЕ!!!</center>Публикую специально заранее, чтобы вы могли подумать на тему.<br/>Так наша встреча пройдёт более эффективно.<br/>Итоговый чек-лист я подготовлю в течение недели после мероприятия и выложу сюда. Запись встречи тоже будет здесь.<br/>До скорой встречи!', course_item_href: '', course_item_href_text: '', course_item_ancnor_name: '', course_item_picture: 'https://runtime.video.cloud.yandex.net/pic1.jpg', course_item_doc_pdf: 'https://runtime.video.cloud.yandex.net/doc.pdf'},
                            {course_item_type: 'HREF', course_item_video_link: '', course_item_text: '', course_item_href: 'https://www.google.com', course_item_href_text: 'Зарегистрироваться', course_item_ancnor_name: '', course_item_picture: '', course_item_doc_pdf: ''},
                            {course_item_type: 'HREF_INT', course_item_video_link: '', course_item_text: '', course_item_href: '#anchor1', course_item_href_text: 'Перейти к разделу', course_item_ancnor_name: '', course_item_picture: '', course_item_doc_pdf: ''},
                            {course_item_type: 'VIDEO', course_item_video_link: 'https://runtime.video.cloud.yandex.net/player/video/vplvrnqfpy25eyvbm4xi?autoplay=0&mute=0', course_item_text: '', course_item_href: '', course_item_ancnor_name: '', course_item_picture: '', course_item_doc_pdf: ''},
                            {course_item_type: 'TEXT', course_item_video_link: '', course_item_text: 'Публикую специально заранее, чтобы вы могли подумать на тему.<br/>Так наша встреча пройдёт более эффективно.<br/>Итоговый чек-лист я подготовлю в течение недели после мероприятия и выложу сюда. Запись встречи тоже будет здесь.<br/>До скорой встречи!', course_item_href: '', course_item_href_text: '', course_item_ancnor_name: '', course_item_picture: 'https://runtime.video.cloud.yandex.net/pic1.jpg', course_item_doc_pdf: 'https://runtime.video.cloud.yandex.net/doc.pdf'},
                            {course_item_type: 'VIDEO', course_item_video_link: 'https://runtime.video.cloud.yandex.net/player/playlist/vplqnm3mj4yan33t74o2?autoplay=0&mute=0', course_item_text: '', course_item_href: '', course_item_ancnor_name: '', course_item_href_text: '', course_item_picture: '', course_item_doc_pdf: ''},
                            {course_item_type: 'PICTURE', course_item_video_link: '', course_item_text: '', course_item_href: '', course_item_href_text: '', course_item_ancnor_name: '', course_item_picture: 'https://storage.yandexcloud.net/pavlikhin1/order20042021.jpg', course_item_doc_pdf: ''},
                            {course_item_type: 'ANCHOR', course_item_video_link: '', course_item_text: '', course_item_href: '', course_item_href_text: '', course_item_ancnor_name: 'anchor1',  course_item_picture: '', course_item_doc_pdf: ''},
                            {course_item_type: 'DOCPDF', course_item_video_link: '', course_item_text: '', course_item_href: '', course_item_href_text: '', course_item_ancnor_name: '', course_item_picture: '', course_item_doc_pdf: 'https://storage.yandexcloud.net/pavlikhin1/%D0%AD%D0%BB%D0%B5%D0%BA%D1%82%D1%80%D0%BE%D0%BD%D0%BD%D1%8B%D0%B9_%D0%B1%D0%B8%D0%BB%D0%B5%D1%82_PAVLIKHIN_ALEKSEI_112565651444.pdf'}
                          ],
      v_course_items: [],
      v_current_course_name: ""//,
/*      
      v_course_items_test:  [
        { course_name: 'ПАЗИС 1',
          course_items: [

          ]

        }
      ]
*/        
    }
  },
  mounted() {
    this.$root.check_for_permition_route(this.current_route_name);
    this.$root.$refs.ref_NavigationMenu.setActivMenuItem(this.current_route_name);

    //определяем доступные для пользователя курсы
    try {
        axios.get('./queries/get_current_user_prmited_courses.php', {
            params: {
            }
        })
        .then((response) => {
            //console.log(response.data)
            if (response.data) {
                //обрабатываем ответ
                //console.log(response.data);
                this.users_permited_courses = response.data;

                // Выбираем первый курс
                for (const users_permited_course of this.users_permited_courses) {
                  this.link_to_selected_course = users_permited_course.course_video_link;
                  //console.log(this.link_to_selected_course);
                  //console.log("BBB");
                  this.on_click(users_permited_course.course_id, users_permited_course.course_name);
                  break;
                }
                
                

            } else {
                // пустой ответ
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

//vplvrnqfpy25eyvbm4xi
//vplvwqsydipghjxptlxq
//https://runtime.video.cloud.yandex.net/player/video/vplvwqsydipghjxptlxq?autoplay=0&mute=0

  },
  methods:{
    on_click(in_course_id, in_course_name){
      //alert(in_course_id);
      //alert(in_course_name);
      this.v_current_course_name = in_course_name;
      //получаем все данные выбранного курса
      try {
          axios.get('./queries/get_course_data.php', {
              params: {
                course_id: in_course_id
              }
          })
          .then((response) => {
              //console.log(response.data)
              if (response.data) {
                  //обрабатываем ответ
                  //console.log(response.data);
                  this.v_course_items = response.data;

              } else {
                  // пустой ответ
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




    }
  },

  template:
    `
        <div class='sidenav'>
          <div id="accordion" class="accordion" style="max-width: 30rem; margin: 1rem auto;">
            <div class="accordion__item">
              <div class="accordion__header">
                Заголовок 1
              </div>
              <div class="accordion__body">
                <div class="accordion__content">
                  <a href="#">Пункт 1.1</a>
                  <a href="#">Пункт 1.2</a>
                  <a href="#">Пункт 1.3</a>
                  <a href="#">Пункт 1.4</a>
                  <a href="#">Пункт 1.5</a>
                </div>
              </div>
            </div>
            <div class="accordion__item">
              <div class="accordion__header">
                Заголовок 2
              </div>
              <div class="accordion__body">
                <div class="accordion__content">
                  <a href="#">Пункт 2.1</a>
                  <a href="#">Пункт 2.2</a>
                  <a href="#">Пункт 2.3</a>
                  <a href="#">Пункт 2.4</a>
                  <a href="#">Пункт 2.5</a>
                </div>
              </div>
            </div>
            <div class="accordion__item">
              <div class="accordion__header">
                Заголовок 3
              </div>
              <div class="accordion__body">
                <div class="accordion__content">
                  <a href="#">Пункт 3.1</a>
                  <a href="#">Пункт 3.2</a>
                  <a href="#">Пункт 3.3</a>
                  <a href="#">Пункт 3.4</a>
                  <a href="#">Пункт 3.5</a>
                </div>
              </div>
            </div>
          </div>

          <table class='msll_table'>
              <tbody>
                <tr>
                    <th width='3%'>#</th>
                    <th >Название курса</th>
                    <th width='25%'>куплен до</th>
                </tr>

                <tr v-for="course in users_permited_courses">
                  <td> 
                    <!--input type="radio" :id="course.course_name" :value="course.course_video_link" v-model="link_to_selected_course"/-->
                    <input type="radio" :id="course.course_name" :value="course.course_video_link" v-model="link_to_selected_course" @click="on_click(course.course_id, course.course_name)"/>
                  </td>
                  <td>
                    {{course.course_name}}
                  </td>
                  <td>
                    {{course.available_until}}
                  </td>
                </tr>
              </tbody>
          </table>

        </div>

        <div class='msll_body'>
          <h2>{{v_current_course_name}}</h2>

          <div v-for="v_course_item in v_course_items">

            <div class="msll_margin_lef_right_20" v-if="v_course_item.course_item_type ==='TEXT'">
              <div class="msll_text_align_left" v-html="v_course_item.course_item_text"></div>
            </div>

            <div v-if="v_course_item.course_item_type ==='VIDEO'">
              <iframe 
                width="1400" 
                height="787.5" 
                :src="v_course_item.course_item_video_link"
                allow="autoplay; fullscreen; accelerometer; gyroscope; picture-in-picture; encrypted-media" 
                frameborder="0" 
                scrolling="">
              </iframe>    
            </div>

            <div v-if="v_course_item.course_item_type ==='PICTURE'">
              <img :src="v_course_item.course_item_picture" alt="Image" class="msll_courses_images">
            </div>

            <div id="anchor1" v-if="v_course_item.course_item_type ==='DOCPDF'">
              <embed :src="v_course_item.course_item_doc_pdf" type="application/pdf" class="msll_courses_doc_pdf">
            </div>

            <div v-if="v_course_item.course_item_type ==='HREF'">
              <a :href="v_course_item.course_item_href" target="_blank" rel="noopener noreferrer">{{v_course_item.course_item_href_text}}</a>
            </div>

            <div v-if="v_course_item.course_item_type ==='HREF_INT'">
              <!--a :href="v_course_item.course_item_href">{{v_course_item.course_item_href_text}}</a-->
              <router-link :to="{ path: '/courses', hash: '#anchor1' }">{{v_course_item.course_item_href_text}}</router-link>
            </div>


            <div v-if="v_course_item.course_item_type ==='ANCHOR1'">
              <a :name="v_course_item.course_item_anchor_name"></a>
            </div>


          </div>
          
          <section id="section-one">...</section>
          <br/>
          <br/>
          <br/>  

          <!--div>
            <iframe 
              width="1400" 
              height="787.5" 
              :src="link_to_selected_course"
              allow="autoplay; fullscreen; accelerometer; gyroscope; picture-in-picture; encrypted-media" 
              frameborder="0" 
              scrolling="">
            </iframe>    
            <br/><br/><br/>   
          </div-->
        </div>

        `
}
