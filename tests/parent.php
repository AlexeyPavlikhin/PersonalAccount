
<script src="../js/vue.global.js"></script>

<script type="module">
    import ChildComp1 from './ChildComp.js'
    import ChildComp2 from './ChildComp2.js'
    

    const { createApp } = Vue

    createApp({
    components: {
        ChildComp1,
        ChildComp2
    },
    data() {
        return {
        greeting: 'Привет от родителя'
        }
    }
    }).mount('#app')

</script>

<div id="app">
    <child-comp1 :msg="greeting"></child-comp1>
    <child-comp2 :msg="greeting"></child-comp2>
    <child-comp2 :msg="greeting"></child-comp2>
</div>