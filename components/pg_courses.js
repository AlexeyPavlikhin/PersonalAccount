export default {

  data() {
    return {
      v_display_style: "display: block",
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
      v_course_contents_items: [],
      v_course_contents_item_name: "",
      
      v_course_items_test:  [
        { course_name: 'ПАЗИС 1',
          course_contents: [
                            {course_contents_item_name: 'Введение 0', course_contents_item_id: '0'},
                            {course_contents_item_name: 'Раздел 1', course_contents_item_id: '1'},
                            {course_contents_item_name: 'Раздел 2', course_contents_item_id: '2'},
                            {course_contents_item_name: 'Раздел 3', course_contents_item_id: '3'}
          ]
        },
        { course_name: 'ПАЗИС 2',
          course_contents: [
                            {course_contents_item_name: 'Введение 1', course_contents_item_id: '10'},
                            {course_contents_item_name: 'Раздел 11', course_contents_item_id: '11'},
                            {course_contents_item_name: 'Раздел 12', course_contents_item_id: '12'},
                            {course_contents_item_name: 'Раздел 13', course_contents_item_id: '13'}
          ]
        }
      ]
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
                //this.users_permited_courses = response.data;
                //формируем массив объетов из результата выборки
                let v_course_name = "";
                this.users_permited_courses = [];
                //console.log(this.v_course_items_test);
                //console.log(this.users_permited_courses.length);

                for (const resp_row of response.data) {
                  if ((this.users_permited_courses.length == 0) || (resp_row.course_name != this.users_permited_courses[this.users_permited_courses.length-1].course_name)){
                    this.users_permited_courses.push(new Object());
                    this.users_permited_courses[this.users_permited_courses.length-1].course_name = resp_row.course_name;
                    this.users_permited_courses[this.users_permited_courses.length-1].course_contents = [];
                    this.users_permited_courses[this.users_permited_courses.length-1].course_display_mode = "display: none";
                    this.users_permited_courses[this.users_permited_courses.length-1].menu_header_class = "accordion__header";
                    this.users_permited_courses[this.users_permited_courses.length-1].available_until = resp_row.available_until;
                    
                  }

                  this.users_permited_courses[this.users_permited_courses.length-1].course_contents.push(new Object());
                  this.users_permited_courses[this.users_permited_courses.length-1].course_contents[this.users_permited_courses[this.users_permited_courses.length-1].course_contents.length-1].course_contents_item_name = resp_row.course_contents_item_name;
                  this.users_permited_courses[this.users_permited_courses.length-1].course_contents[this.users_permited_courses[this.users_permited_courses.length-1].course_contents.length-1].course_contents_item_id = resp_row.course_contents_item_id;
                  this.users_permited_courses[this.users_permited_courses.length-1].course_contents[this.users_permited_courses[this.users_permited_courses.length-1].course_contents.length-1].course_contents_item_menu_class = "accordion_content_item" ;

                }
                //console.log(this.users_permited_courses.length);
                //console.log(this.users_permited_courses);


/*
                // Выбираем первый курс
                for (const users_permited_course of this.users_permited_courses) {
                  this.link_to_selected_course = users_permited_course.course_video_link;
                  //console.log(this.link_to_selected_course);
                  //console.log("BBB");
                  this.on_click(users_permited_course.course_id, users_permited_course.course_name);
                  break;
                }
*/                
                

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
    on_menu_header_click(in_display_mode){
      if (in_display_mode == "display: none"){
        in_display_mode = "display: block";
      } else {
        in_display_mode = "display: none";
      }
      //скрыть все пункты списка
      for (const permited_cours of this.users_permited_courses){
        permited_cours.course_display_mode = "display: none";
      }
      return in_display_mode;
    },

    on_menu_header_click2(in_menu_header_class){
      if (in_menu_header_class == "accordion__header"){
        in_menu_header_class = "accordion__header_selected";
      } else {
        in_menu_header_class = "accordion__header";
      }
      //перекрасить в серый все пункты списка
      for (const permited_cours of this.users_permited_courses){
        permited_cours.menu_header_class = "accordion__header";
      }
      return in_menu_header_class;
    },    

    on_click(in_course_contents_item_id, in_course_contents_item_name){
      //alert(in_course_id);
      //alert(in_course_name);
      this.v_course_contents_item_name = in_course_contents_item_name;
      //получаем все данные выбранного курса
      try {
          axios.get('./queries/get_course_data.php', {
              params: {
                course_contents_item_id: in_course_contents_item_id
              }
          })
          .then((response) => {
              //console.log(response.data)
              if (response.data) {
                  //обрабатываем ответ
                  //console.log(response.data);
                  this.v_course_contents_items = response.data;

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

      //раскрасить все пункты списка в дефолтный стиль
      for (const permited_cours of this.users_permited_courses){
        for (const menu_item of permited_cours.course_contents){
          menu_item.course_contents_item_menu_class = "accordion_content_item";
        }
      }      
    },

    get_display_mode(in_item_type){
      if (in_item_type == "VIDEO"){
        //this.v_display_style = "inline-block";
        return "display: inline-block";
      } else {
        //this.v_display_style = "block";
        return "display: block";
      }
    }

  },

  template:
    `
        <div class='sidenav'>
          <div id="accordion" class="accordion" style="max-width: 30rem; margin: 1rem auto;">

          
            <div class="accordion__item" v-for="course in users_permited_courses">
              <div :class="course.menu_header_class" @click="course.course_display_mode = on_menu_header_click(course.course_display_mode); course.menu_header_class = on_menu_header_click2(course.menu_header_class)">
                <div>{{course.course_name}}</div>
                <div class="black_text">Доступен до {{course.available_until}}</div>
              </div>
              <div class="accordion__body" :style="course.course_display_mode" >
                <div class="accordion__content">
                  <div :class="course_contents_item.course_contents_item_menu_class"  v-for="course_contents_item in course.course_contents" 
                          @click="on_click(course_contents_item.course_contents_item_id, course_contents_item.course_contents_item_name); course_contents_item.course_contents_item_menu_class='accordion_content_item_selected'">
                    {{course_contents_item.course_contents_item_name}}
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <div class='msll_body'>
          <!--h2>{{v_course_contents_item_name}}</h2-->

          <div class="msll_margin_lef_right_20 msll_margin_top_bottom_20" v-for="course_contents_item in v_course_contents_items" :style="get_display_mode(course_contents_item.course_item_type_name)">

            <div class="msll_margin_lef_right_20" v-if="course_contents_item.course_item_type ==='TEXT3'">
              <div class="msll_text_align_left" v-html="course_contents_item.course_item_data"></div>
            </div>

            <div v-if="course_contents_item.course_item_type_name ==='TEXT'" class="msll_atricle_container">
              <div class="msll_text_align_left msll_atricle_content" v-html="course_contents_item.course_item_data"></div>
            </div>

            <div v-if="course_contents_item.course_item_type_name ==='VIDEO'">
              <div v-html="course_contents_item.course_item_data"></div>
              <iframe 
                width="400" 
                height="500" 
                :src="course_contents_item.course_item_data2"
                allow="autoplay; fullscreen; accelerometer; gyroscope; picture-in-picture; encrypted-media" 
                frameborder="0" 
                scrolling="">
              </iframe>    
            </div>
       

            <div v-if="course_contents_item.course_item_type_name ==='PICTURE'">
              <div v-html="course_contents_item.course_item_data"></div>
              <img :src="course_contents_item.course_item_data2" alt="Image" class="msll_courses_images">
            </div>

            <div id="anchor1" v-if="course_contents_item.course_item_type_name ==='DOCPDF'">
              <div v-html="course_contents_item.course_item_data"></div>
              <embed :src="course_contents_item.course_item_data2" type="application/pdf" class="msll_courses_doc_pdf">
            </div>

            <div v-if="course_contents_item.course_item_type_name ==='HREF'">
              <a :href="course_contents_item.course_item_data" target="_blank" rel="noopener noreferrer">{{course_contents_item.course_item_data2}}</a>
            </div>

            <div v-if="course_contents_item.course_item_type_name ==='HREF_INT'">
              <!--a :href="course_contents_item.course_item_data">{{course_contents_item.course_item_data2}}</a-->
              <router-link :to="{ path: '/courses', hash: '#anchor1' }">{{course_contents_item.course_item_data2}}</router-link>
            </div>


            <div v-if="course_contents_item.course_item_type_name ==='ANCHOR1'">
              <a :name="course_contents_item.course_item_data"></a>
            </div>




            <div v-if="course_contents_item.course_item_type ==='VIDEO_save'">
              <div>{{course_contents_item.course_item_data}}</div>
              <iframe 
                width="1400" 
                height="787.5" 
                :src="course_contents_item.course_item_data2"
                allow="autoplay; fullscreen; accelerometer; gyroscope; picture-in-picture; encrypted-media" 
                frameborder="0" 
                scrolling="">
              </iframe>    
            </div>



            <div>
              <iframe v-if="course_contents_item.course_item_type ==='VIDEO1'" 
                width="400" 
                height="500" 
                :src="course_contents_item.course_item_data2"
                allow="autoplay; fullscreen; accelerometer; gyroscope; picture-in-picture; encrypted-media" 
                frameborder="5" 
                scrolling="1">
              </iframe>    
            </div>

            <div v-if="course_contents_item.course_item_type ==='VIDEO1'">
              <iframe 
                width="400" 
                height="500" 
                :src="course_contents_item.course_item_data"
                allow="autoplay; fullscreen; accelerometer; gyroscope; picture-in-picture; encrypted-media" 
                frameborder="5" 
                scrolling="1">
              </iframe>    

              <iframe 
                width="400" 
                height="500" 
                :src="course_contents_item.course_item_data"
                allow="autoplay; fullscreen; accelerometer; gyroscope; picture-in-picture; encrypted-media" 
                frameborder="5" 
                scrolling="">
              </iframe>           

              <iframe 
                width="400" 
                height="500" 
                :src="course_contents_item.course_item_data"
                allow="autoplay; fullscreen; accelerometer; gyroscope; picture-in-picture; encrypted-media" 
                frameborder="5" 
                scrolling="">
              </iframe>                   
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
