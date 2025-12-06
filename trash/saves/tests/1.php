<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vue 3 with Vue Router CDN</title>
</head>
<body>
    <div id="app">
        <router-link to="/">Home</router-link>
        <router-link to="/about">About</router-link>
        <router-view></router-view>
    </div>

</body>
</html>

<!-- Include Vue.js 3 -->
<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<!-- Include Vue Router 4 -->
<script src="https://unpkg.com/vue-router@4/dist/vue-router.global.js"></script>

<script  type="module">
    const { createApp } = Vue;
    const { createRouter, createWebHistory } = VueRouter;

    // Define your components
    const Home = { template: '<div><h1>Home Page</h1></div>' };
    const About = { template: '<div><h1>About Page</h1></div>' };
    //import About from './1.js';
    //import routes from './routes.js';

    // Define your routes
    const routes = [
        
        { path: '/about', component: About },
        { path: '/', component: Home }
    ];

    // Create the router instance
    const router = createRouter({
        history: createWebHistory("#"),
//        mode: "hash",
        routes
    });

    // Create and mount the Vue app
    const app = createApp({
        mounted(){
            //this.$router.push('/about');
        }
    });
    app.use(router); // Use the router
    app.mount('#app');
</script>
